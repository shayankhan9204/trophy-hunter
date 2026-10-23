<?php

namespace App\Services;

use App\Models\Event;
use App\Models\EventContact;
use App\Models\EventSafetyCheck;
use App\Models\Profile;
use App\Models\Team;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Twilio\Exceptions\TwilioException;

/**
 * Service to orchestrate Distress (SOS) alerts.
 *
 * Responsibilities:
 *  - Validate event membership of the team and angler.
 *  - Determine distress coordinates (supplied vs fallback to latest "I'm Safe" safety check).
 *  - Send SOS alert to event contacts with team details and registered anglers.
 *  - Find nearby teams in the same event sharing the SOS team's latest location_code.
 *  - Send distress SMS for each qualifying nearby team with vessel specs and boat profile.
 *  - Idempotency guard and Twilio error handling.
 */
class DistressService
{
    public function __construct(private readonly TwilioSmsService $sms) {}

    /**
     * Process an incoming SOS request.
     *
     * @param int        $eventId
     * @param int        $teamId
     * @param int        $anglerId
     * @param float|null $latitude
     * @param float|null $longitude
     * @param Carbon|null $pressedAt
     * @return array
     *
     * @throws \InvalidArgumentException If membership validation fails.
     * @throws \RuntimeException If location cannot be determined.
     */
    public function processDistress(
        int $eventId,
        int $teamId,
        int $anglerId,
        ?float $latitude = null,
        ?float $longitude = null,
        ?Carbon $pressedAt = null
    ): array {
        $pressedAt = $pressedAt ?? Carbon::now();
        $timeString = $pressedAt->format('Y-m-d H:i:s');

        // ── 1. Validate Event, Team, and Angler Membership ──────────────────────
        $event = Event::where('id', $eventId)->whereNull('deleted_at')->first();
        if (! $event) {
            Log::warning('Distress SOS: Event not found or deleted.', ['event_id' => $eventId]);
            throw new \InvalidArgumentException('The specified event was not found.');
        }

        $team = Team::where('id', $teamId)->whereNull('deleted_at')->first();
        if (! $team) {
            Log::warning('Distress SOS: Team not found or deleted.', ['team_id' => $teamId]);
            throw new \InvalidArgumentException('The specified team was not found.');
        }

        $angler = User::find($anglerId);
        if (! $angler) {
            Log::warning('Distress SOS: Angler user not found.', ['angler_id' => $anglerId]);
            throw new \InvalidArgumentException('The specified angler was not found.');
        }

        // Verify that the team belongs to the event
        $teamInEvent = DB::table('event_team_user')
            ->where('event_id', $eventId)
            ->where('team_id', $teamId)
            ->whereNull('deleted_at')
            ->exists();

        if (! $teamInEvent) {
            Log::warning('Distress SOS: Team does not belong to event.', [
                'event_id' => $eventId,
                'team_id'  => $teamId,
            ]);
            throw new \InvalidArgumentException('The specified team is not registered for this event.');
        }

        // Verify that the angler belongs to this team in this event
        $anglerInTeamEvent = DB::table('event_team_user')
            ->where('event_id', $eventId)
            ->where('team_id', $teamId)
            ->where('user_id', $anglerId)
            ->whereNull('deleted_at')
            ->exists();

        if (! $anglerInTeamEvent) {
            Log::warning('Distress SOS: Angler does not belong to team in event.', [
                'event_id'  => $eventId,
                'team_id'   => $teamId,
                'angler_id' => $anglerId,
            ]);
            throw new \InvalidArgumentException('The specified angler is not registered in this team for this event.');
        }

        // ── 2. Determine Distress Location ──────────────────────────────────────
        $hasSuppliedCoords = ($latitude !== null && $longitude !== null);

        // Fetch latest safety check for the SOS team (needed for fallback coordinates and location_code)
        $sosLatestSafetyCheck = EventSafetyCheck::where('event_id', $eventId)
            ->where('team_id', $teamId)
            ->orderByDesc('time_stamp')
            ->orderByDesc('id')
            ->first();

        if ($hasSuppliedCoords) {
            $distressLat = (float) $latitude;
            $distressLng = (float) $longitude;
            $isFallback  = false;
            $locationCode = $sosLatestSafetyCheck?->location_code;
        } else {
            // Must have a usable fallback safety check
            if (! $sosLatestSafetyCheck || $sosLatestSafetyCheck->latitude === null || $sosLatestSafetyCheck->longitude === null) {
                Log::warning('Distress SOS: No coordinates supplied and no usable fallback safety check found.', [
                    'event_id'  => $eventId,
                    'team_id'   => $teamId,
                    'angler_id' => $anglerId,
                ]);
                throw new \RuntimeException('No distress location coordinates provided and no fallback safety check location found.');
            }

            $distressLat  = (float) $sosLatestSafetyCheck->latitude;
            $distressLng  = (float) $sosLatestSafetyCheck->longitude;
            $locationCode = $sosLatestSafetyCheck->location_code;
            $isFallback   = true;
        }

        $formattedCoords = $this->formatCoordinatesToDDMMSSS($distressLat, $distressLng);

        // ── 3. Idempotency Guard ────────────────────────────────────────────────
        // Prevent duplicate Twilio dispatches if duplicate requests arrive within 60 seconds
        $cacheKey = sprintf('distress_sos_%d_%d_%d_%.4f_%.4f', $eventId, $teamId, $anglerId, $distressLat, $distressLng);
        if (! Cache::add($cacheKey, true, now()->addSeconds(60))) {
            Log::info('Distress SOS: Duplicate distress alert suppressed by idempotency guard.', [
                'cache_key' => $cacheKey,
            ]);

            return [
                'duplicate'         => true,
                'distress_location' => [
                    'latitude'      => $distressLat,
                    'longitude'     => $distressLng,
                    'formatted'     => $formattedCoords,
                    'is_fallback'   => $isFallback,
                    'location_code' => $locationCode,
                ],
                'time_pressed'      => $timeString,
                'contacts_notified' => 0,
                'nearby_teams_notified' => 0,
            ];
        }

        // ── 4. Send Distress SMS to Event Contacts ──────────────────────────────
        $eventContacts = $event->contacts()->whereNull('deleted_at')->get();
        if ($eventContacts->isEmpty()) {
            Log::warning('Distress SOS: No active event contacts configured for event.', [
                'event_id' => $eventId,
            ]);
        }

        // Get all members of the SOS team registered for this event
        $sosTeamMembers = DB::table('event_team_user')
            ->join('users', 'users.id', '=', 'event_team_user.user_id')
            ->where('event_team_user.event_id', $eventId)
            ->where('event_team_user.team_id', $teamId)
            ->whereNull('event_team_user.deleted_at')
            ->select('users.id', 'users.name', 'users.phone')
            ->get();

        $memberLines = [];
        foreach ($sosTeamMembers as $member) {
            $formattedPhone = $this->formatPhoneForDisplay($member->phone);
            $memberLines[]  = "– {$member->name} ({$formattedPhone})";
        }

        $sosContactMessage = implode("\n", array_filter([
            'DISTRESS ALERT',
            "Event: {$event->name}",
            "Team: {$team->name}",
            "Distress Location: {$formattedCoords}",
            "Time: {$timeString}",
            'Registered Team Members:',
            implode("\n", $memberLines),
        ]));

        $contactsSent = 0;
        $contactsFailed = 0;

        foreach ($eventContacts as $contact) {
            if (empty($contact->phone)) {
                Log::warning('Distress SOS: Event contact has no phone number — skipping.', [
                    'event_contact_id' => $contact->id,
                    'name'             => $contact->name,
                ]);
                continue;
            }

            $to = $this->toE164($contact->phone);

            try {
                $this->sms->send($to, $sosContactMessage);
                $contactsSent++;
            } catch (TwilioException $e) {
                $contactsFailed++;
                Log::error('Distress SOS: Twilio send failed for event contact.', [
                    'event_contact_id' => $contact->id,
                    'to'               => $to,
                    'error'            => $e->getMessage(),
                ]);
            }
        }

        // ── 5. SMS to Other Nearby Boats ───────────────────────────────────────
        // The nearby teams (found in the same location_code) receive an alert about
        // the SOS team in distress, including the SOS team's persons on board and
        // the SOS team's boat information (registration, length, make, colour).
        $nearbyTeamsAlertsSent = 0;
        $qualifyingTeamsData   = [];

        if (! empty($locationCode)) {
            // Count registered anglers on board the SOS team
            $sosAnglerCount = $sosTeamMembers->count();
            $sosPersonsPhrase = "{$sosAnglerCount} " . ($sosAnglerCount === 1 ? 'person on board' : 'persons on board');

            // Retrieve boat profile data from the SOS team members
            $sosMemberUserIds = $sosTeamMembers->pluck('id');
            $sosBoatProfile = Profile::whereIn('user_id', $sosMemberUserIds)
                ->where(function ($q) {
                    $q->whereNotNull('boat_registration')
                      ->orWhereNotNull('boat_length')
                      ->orWhereNotNull('boat_maker')
                      ->orWhereNotNull('boat_color');
                })
                ->first() ?? Profile::whereIn('user_id', $sosMemberUserIds)->first();

            $sosBoatReg   = $sosBoatProfile?->boat_registration ?: 'N/A';
            $sosBoatLen   = $sosBoatProfile?->boat_length ?: 'N/A';
            $sosBoatMake  = $sosBoatProfile?->boat_maker ?: 'N/A';
            $sosBoatColor = $sosBoatProfile?->boat_color ?: 'N/A';

            // Build SMS describing the SOS team in distress
            $nearbyMessage = implode("\n", [
                'DISTRESS ALERT',
                "Team: {$team->name}",
                "Distress Location: {$formattedCoords}",
                "Distress Time: {$timeString}",
                "Persons on Board: {$sosPersonsPhrase}",
                "Boat Registration: {$sosBoatReg}",
                "Boat Length: {$sosBoatLen}",
                "Boat Make: {$sosBoatMake}",
                "Boat Colour: {$sosBoatColor}",
            ]);

            $otherTeamIds = DB::table('event_team_user')
                ->where('event_id', $eventId)
                ->where('team_id', '!=', $teamId)
                ->whereNull('deleted_at')
                ->distinct()
                ->pluck('team_id');

            foreach ($otherTeamIds as $otherTeamId) {
                $otherLatestCheck = EventSafetyCheck::where('event_id', $eventId)
                    ->where('team_id', $otherTeamId)
                    ->orderByDesc('time_stamp')
                    ->orderByDesc('id')
                    ->first();

                if ($otherLatestCheck && $otherLatestCheck->location_code === $locationCode) {
                    $qualifyingTeam = Team::find($otherTeamId);
                    if (! $qualifyingTeam) {
                        continue;
                    }

                    // Retrieve anglers of this nearby qualifying team
                    $nearbyAnglers = DB::table('event_team_user')
                        ->join('users', 'users.id', '=', 'event_team_user.user_id')
                        ->where('event_team_user.event_id', $eventId)
                        ->where('event_team_user.team_id', $otherTeamId)
                        ->whereNull('event_team_user.deleted_at')
                        ->select('users.id', 'users.name', 'users.phone')
                        ->get();

                    $qualifyingTeamsData[] = [
                        'team_id'               => $otherTeamId,
                        'team_name'             => $qualifyingTeam->name,
                        'anglers_count'         => $nearbyAnglers->count(),
                        'sos_persons_on_board'  => $sosPersonsPhrase,
                        'sos_boat_registration' => $sosBoatReg,
                        'sos_boat_length'       => $sosBoatLen,
                        'sos_boat_make'         => $sosBoatMake,
                        'sos_boat_color'        => $sosBoatColor,
                        'message'               => $nearbyMessage,
                    ];

                    // Send the distress SMS to each member/angler of the nearby boat
                    foreach ($nearbyAnglers as $nearbyAngler) {
                        if (empty($nearbyAngler->phone)) {
                            Log::warning('Distress SOS: Nearby team angler has no phone number — skipping.', [
                                'nearby_team_id' => $otherTeamId,
                                'angler_id'      => $nearbyAngler->id,
                                'angler_name'    => $nearbyAngler->name,
                            ]);
                            continue;
                        }

                        $to = $this->toE164($nearbyAngler->phone);
                        try {
                            $this->sms->send($to, $nearbyMessage);
                            $nearbyTeamsAlertsSent++;
                        } catch (TwilioException $e) {
                            Log::error('Distress SOS: Twilio send failed for nearby team angler.', [
                                'nearby_team_id' => $otherTeamId,
                                'angler_id'      => $nearbyAngler->id,
                                'to'             => $to,
                                'error'          => $e->getMessage(),
                            ]);
                        }
                    }
                }
            }
        }

        Log::info('Distress SOS processed successfully.', [
            'event_id'              => $eventId,
            'team_id'               => $teamId,
            'angler_id'             => $anglerId,
            'latitude'              => $distressLat,
            'longitude'             => $distressLng,
            'is_fallback'           => $isFallback,
            'location_code'         => $locationCode,
            'contacts_sent'         => $contactsSent,
            'contacts_failed'       => $contactsFailed,
            'nearby_teams_matched'  => count($qualifyingTeamsData),
            'nearby_alerts_sent'    => $nearbyTeamsAlertsSent,
        ]);

        return [
            'duplicate'             => false,
            'distress_location'     => [
                'latitude'          => $distressLat,
                'longitude'         => $distressLng,
                'formatted'         => $formattedCoords,
                'is_fallback'       => $isFallback,
                'location_code'     => $locationCode,
            ],
            'time_pressed'          => $timeString,
            'contacts_notified'     => $contactsSent,
            'contacts_failed'       => $contactsFailed,
            'nearby_teams_matched'  => count($qualifyingTeamsData),
            'nearby_alerts_sent'    => $nearbyTeamsAlertsSent,
            'nearby_teams'          => $qualifyingTeamsData,
        ];
    }

