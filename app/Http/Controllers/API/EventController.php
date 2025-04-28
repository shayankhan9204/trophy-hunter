<?php

namespace App\Http\Controllers\API;

use App\Helpers\APIResponse;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Notification;
use Illuminate\Support\Facades\Auth;

class EventController extends Controller
{
    public function myEvents()
    {
        $events = Auth::user()->team
            ? Auth::user()->team->events()
                ->orderByDesc('id')
                ->get()
            : collect();

        return APIResponse::success('Events Fetched Successfully', [
            'events' => $events,
        ]);
    }

    public function eventDetail($id)
    {
        $event = Event::where('id' , $id)
                ->with(['contacts', 'rules', 'media' => function ($query) {
                    $query->where('collection_name', 'sponsors');
                }])
                ->orderByDesc('id')
                ->get()
                ->map(function ($event) {
                    $event->sponsor_images = $event->getSponsorImages();
                    return $event;
                });

        return APIResponse::success('Event Detail Fetched Successfully', [
            'event' => $event,
        ]);
    }
    public function notifications()
    {
        $notification = Notification::where('user_id' , Auth::id())->get();
        return APIResponse::success('Notification Fetched Successfully', [
            'notification' => $notification,
        ]);

    }

}
