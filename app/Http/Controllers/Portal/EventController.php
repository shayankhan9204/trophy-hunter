<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventContact;
use App\Models\Rule;
use App\Models\Team;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class EventController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $events = Event::orderByDesc('id')->get();

            return DataTables::of($events)
                ->addColumn('date', function ($row) {
                    return $row->date ? Carbon::parse($row->date)->format('d F Y') : '';
                })
                ->addColumn('action', function ($row) {
                    $editUrl = route('event.edit', ['id' => $row->id]);
                    $deleteUrl = route('event.delete', ['id' => $row->id]);
                    $actions = '';
                    $actions .= '<a href="' . $editUrl . '" class="mr-2" data-toggle="tooltip" title="Edit Event">
                        <i class="fas fa-pencil-alt"></i>
                    </a>';
                    $actions .= '<form action="' . $deleteUrl . '" method="POST" style="display: inline;" onsubmit="return confirm(\'Are you sure you want to delete this Event?\');">
                        ' . csrf_field() . method_field('DELETE') . '
                        <button type="submit" class="btn btn-link text-danger p-0" data-toggle="tooltip" title="Delete Event">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </form>';
                    return $actions;
                })->rawColumns(['action'])
                ->make(true);
        }
        return view('portal.events.index');
    }

    public function create()
    {
        $teams = Team::get();
        return view('portal.events.create', compact('teams'));
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'date' => 'required|date',
                'location' => 'required|string|max:255',
                'start_time' => 'required',
                'end_time' => 'required',
                'teams' => 'nullable|array',
                'teams.*' => 'exists:teams,id',

                'contact_name' => 'nullable|array',
                'contact_name.*' => 'nullable|string|max:255',
                'contact_email' => 'nullable|array',
                'contact_email.*' => 'nullable|email',
                'contact_phone' => 'nullable|array',
                'contact_phone.*' => 'nullable|string',

                'event_title' => 'nullable|array',
                'event_title.*' => 'nullable|string|max:255',
                'description' => 'nullable|array',
                'description.*' => 'nullable|string',

                'sponsors' => 'nullable|array',
                'sponsors.*' => 'file|mimes:jpg,jpeg,png,gif,webp',
            ]);

            DB::beginTransaction();

            $event = Event::create([
                'name' => $request->name,
                'date' => $request->date,
                'location' => $request->location,
                'start_time' => $request->start_time,
                'end_time' => $request->end_time,
            ]);

            if ($request->has('teams')) {
                $event->teams()->sync($request->teams);

            }

            if ($request->contact_name) {
                foreach ($request->contact_name as $index => $name) {
                    if ($name || $request->contact_email[$index] || $request->contact_phone[$index]) {
                        EventContact::create([
                            'event_id' => $event->id,
                            'name' => $name,
                            'email' => $request->contact_email[$index] ?? null,
                            'phone' => $request->contact_phone[$index] ?? null,
                        ]);
                    }
                }
            }

            if ($request->event_title) {
                foreach ($request->event_title as $index => $title) {
                    if ($title || $request->description[$index]) {
                        Rule::create([
                            'event_id' => $event->id,
                            'title' => $title,
                            'description' => json_encode($request->description[$index]) ?? null,
                        ]);
                    }
                }
            }

            if ($request->hasFile('sponsors')) {
                foreach ($request->file('sponsors') as $file) {
                    $event->addMedia($file)
                        ->usingName(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) // Use filename as sponsor name
                        ->toMediaCollection('sponsors');
                }
            }

            DB::commit();

            return redirect()->route('event.index')->with('success', 'Event created successfully.');
        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'Something went wrong: ' . $e->getMessage());
        }
    }

    public function edit($id)
    {
        $teams = Team::get();
        $event = Event::where('id', $id)
            ->with('contacts', 'notifications', 'rules', 'teams')->first();

        return view('portal.events.edit', compact('teams', 'event'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'date' => 'required|date',
            'location' => 'required|string',
            'teams' => 'nullable|array',
            'start_time' => 'required',
            'end_time' => 'required',
        ]);

        $event = Event::findOrFail($request->id);

        $event->update([
            'name' => $request->name,
            'date' => $request->date,
            'location' => $request->location,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
        ]);

        $event->teams()->sync($request->teams ?? []);

        if ($request->hasFile('sponsors')) {
            $event->clearMediaCollection('sponsors');
            foreach ($request->file('sponsors') as $file) {
                $event->addMedia($file)->toMediaCollection('sponsors');
            }
        }

        $event->contacts()->delete();

        foreach ($request->contact_name ?? [] as $index => $name) {
            if ($name) {
                EventContact::create([
                    'event_id' => $event->id,
                    'name' => $name,
                    'email' => $request->contact_email[$index] ?? null,
                    'phone' => $request->contact_phone[$index] ?? null,
                ]);
            }
        }

        $event->rules()->delete();

        foreach ($request->title ?? [] as $index => $title) {
            if ($title || !empty($request->description[$index])) {
                Rule::create([
                    'event_id' => $event->id,
                    'title' => $title,
                    'description' => json_encode($request->description[$index]),
                ]);
            }
        }

        return redirect()->back()->with('success', 'Event updated successfully!');
    }

    public function destroy($id)
    {
        $event = Event::findOrFail($id);

        $event->teams()->detach();
        $event->contacts()->delete();
        $event->rules()->delete();
        $event->clearMediaCollection('sponsors');

        $event->delete();

        return redirect()->route('event.index')->with('success', 'Event deleted successfully!');
    }

}