    /**
     * Format decimal latitude and longitude to DD MM SSS (Degrees Minutes Seconds).
     *
     * Example:
     *   -33.8688, 151.2093 -> 'S33 52 008, E151 12 033'
     *
     * @param float|null $lat
     * @param float|null $lng
     * @return string
     */
    public function formatCoordinatesToDDMMSSS(?float $lat, ?float $lng): string
    {
        if ($lat === null || $lng === null) {
            return 'N/A';
        }

        // Latitude
        $latDir = $lat < 0 ? 'S' : 'N';
        $absLat = abs($lat);
        $latDeg = (int) floor($absLat);
        $latMinFloat = ($absLat - $latDeg) * 60;
        $latMin = (int) floor($latMinFloat);
        $latSec = (int) round(($latMinFloat - $latMin) * 60);

        if ($latSec >= 60) {
            $latSec = 0;
            $latMin++;
        }
        if ($latMin >= 60) {
            $latMin = 0;
            $latDeg++;
        }

        // Longitude
        $lngDir = $lng < 0 ? 'W' : 'E';
        $absLng = abs($lng);
        $lngDeg = (int) floor($absLng);
        $lngMinFloat = ($absLng - $lngDeg) * 60;
        $lngMin = (int) floor($lngMinFloat);
        $lngSec = (int) round(($lngMinFloat - $lngMin) * 60);

        if ($lngSec >= 60) {
            $lngSec = 0;
            $lngMin++;
        }
        if ($lngMin >= 60) {
            $lngMin = 0;
            $lngDeg++;
        }

        return sprintf(
            "%s%02d %02d %03d, %s%02d %02d %03d",
            $latDir, $latDeg, $latMin, $latSec,
            $lngDir, $lngDeg, $lngMin, $lngSec
        );
    }

