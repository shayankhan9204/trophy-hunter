<?php

namespace App\Http\Controllers\API;

use App\Helpers\APIResponse;
use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EventController extends Controller
{
    public function myEvents()
    {
        $events = Auth::user()->team
            ? Auth::user()->team->events()
                ->with(['notifications', 'contacts', 'rules', 'media' => function ($query) {
                    $query->where('collection_name', 'sponsors');
                }])
                ->orderByDesc('id')
                ->get()
                ->map(function ($event) {
                    $event->sponsor_images = $event->getSponsorImages();
                    return $event;
                })
            : collect();

        return APIResponse::success('Events Fetched Successfully', [
            'events' => $events,
        ]);
    }


}
