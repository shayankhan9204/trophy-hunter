<?php

namespace App\Http\Controllers\API;

use App\Helpers\APIResponse;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventAttendance;
use App\Models\EventSafetyCheck;
use App\Models\EventCatch;
use App\Models\Notification;
use App\Models\Specie;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class EventController extends Controller
{
    public function myEvents()
    {
        $user = Auth::user();

        $eventTeamUsers = \DB::table('event_team_user')
            ->where('user_id', $user->id)
            ->whereNull('deleted_at')
            ->get();

        $eventIds = $eventTeamUsers->pluck('event_id')->unique();

        $events = Event::with('dates')
            ->whereIn('id', $eventIds)
            ->orderByDesc('id')
            ->get();

        foreach ($events as $event) {
            $etu = $eventTeamUsers->firstWhere('event_id', $event->id);
            $event->share_contact_data = $etu ? (bool)$etu->share_contact_data : false;
        }

        return APIResponse::success('Events Fetched Successfully', [
            'events' => $events,
        ]);
    }

    public function eventDetail($id)
    {
        if (empty($id)) {
            return APIResponse::error('Event ID is required');
        }

        $event = Event::where('id', $id)->with(['contacts', 'rules', 'dates', 'species', 'locationAreas'])->first();

        // $userTeamIds = Auth::user()->team->pluck('id')->toArray();
        $team = $event->teams()
            // ->whereIn('team_id', $userTeamIds)
            ->wherePivot('user_id', Auth::id())
            ->first();

        if (!$team) {
            return APIResponse::error('You are not registered in this event');
        }

        $event->share_contact_data = isset($team->pivot->share_contact_data) ? (bool)$team->pivot->share_contact_data : false;

        $event->sponsor_images = $event->getSponsorImages();
        $eventCatches = EventCatch::with(['angler', 'specie'])
            ->where('event_id', $id)
            ->where('team_id', $team->id)
            ->get();

        $anglerAngularUids = DB::table('event_team_user')
            ->where('event_id', $id)
            ->where('team_id', $team->id)
            ->pluck('angular_uid', 'user_id');

        foreach ($eventCatches as $catch) {
            $catch->angler->angular_uid = $anglerAngularUids[$catch->angler_id] ?? null;
        }

        $event->event_catches = $eventCatches;

        return APIResponse::success('Event Detail Fetched Successfully', [
            'event' => $event,
        ]);
    }

    public function notifications($id = null)
    {
        $query = Notification::where('user_id', Auth::id());

        if ($id) {
            $query->where('event_id', $id);
        }

        $notifications = $query->orderByDesc('created_at')->get();

        return APIResponse::success('Notification Fetched Successfully', [
            'notification' => $notifications,
        ]);
    }


    public function species($id = null)
    {
        if (empty($id)) {
            return APIResponse::error('Event ID is required');
        }

        $event = Event::find($id);
        if (!$event) {
            return APIResponse::error('Event not found');
        }

        $species = $event->species()->get();

        return APIResponse::success('Species fetched successfully', [
            'species' => $species,
        ]);
    }

    public function submitBag(Request $request, $event_id = null)
    {
        DB::beginTransaction();

        try {
            $request->validate([
                'fish_bag'      => 'nullable|array',
                'safety_checks' => 'nullable|array',
            ]);

            $event = Event::findOrFail($event_id);

            // ── Fish Bag ─────────────────────────────────────────────────────
            if (!empty($request->fish_bag)) {
                $rules = [
                    'fish_bag.*.angler_id'  => 'required',
                    'fish_bag.*.points'     => 'required',
                    'fish_bag.*.specie_id'  => 'required|exists:species,id',
                    'fish_bag.*.fork_length' => 'required|numeric',
//                'fish_bag.*.specie_image' => 'array|min:1',
                ];

                if ($event->tagged == 1) {
                    $rules['fish_bag.*.tag_type']  = 'required|string';
                    $rules['fish_bag.*.tag_no']    = 'required|string';
                    $rules['fish_bag.*.line_class'] = 'required|string';
                } else {
                    $rules['fish_bag.*.tag_type']  = 'nullable|string';
                    $rules['fish_bag.*.tag_no']    = 'nullable|string';
                    $rules['fish_bag.*.line_class'] = 'nullable|string';
                }

                $request->validate($rules);

                foreach ($request->fish_bag as $item) {
                    $exists = EventCatch::where('event_id', $event->id)
                        ->where('team_id', $item['team_id'] ?? null)
                        ->where('angler_id', $item['angler_id'])
                        ->where('catch_timestamp', $item['created_at'] ?? null)
                        ->exists();

                    if ($exists) {
                        continue; // skip duplicate
                    }

                    $eventCatch = EventCatch::create([
                        'event_id'        => $event->id,
                        'team_id'         => $item['team_id'] ?? null,
                        'angler_id'       => $item['angler_id'],
                        'specie_id'       => $item['specie_id'],
                        'fork_length'     => $item['fork_length'],
                        'tag_type'        => $item['tag_type'] ?? null,
                        'tag_no'          => $item['tag_no'] ?? null,
                        'line_class'      => $item['line_class'] ?? null,
                        'points'          => $item['points'] ?? null,
                        'catch_timestamp' => $item['created_at'] ?? null,
                    ]);

                    if (isset($item['specie_image']) && is_array($item['specie_image'])) {
                        foreach ($item['specie_image'] as $image) {
                            // $eventCatch->addMedia($image)->toMediaCollection('event_fish_images');
                            Media::create([
                                'model_type'             => EventCatch::class,
                                'model_id'               => $eventCatch->id,
                                'collection_name'        => 'event_fish_images',
                                'name'                   => 'fish-image',
                                'file_name'              => basename($image),
                                'disk'                   => 'public',
                                'size'                   => 0,
                                'custom_properties'      => ['url' => $image],
                                'manipulations'          => [],
                                'generated_conversions'  => [],
                                'responsive_images'      => [],
                            ]);
                        }
                    }

                    if (isset($item['glory_photos']) && is_array($item['glory_photos'])) {
                        foreach ($item['glory_photos'] as $image) {
                            // $eventCatch->addMedia($image)->toMediaCollection('glory_photos');
                            Media::create([
                                'model_type'             => EventCatch::class,
                                'model_id'               => $eventCatch->id,
                                'collection_name'        => 'glory_photos',
                                'name'                   => 'fish-image',
                                'file_name'              => basename($image),
                                'disk'                   => 'public',
                                'size'                   => 0,
                                'custom_properties'      => ['url' => $image],
                                'manipulations'          => [],
                                'generated_conversions'  => [],
                                'responsive_images'      => [],
                            ]);
                        }
                    }

                    if (isset($item['release_video'])) {
                        // $eventCatch->addMedia($item['release_video'])->toMediaCollection('release_video');
                        Media::create([
                            'model_type'             => EventCatch::class,
                            'model_id'               => $eventCatch->id,
                            'collection_name'        => 'release_video',
                            'name'                   => 'fish-image',
                            'file_name'              => basename($item['release_video']),
                            'disk'                   => 'public',
                            'size'                   => 0,
                            'custom_properties'      => ['url' => $item['release_video']],
                            'manipulations'          => [],
                            'generated_conversions'  => [],
                            'responsive_images'      => [],
                        ]);
                    }
                }
            }

            // ── Safety Checks ────────────────────────────────────────────────
            if (!empty($request->safety_checks)) {
                $request->validate([
                    'safety_checks.*.location_code' => 'required|string',
                    'safety_checks.*.latitude'      => 'required',
                    'safety_checks.*.longitude'     => 'required',
                    'safety_checks.*.time_stamp'    => 'required',
                    'safety_checks.*.team_id'       => 'required',
                    'safety_checks.*.angler_id'     => 'required',
                ]);

                foreach ($request->safety_checks as $check) {
                    // Skip duplicate: same angler already checked in at this timestamp
                    $alreadyExists = EventSafetyCheck::where('event_id', $event->id)
                        ->where('angler_id', $check['angler_id'])
                        ->where('time_stamp', $check['time_stamp'])
                        ->exists();

                    if ($alreadyExists) {
                        continue;
                    }

                    // Resolve the location area from the event's grid map
                    $locationArea = $event->locationAreas()
                        ->where('location_reference', $check['location_code'])
                        ->first();

                    // Skip silently if location code is invalid (don't fail the whole sync)
                    if (!$locationArea) {
                        continue;
                    }

                    EventSafetyCheck::create([
                        'event_id'               => $event->id,
                        'team_id'                => $check['team_id'],
                        'angler_id'              => $check['angler_id'],
                        'event_location_area_id' => $locationArea->id,
                        'location_code'          => $check['location_code'],
                        'latitude'               => $check['latitude'],
                        'longitude'              => $check['longitude'],
                        'time_stamp'             => $check['time_stamp'],
                    ]);
                }
            }

            DB::commit();

            return APIResponse::success('Bag Submitted Successfully');

        } catch (\Exception $exception) {
            DB::rollBack();

            return APIResponse::error($exception->getMessage());
        }
    }

    public function submitAttendance(Request $request, $event_id = null)
    {
        try {
            $request->validate([
                'attendance' => 'required|array|min:1',
            ]);

            $event = Event::findOrFail($event_id);

            foreach ($request->attendance as $item) {
                $existingAttendance = EventAttendance::where('user_id', $item['user_id'])
                    ->where('team_id', $item['team_id'])
                    ->where('event_id', $event->id)
                    ->where('date', $item['date'])
                    ->whereNull('time_out')
                    ->orderByDesc('id')
                    ->first();

                if ($existingAttendance && !empty($item['timeOut'])) {
                    $existingAttendance->update([
                        'time_out' => $item['timeOut'],
                        'time_out_latitude' => $item['latitude'] ?? $existingAttendance->time_in_latitude,
                        'time_out_longitude' => $item['longitude'] ?? $existingAttendance->time_in_longitude,
                    ]);
                } else {
                    EventAttendance::create([
                        'user_id' => $item['user_id'],
                        'team_id' => $item['team_id'],
                        'event_id' => $event->id,
                        'date' => $item['date'],
                        'time_in' => $item['timeIn'] ?? null,
                        'time_out' => $item['timeOut'] ?? null,
                        'time_in_latitude' => $item['latitude'],
                        'time_in_longitude' => $item['longitude'],
                    ]);
                }
            }

            return APIResponse::success('Attendance Mark Successfully');

        } catch (\Exception $exception) {
            return APIResponse::error($exception->getMessage());
        }
    }

    public function submitSafetyCheck(Request $request, $event_id = null)
    {
        try {
            $request->validate([
                'event_id'      => 'required|integer|exists:events,id',
                'location_code' => 'required|string',
                'latitude'      => 'required',
                'longitude'     => 'required',
                'time_stamp'    => 'required',
                'team_id'       => 'required',
                'angler_id'     => 'required',
            ]);

            $event = Event::findOrFail($request->event_id);

            // Resolve the location area from the event's grid map
            $locationArea = $event->locationAreas()
                ->where('location_reference', $request->location_code)
                ->first();

            if (!$locationArea) {
                return APIResponse::error(
                    'The provided location_code does not match any location in this event grid map.'
                );
            }

            $safetyCheck = EventSafetyCheck::create([
                'event_id'               => $request->event_id,
                'team_id'                => $request->team_id,
                'angler_id'              => $request->angler_id,
                'event_location_area_id' => $locationArea->id,
                'location_code'          => $request->location_code,
                'latitude'               => $request->latitude,
                'longitude'              => $request->longitude,
                'time_stamp'             => $request->time_stamp,
            ]);

            return APIResponse::success('Safety check submitted successfully', [
                'safety_check' => $safetyCheck,
            ]);

        } catch (\Exception $exception) {
            return APIResponse::error($exception->getMessage());
        }
    }

}