    /**
     * Convert a phone number to E.164 for Twilio.
     *
     * Handles:
     *   - '+61402066282' -> '+61402066282' (already E.164)
     *   - '0409078051'   -> '+61409078051' (standard 10-digit AU mobile with leading 0)
     *   - '402066282'    -> '+61402066282' (9-digit AU mobile with stripped leading 0)
     *   - '61402066282'  -> '+61402066282' (bare digits with country code)
     */
    public function toE164(string $phone): string
    {
        $normalized = preg_replace('/[\s\-\(\)]/', '', $phone);

        if (str_starts_with($normalized, '+')) {
            return $normalized;
        }

        $cc = config('services.twilio.default_country_code', '61');

        // Standard local mobile: '0409078051' -> '+61409078051'
        if (str_starts_with($normalized, '0')) {
            return '+' . $cc . substr($normalized, 1);
        }

        // 9-digit AU mobile where Excel/import stripped the leading 0: '402066282' -> '+61402066282'
        if (strlen($normalized) === 9 && str_starts_with($normalized, '4') && $cc === '61') {
            return '+' . $cc . $normalized;
        }

        // Already has country code prefix without '+': '61402066282' -> '+61402066282'
        if (str_starts_with($normalized, $cc)) {
            return '+' . $normalized;
        }

        return '+' . $cc . $normalized;
    }

    /**
     * Format a phone number for user-facing SMS display.
     */
    public function formatPhoneForDisplay(?string $phone): string
    {
        if (empty($phone)) {
            return 'No phone';
        }

        $clean = preg_replace('/[\s\-]/', '', $phone);

        if (str_starts_with($clean, '0') || str_starts_with($clean, '+')) {
            return $clean;
        }

        return '0' . $clean;
    }
}
