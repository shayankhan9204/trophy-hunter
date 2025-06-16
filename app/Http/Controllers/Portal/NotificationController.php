<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $notificationIds = Notification::select(DB::raw('MIN(id) as id'))
                ->groupBy('event_id', 'title', 'message')
                ->pluck('id');

            $notifications = Notification::whereIn('id', $notificationIds)
                ->with('event') // if you want event data
                ->orderByDesc('id')
                ->get();

            return DataTables::of($notifications)
                ->addColumn('event', function ($row) {
                    return $row->event->name ?? '-';
                })
                ->addColumn('notify_date', function ($row) {
                    return optional($row->created_at)->format('Y-m-d') ?? '-';
                })
                ->make(true);
        }

        return view('portal.notification.index');
    }

    public function create()
    {
        $events = Event::get();
        return view('portal.notification.create' , compact('events'));
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'event_id' => 'required|exists:events,id',
                'title' => 'required|string',
                'message' => 'required|string',
            ]);

            DB::beginTransaction();
            $event = Event::with('teams.users')->findOrFail($validated['event_id']);

            foreach ($event->teams as $team) {
                $userId = $team->pivot->user_id;

                if(isset($userId)){
                    // foreach ($team->users as $user) {
                    Notification::create([
                        'user_id' => $userId,
                        'event_id' => $validated['event_id'],
                        'title' => $validated['title'],
                        'message' => $validated['message'],
                        'is_read' => false,
                    ]);
                    // }
                }

            }

            DB::commit();

            return redirect()->route('notification.index')->with('success', 'Notification sent successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Something went wrong: ' . $e->getMessage());
        }
    }

}
