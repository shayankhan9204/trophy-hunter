<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventContact;
use App\Models\EventDate;
use App\Models\EventLocationArea;
use App\Models\EventSafetyCheck;
use App\Models\Profile;
use App\Models\Team;
use App\Models\User;
use App\Services\DistressService;
use App\Services\TwilioSmsService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;
use Twilio\Exceptions\TwilioException;

class DistressApiTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;
    protected Event $event;
    protected Team $team;
    protected User $angler;
    protected EventLocationArea $locationArea;

    protected function setUp(): void
    {
        parent::setUp();

        // Create base models for testing
        $this->user = User::factory()->create();

        $this->event = Event::create([
            'name'        => 'Test Fishing Championship',
            'location'    => 'Sydney Harbour',
            'has_im_safe' => true,
        ]);

        $this->team = Team::create([
            'name'     => 'Marlin Hunters',
            'team_uid' => 'TM-TEST-001',
        ]);

        $this->angler = User::create([
            'name'     => 'John Doe',
            'email'    => 'john.doe.test@example.com',
            'phone'    => '0412345678',
            'password' => bcrypt('secret123'),
        ]);

        // Register team and angler in the event
        DB::table('event_team_user')->insert([
            'event_id'           => $this->event->id,
            'team_id'            => $this->team->id,
            'user_id'            => $this->angler->id,
            'share_contact_data' => true,
            'created_at'         => now(),
            'updated_at'         => now(),
        ]);

        $this->locationArea = EventLocationArea::create([
            'event_id'           => $this->event->id,
            'location_reference' => 'LOC-A1',
            'coordinates'        => '[]',
        ]);
    }

    /**
     * 1. VALIDATION: Missing required fields.
     */
    public function test_distress_api_requires_event_id_team_id_and_angler_id(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/events/distress', []);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Validation failed',
            ]);

        $response->assertJsonValidationErrors(['event_id', 'team_id', 'angler_id']);
    }

    /**
     * 1. VALIDATION: Invalid coordinate ranges.
     */
    public function test_distress_api_validates_coordinate_ranges(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/events/distress', [
            'event_id'  => $this->event->id,
            'team_id'   => $this->team->id,
            'angler_id' => $this->angler->id,
            'latitude'  => 95.0, // Invalid: must be between -90 and 90
            'longitude' => 200.0, // Invalid: must be between -180 and 180
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['latitude', 'longitude']);
    }

    /**
     * 2. AUTHORIZATION / EVENT MEMBERSHIP: Team or angler not in event.
     */
    public function test_distress_api_fails_if_team_or_angler_not_registered_in_event(): void
    {
        $unregisteredTeam = Team::create(['name' => 'Outsider Team']);

        $response = $this->actingAs($this->user)->postJson('/api/events/distress', [
            'event_id'  => $this->event->id,
            'team_id'   => $unregisteredTeam->id,
            'angler_id' => $this->angler->id,
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'The specified team is not registered for this event.',
            ]);
    }

    /**
     * 3. LOCATION & FALLBACK: Uses supplied coordinates when both provided.
     */
    public function test_distress_api_uses_supplied_coordinates(): void
    {
        EventContact::create([
            'event_id' => $this->event->id,
            'name'     => 'Safety Officer',
            'phone'    => '0499887766',
        ]);

        $mockSms = Mockery::mock(TwilioSmsService::class);
        $mockSms->shouldReceive('send')
            ->once()
            ->withArgs(function ($to, $body) {
                // Must contain location in (longitude, latitude) format
                return str_contains($body, 'Distress Location: (151.2093, -33.8688)')
                    && str_contains($body, 'Team: Marlin Hunters');
            })
            ->andReturn('SM_TEST_SID');

        $this->app->instance(TwilioSmsService::class, $mockSms);

        $response = $this->actingAs($this->user)->postJson('/api/events/distress', [
            'event_id'  => $this->event->id,
            'team_id'   => $this->team->id,
            'angler_id' => $this->angler->id,
            'latitude'  => -33.8688,
            'longitude' => 151.2093,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data'    => [
                    'distress_location' => [
                        'latitude'    => -33.8688,
                        'longitude'   => 151.2093,
                        'is_fallback' => false,
                    ],
                    'contacts_notified' => 1,
                ],
            ]);
    }

    /**
     * 3. LOCATION & FALLBACK: Falls back to latest safety check when coordinates omitted.
     */
    public function test_distress_api_falls_back_to_latest_safety_check_coordinates(): void
    {
        EventContact::create([
            'event_id' => $this->event->id,
            'name'     => 'Safety Officer',
            'phone'    => '0499887766',
        ]);

        // Create an older safety check
        EventSafetyCheck::create([
            'event_id'               => $this->event->id,
            'team_id'                => $this->team->id,
            'angler_id'              => $this->angler->id,
            'event_location_area_id' => $this->locationArea->id,
            'location_code'          => 'LOC-OLD',
            'latitude'               => -30.0000,
            'longitude'              => 150.0000,
            'time_stamp'             => Carbon::now()->subHours(2),
        ]);

        // Create the latest safety check
        EventSafetyCheck::create([
            'event_id'               => $this->event->id,
            'team_id'                => $this->team->id,
            'angler_id'              => $this->angler->id,
            'event_location_area_id' => $this->locationArea->id,
            'location_code'          => 'LOC-LATEST',
            'latitude'               => -32.1234,
            'longitude'              => 152.5678,
            'time_stamp'             => Carbon::now()->subMinutes(10),
        ]);

        $mockSms = Mockery::mock(TwilioSmsService::class);
        $mockSms->shouldReceive('send')
            ->once()
            ->withArgs(function ($to, $body) {
                return str_contains($body, 'Distress Location: (152.5678, -32.1234)');
            })
            ->andReturn('SM_FALLBACK_SID');

        $this->app->instance(TwilioSmsService::class, $mockSms);

        $response = $this->actingAs($this->user)->postJson('/api/events/distress', [
            'event_id'  => $this->event->id,
            'team_id'   => $this->team->id,
            'angler_id' => $this->angler->id,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data'    => [
                    'distress_location' => [
                        'latitude'      => -32.1234,
                        'longitude'     => 152.5678,
                        'is_fallback'   => true,
                        'location_code' => 'LOC-LATEST',
                    ],
                ],
            ]);
    }

    /**
     * 3. LOCATION & FALLBACK: Fails when no coordinates and no safety check fallback exists.
     */
    public function test_distress_api_fails_when_no_usable_fallback_location_exists(): void
    {
        $mockSms = Mockery::mock(TwilioSmsService::class);
        $mockSms->shouldNotReceive('send');
        $this->app->instance(TwilioSmsService::class, $mockSms);

        $response = $this->actingAs($this->user)->postJson('/api/events/distress', [
            'event_id'  => $this->event->id,
            'team_id'   => $this->team->id,
            'angler_id' => $this->angler->id,
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'No distress location coordinates provided and no fallback safety check location found.',
            ]);
    }

    /**
     * 4 & 5. MATCHING TEAMS & MESSAGE CONTENT:
     * Finds other teams in same event with same latest location_code, formats persons on board & boat profile.
     */
    public function test_distress_api_finds_nearby_teams_and_sends_formatted_sms(): void
    {
        EventContact::create([
            'event_id' => $this->event->id,
            'name'     => 'Event Director',
            'phone'    => '0411223344',
        ]);

        // SOS team's latest safety check is at 'GRID-42'
        EventSafetyCheck::create([
            'event_id'               => $this->event->id,
            'team_id'                => $this->team->id,
            'angler_id'              => $this->angler->id,
            'event_location_area_id' => $this->locationArea->id,
            'location_code'          => 'GRID-42',
            'latitude'               => -33.5000,
            'longitude'              => 151.1000,
            'time_stamp'             => Carbon::now()->subMinutes(15),
        ]);

        // Nearby qualifying team 1: registered in event, latest check at 'GRID-42'
        $nearbyTeam = Team::create(['name' => 'Ocean Raiders']);

        $angler2 = User::create([
            'name'     => 'Alice Smith',
            'email'    => 'alice@example.com',
            'phone'    => '0422334455',
            'password' => bcrypt('password'),
        ]);

        $angler3 = User::create([
            'name'     => 'Bob Jones',
            'email'    => 'bob@example.com',
            'phone'    => '0433445566',
            'password' => bcrypt('password'),
        ]);

        DB::table('event_team_user')->insert([
            ['event_id' => $this->event->id, 'team_id' => $nearbyTeam->id, 'user_id' => $angler2->id, 'share_contact_data' => true, 'created_at' => now(), 'updated_at' => now()],
            ['event_id' => $this->event->id, 'team_id' => $nearbyTeam->id, 'user_id' => $angler3->id, 'share_contact_data' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Boat profile for the SOS team (the vessel in distress)
        Profile::create([
            'user_id'           => $this->angler->id,
            'boat_registration' => 'SOS-REG-1',
            'boat_length'       => '7.2m',
            'boat_maker'        => 'Haines Hunter',
            'boat_color'        => 'Red / White',
        ]);

        EventSafetyCheck::create([
            'event_id'               => $this->event->id,
            'team_id'                => $nearbyTeam->id,
            'angler_id'              => $angler2->id,
            'event_location_area_id' => $this->locationArea->id,
            'location_code'          => 'GRID-42',
            'latitude'               => -33.5200,
            'longitude'              => 151.1100,
            'time_stamp'             => Carbon::now()->subMinutes(5),
        ]);

        // Non-qualifying team: latest check at 'GRID-99'
        $farTeam = Team::create(['name' => 'Far Away Anglers']);
        $farAngler = User::create([
            'name'     => 'Charlie Far',
            'email'    => 'charlie@example.com',
            'phone'    => '0444556677',
            'password' => bcrypt('password'),
        ]);

        DB::table('event_team_user')->insert([
            'event_id' => $this->event->id,
            'team_id'  => $farTeam->id,
            'user_id'  => $farAngler->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        EventSafetyCheck::create([
            'event_id'               => $this->event->id,
            'team_id'                => $farTeam->id,
            'angler_id'              => $farAngler->id,
            'event_location_area_id' => $this->locationArea->id,
            'location_code'          => 'GRID-99',
            'latitude'               => -35.0000,
            'longitude'              => 150.0000,
            'time_stamp'             => Carbon::now()->subMinutes(2),
        ]);

        $mockSms = Mockery::mock(TwilioSmsService::class);

        // Expectation 1: SOS alert to event contact with SOS team details & members
        $mockSms->shouldReceive('send')
            ->once()
            ->withArgs(function ($to, $body) {
                return str_contains($body, '🚨 DISTRESS (SOS) ALERT')
                    && str_contains($body, 'Team: Marlin Hunters')
                    && str_contains($body, 'Distress Location: (151.2000, -33.4000)')
                    && str_contains($body, '– John Doe (0412345678)');
            })
            ->andReturn('SM_SOS_SID');

        // Expectation 2: Alert to nearby team members containing SOS team's boat specs & persons on board
        $mockSms->shouldReceive('send')
            ->twice() // sent to both Alice and Bob on the nearby boat
            ->withArgs(function ($to, $body) {
                return str_contains($body, '🚨 DISTRESS (SOS) ALERT - NEARBY VESSEL IN DISTRESS')
                    && str_contains($body, 'Vessel in Distress: Marlin Hunters')
                    && str_contains($body, 'Distress Location: (151.2000, -33.4000)')
                    && str_contains($body, 'Persons on Board: 1 person on board')
                    && str_contains($body, 'Boat Registration: SOS-REG-1')
                    && str_contains($body, 'Boat Length: 7.2m')
                    && str_contains($body, 'Boat Make: Haines Hunter')
                    && str_contains($body, 'Boat Colour: Red / White');
            })
            ->andReturn('SM_NEARBY_SID');

        $this->app->instance(TwilioSmsService::class, $mockSms);

        $response = $this->actingAs($this->user)->postJson('/api/events/distress', [
            'event_id'  => $this->event->id,
            'team_id'   => $this->team->id,
            'angler_id' => $this->angler->id,
            'latitude'  => -33.4000,
            'longitude' => 151.2000,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data'    => [
                    'contacts_notified'    => 1,
                    'nearby_teams_matched' => 1,
                    'nearby_alerts_sent'   => 2, // 2 anglers notified on nearby boat
                ],
            ]);
    }

    /**
     * 6. RELIABILITY: Twilio send failure does not crash the API.
     */
    public function test_distress_api_handles_twilio_exception_gracefully(): void
    {
        EventContact::create([
            'event_id' => $this->event->id,
            'name'     => 'Coordinator',
            'phone'    => '0411002233',
        ]);

        $mockSms = Mockery::mock(TwilioSmsService::class);
        $mockSms->shouldReceive('send')
            ->once()
            ->andThrow(new TwilioException('Twilio network error'));

        $this->app->instance(TwilioSmsService::class, $mockSms);

        $response = $this->actingAs($this->user)->postJson('/api/events/distress', [
            'event_id'  => $this->event->id,
            'team_id'   => $this->team->id,
            'angler_id' => $this->angler->id,
            'latitude'  => -33.1000,
            'longitude' => 151.1000,
        ]);

        // API gracefully acknowledges the distress and reports 0 sent, 1 failed
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data'    => [
                    'contacts_notified' => 0,
                    'contacts_failed'   => 1,
                ],
            ]);
    }
}
