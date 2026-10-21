<?php

namespace App\Services;

use App\Models\EventContact;
use App\Models\EventDate;
use App\Models\MissedCheckinAlertSent;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Twilio\Exceptions\TwilioException;

/**
 * Orchestrates "missed check-in" SMS alerts.
 *
 * Responsibilities:
 *  - Determine which teams missed the safety check-in window.
 *  - Apply consent filtering (share_contact_data on event_team_user).
 *  - Build and split SMS messages.
 *  - Dispatch via TwilioSmsService with per-contact idempotency.
 */
class MissedCheckinSmsService
{
    /** Maximum characters per SMS part before we split. */
    private const MAX_SMS_LENGTH = 1500;

    public function __construct(private readonly TwilioSmsService $sms) {}

    // -------------------------------------------------------------------------
    // Public entry point
    // -------------------------------------------------------------------------

    /**
     * Process a single interval for the given event date.
     *
     * @param  EventDate $eventDate      The event date record (must have event() loaded or loadable).
     * @param  int       $intervalNumber 1-based slot index for this event date.
     */
    public function processInterval(EventDate $eventDate, int $intervalNumber): void
    {
        $event = $eventDate->event ?? $eventDate->load('event')->event;

        // Guard: feature must be enabled at event level
        if (! $event->has_im_safe) {
            Log::warning('MissedCheckin: Event does not have has_im_safe enabled — skipping.', [
                'event_id'        => $event->id,
                'event_date_id'   => $eventDate->id,
                'interval_number' => $intervalNumber,
            ]);
            return;
        }

        // Compute interval window
        $intervalStart = Carbon::parse("{$eventDate->date} {$eventDate->start_time}")
            ->addMinutes((int) $eventDate->im_safe_interval * ($intervalNumber - 1));
        $intervalEnd   = $intervalStart->copy()->addMinutes((int) $eventDate->im_safe_interval);
        $dueTime       = $intervalEnd->format('H:i');

        // Teams signed on to the event
        $signedOnTeamIds = $this->getSignedOnTeamIds($event->id);
        if ($signedOnTeamIds->isEmpty()) {
            return;
        }

        // Teams that ARE safe (any angler checked in within the interval)
        $safeTeamIds = $this->getSafeTeamIds($event->id, $intervalStart, $intervalEnd);

        // Missed = signed-on minus safe
        $missedTeamIds = $signedOnTeamIds->diff($safeTeamIds)->values();
        if ($missedTeamIds->isEmpty()) {
            Log::info('MissedCheckin: All teams safe, no SMS needed.', [
                'event_id'        => $event->id,
                'event_date_id'   => $eventDate->id,
                'interval_number' => $intervalNumber,
            ]);
            return;
        }

        // Build per-row strings (consent-filtered)
        $rows = $this->buildMessageRows($event->id, $missedTeamIds, $intervalNumber);
        if (empty($rows)) {
            Log::warning('MissedCheckin: Missed teams found but no consenting anglers — no SMS sent.', [
                'event_id'        => $event->id,
                'interval_number' => $intervalNumber,
            ]);
            return;
        }

        // Split into ≤1500-char parts
        $parts = $this->splitIntoParts((string) $event->name, $intervalNumber, $dueTime, $rows);

        // Send to every event contact (idempotent per contact)
        $contacts = $event->contacts()->whereNull('deleted_at')->get();
        foreach ($contacts as $contact) {
            $this->sendToContact($contact, $eventDate, $intervalNumber, $parts);
        }
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /** Return distinct team_ids currently signed on to an event (soft-delete aware). */
    private function getSignedOnTeamIds(int $eventId): Collection
    {
        return DB::table('event_team_user')
            ->where('event_id', $eventId)
            ->whereNull('deleted_at')
            ->distinct()
            ->pluck('team_id');
    }

    /** Return team_ids where at least one angler's time_stamp falls within the interval. */
    private function getSafeTeamIds(int $eventId, Carbon $start, Carbon $end): Collection
    {
        return DB::table('event_safety_checks')
            ->where('event_id', $eventId)
            ->whereBetween('time_stamp', [$start->toDateTimeString(), $end->toDateTimeString()])
            ->distinct()
            ->pluck('team_id');
    }

    /**
     * Build one row string per consenting angler in each missed team.
     *
     * Format: "Team Name - Angler Name - 0412345678"
     */
    private function buildMessageRows(int $eventId, Collection $missedTeamIds, int $intervalNumber): array
    {
        $rows = [];

        foreach ($missedTeamIds as $teamId) {
            $anglers = DB::table('event_team_user')
                ->join('users',  'users.id',  '=', 'event_team_user.user_id')
                ->join('teams',  'teams.id',  '=', 'event_team_user.team_id')
                ->where('event_team_user.event_id', $eventId)
                ->where('event_team_user.team_id', $teamId)
                ->whereNull('event_team_user.deleted_at')
                ->where('event_team_user.share_contact_data', true)
                ->select(
                    'teams.name  as team_name',
                    'users.name  as angler_name',
                    'users.phone as angler_phone'
                )
                ->get();

            if ($anglers->isEmpty()) {
                Log::warning('MissedCheckin: Team has no consenting anglers — omitted from SMS.', [
                    'event_id'        => $eventId,
                    'team_id'         => $teamId,
                    'interval_number' => $intervalNumber,
                ]);
                continue;
            }

            foreach ($anglers as $angler) {
                $phone  = $this->formatAnglerPhone($angler->angler_phone);
                $rows[] = "{$angler->team_name} - {$angler->angler_name} - {$phone}";
            }
        }

        return $rows;
    }

    /**
     * Format an angler phone for the message body.
     *
     * Upload convention: stored numbers may omit the leading "0".
     * Prepend "0" only when the number doesn't already start with it.
     * Never modify stored data.
     */
    private function formatAnglerPhone(?string $phone): string
    {
        if (empty($phone)) {
            return 'No phone';
        }

        // Strip whitespace/dashes for clean comparison only
        $clean = preg_replace('/[\s\-]/', '', $phone);

        if (str_starts_with($clean, '0')) {
            return $clean;
        }

        return '0' . $clean;
    }

    /**
     * Convert an event contact phone number to E.164 for Twilio.
     *
     * Assumes the country code from config('services.twilio.default_country_code')
     * (default: '61' for Australia) when the number starts with a bare "0".
     */
    public function toE164(string $phone): string
    {
        $normalized = preg_replace('/[\s\-\(\)]/', '', $phone);

        // Already E.164
        if (str_starts_with($normalized, '+')) {
            return $normalized;
        }

        $cc = config('services.twilio.default_country_code', '61');

        if (str_starts_with($normalized, '0')) {
            // "0412345678" → "+61412345678"
            return '+' . $cc . substr($normalized, 1);
        }

        Bare digits with country code already (no leading + or 0)
        return '+' . $normalized;
    }

    /** Build the SMS header line. */
    private function buildHeader(string $eventName, int $intervalNumber, string $dueTime): string
    {
        return implode("\n", [
            "Safety Alert: Missed \"I'm Safe\" check-ins for {$eventName}",
            "Due: {$dueTime}",   
        ]);
    }

    /**
     * Split rows into SMS parts, each ≤ MAX_SMS_LENGTH characters.
     * The header is prepended to every part so each SMS is self-contained.
     * Breaks only between rows — never mid-row.
     */
    private function splitIntoParts(string $eventName, int $intervalNumber, string $dueTime, array $rows): array
    {
        $header  = $this->buildHeader($eventName, $intervalNumber, $dueTime);
        $parts   = [];
        $current = $header;

        foreach ($rows as $row) {
            $candidate = $current . "\n" . $row;

            if (strlen($candidate) > self::MAX_SMS_LENGTH && $current !== $header) {
                // Flush current part, start fresh with header + this row
                $parts[]  = $current;
                $current  = $header . "\n" . $row;
            } else {
                $current = $candidate;
            }
        }

        // Flush the last part (only if it has rows beyond the header)
        if ($current !== $header) {
            $parts[] = $current;
        }

        return $parts;
    }

    /**
     * Send all SMS parts to a single event contact.
     *
     * Idempotent: skips the contact if a sent record already exists.
     * Writes the idempotency record only after all parts are sent successfully.
     * If any part fails, the record is NOT written so the next job attempt retries.
     */
    private function sendToContact(
        EventContact $contact,
        EventDate    $eventDate,
        int          $intervalNumber,
        array        $parts
    ): void {
        // Per-contact idempotency check
        $alreadySent = MissedCheckinAlertSent::where('event_date_id', $eventDate->id)
            ->where('interval_number', $intervalNumber)
            ->where('event_contact_id', $contact->id)
            ->exists();

        if ($alreadySent) {
            Log::info('MissedCheckin: Alert already sent to contact — skipping.', [
                'event_contact_id' => $contact->id,
                'event_date_id'    => $eventDate->id,
                'interval_number'  => $intervalNumber,
            ]);
            return;
        }

        if (empty($contact->phone)) {
            Log::warning('MissedCheckin: Contact has no phone number — skipping.', [
                'event_contact_id' => $contact->id,
            ]);
            return;
        }

        $to      = $this->toE164($contact->phone);
        $lastSid = null;

        foreach ($parts as $index => $body) {
            try {
                $sid     = $this->sms->send($to, $body);
                $lastSid = $sid;
            } catch (TwilioException $e) {
                // Log and bail; idempotency record is NOT written → next retry will re-send.
                // One failed contact must not block others — the caller (processInterval) will
                // continue to the next contact.
                Log::error('MissedCheckin: Twilio send failed for contact.', [
                    'event_contact_id' => $contact->id,
                    'to'               => $to,
                    'part'             => $index + 1,
                    'total_parts'      => count($parts),
                    'error'            => $e->getMessage(),
                ]);
                return;  // abort only this contact's sending
            }
        }

        // All parts sent successfully — record idempotency
        MissedCheckinAlertSent::create([
            'event_date_id'      => $eventDate->id,
            'interval_number'    => $intervalNumber,
            'event_contact_id'   => $contact->id,
            'twilio_message_sid' => $lastSid,
        ]);
    }
}
