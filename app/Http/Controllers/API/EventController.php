<?php

namespace App\Http\Controllers\API;

use App\Helpers\APIResponse;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventAttendance;
use App\Models\EventCatch;
use App\Models\Notification;
use App\Models\Specie;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EventController extends Controller
{
    public function myEvents()
    {
        $user = Auth::user();

        $eventIds = \DB::table('event_team_user')
            ->where('user_id', $user->id)
            ->distinct()
            ->pluck('event_id');

        $events = Event::with('dates')
            ->whereIn('id', $eventIds)
            ->orderByDesc('id')
            ->get();

        return APIResponse::success('Events Fetched Successfully', [
            'events' => $events,
        ]);
    }

    public function eventDetail($id)
    {
        if (empty($id)) {
            return APIResponse::error('Event ID is required');
        }

        $event = Event::where('id', $id)->with(['contacts', 'rules', 'dates', 'species'])->first();

        // $userTeamIds = Auth::user()->team->pluck('id')->toArray();
        $team = $event->teams()
            // ->whereIn('team_id', $userTeamIds)
            ->wherePivot('user_id', Auth::id())
            ->first();

        if (!$team) {
            return APIResponse::error('You are not registered in this event');
        }

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
        try {
            $request->validate([
                'fish_bag' => 'required|array|min:1',
            ]);

            $event = Event::findOrFail($event_id);

            $rules = [
                'fish_bag.*.angler_id' => 'required',
                'fish_bag.*.points' => 'required',
                'fish_bag.*.specie_id' => 'required|exists:species,id',
                'fish_bag.*.fork_length' => 'required|numeric',
//                'fish_bag.*.specie_image' => 'array|min:1',
            ];

            if ($event->tagged == 1) {
                $rules['fish_bag.*.tag_type'] = 'required|string';
                $rules['fish_bag.*.tag_no'] = 'required|string';
                $rules['fish_bag.*.line_class'] = 'required|string';

            } else {
                $rules['fish_bag.*.tag_type'] = 'nullable|string';
                $rules['fish_bag.*.tag_no'] = 'nullable|string';
                $rules['fish_bag.*.line_class'] = 'nullable|string';
            }

            $validated = $request->validate($rules);

            foreach ($request->fish_bag as $item) {
                $angler = User::find($item['angler_id']);

                $eventCatch = EventCatch::create([
                    'event_id' => $event->id,
                    'team_id' => $item['team_id'] ?? null,
                    'angler_id' => $item['angler_id'],
                    'specie_id' => $item['specie_id'],
                    'fork_length' => $item['fork_length'],
                    'tag_type' => $item['tag_type'] ?? null,
                    'tag_no' => $item['tag_no'] ?? null,
                    'line_class' => $item['line_class'] ?? null,
                    'points' => $item['points'] ?? null,
                    'catch_timestamp' => $item['created_at'] ?? null
                ]);

                if (isset($item['specie_image']) && is_array($item['specie_image'])) {
                    foreach ($item['specie_image'] as $image) {
                        $eventCatch->addMedia($image)->toMediaCollection('event_fish_images');
                    }
                }
                if (isset($item['glory_photos']) && is_array($item['glory_photos'])) {
                    foreach ($item['glory_photos'] as $image) {
                        $eventCatch->addMedia($image)->toMediaCollection('glory_photos');
                    }
                }
                if (isset($item['release_video'])) {
                    $eventCatch->addMedia($item['release_video'])->toMediaCollection('release_video');
                }

            }

            return APIResponse::success('Bag Submitted Successfully');

        } catch (\Exception $exception) {
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

}
