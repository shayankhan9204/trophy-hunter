<?php

namespace App\Http\Controllers\Portal;

use App\Exports\EventCatchExport;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventCatch;
use App\Models\EventContact;
use App\Models\EventDate;
use App\Models\Rule;
use App\Models\Specie;
use App\Models\Team;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Log;

class EventController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $events = Event::with('dates')->orderByDesc('id')->get();

            return DataTables::of($events)
                ->addColumn('date', function ($row) {
                    return $row->dates->pluck('date')->map(function ($date) {
                        return Carbon::parse($date)->format('d F Y');
                    })->implode(', ');
                })
                ->addColumn('start_time', function ($row) {
                    return $row->dates->pluck('start_time')->implode(', ');
                })
                ->addColumn('end_time', function ($row) {
                    return $row->dates->pluck('end_time')->implode(', ');
                })
                ->addColumn('action', function ($row) {
                    $editUrl = route('event.edit', ['id' => $row->id]);
                    $editCatchUrl = route('event.edit.catch', ['id' => $row->id]);
                    $deleteUrl = route('event.delete', ['id' => $row->id]);
                    $exportUrl = route('event.export.catch', ['id' => $row->id]); // <-- Export URL

                    $actions = '';
                    $actions .= '<a href="' . $editUrl . '" class="mr-2" data-toggle="tooltip" title="Edit Event">
                        <i class="fas fa-pencil-alt"></i>
                    </a>';

                    $actions .= '<a href="' . $editCatchUrl . '" class="mr-2" data-toggle="tooltip" title="Edit Catch">
                        <i class="fas fa-list"></i>
                    </a>';

                    $actions .= '<form action="' . $deleteUrl . '" method="POST" style="display: inline;" onsubmit="return confirm(\'Are you sure you want to delete this Event?\');">
                        ' . csrf_field() . method_field('DELETE') . '
                        <button type="submit" class="btn btn-link text-danger p-0" data-toggle="tooltip" title="Delete Event">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </form>';

                    $actions .= '<a href="' . $exportUrl . '" class="ml-2" data-toggle="tooltip" title="Export Catch as Excel">
                        <i class="fas fa-file-excel text-success"></i>
                    </a>';
                    return $actions;
                })
                ->rawColumns(['action'])
                ->make(true);
        }
        return view('portal.events.index');
    }

    public function create()
    {
//        $teams = Team::get();
        $species = Specie::get();
        return view('portal.events.create', compact( 'species'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'date' => 'required|array',
            'location' => 'required|string|max:255',
//            'fish_bag_size' => 'required',
            'start_time' => 'required|array',
            'end_time' => 'required|array',
//            'teams' => 'required|array',
//            'teams.*' => 'exists:teams,id',
            'species' => 'required|array',
            'species.*' => 'exists:species,id',

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

//            'sponsors' => 'required|array',
//            'sponsors.*' => 'file|mimes:jpg,jpeg,png,gif,webp',
        ]);

        try {
            DB::beginTransaction();

            $event = Event::create([
                'name' => $request->name,
                'location' => $request->location,
                'fish_bag_size' => $request->fish_bag_size,
                'minimum_release_size' => $request->minimum_release_size,
                'is_tagged' => isset($request->is_tagged) ? $request->is_tagged : 0,
            ]);

//            if ($request->has('teams')) {
//                $event->teams()->sync($request->teams);
//
//            }

            if ($request->has('species')) {
                $event->species()->sync($request->species);

            }

            if ($request->date) {
                foreach ($request->date as $index => $date) {
                    if ($date || $request->start_time[$index] || $request->end_time[$index]) {
                        EventDate::create([
                            'event_id' => $event->id,
                            'date' => $date,
                            'start_time' => $request->start_time[$index],
                            'end_time' => $request->end_time[$index],
                        ]);
                    }
                }
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
//        $teams = Team::get();
        $event = Event::where('id', $id)
            ->with('contacts', 'notifications', 'rules', 'teams', 'dates')->first();
        $species = Specie::get();

        return view('portal.events.edit', compact( 'event', 'species'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'date' => 'required|array',
            'location' => 'required|string',
//            'fish_bag_size' => 'required',
//            'teams' => 'required|array',
            'species' => 'required|array',
            'start_time' => 'required|array',
            'end_time' => 'required|array',
        ]);

        DB::beginTransaction();

        try {

            $event = Event::findOrFail($request->id);

            $event->update([
                'name' => $request->name,
                'date' => $request->date,
                'location' => $request->location,
                'fish_bag_size' => $request->fish_bag_size ?? $event->fish_bag_size,
                'minimum_release_size' => $request->minimum_release_size ?? $event->minimum_release_size,
                'start_time' => $request->start_time,
                'end_time' => $request->end_time,
                'is_tagged' => isset($request->is_tagged) ? $request->is_tagged : 0,
            ]);

//            $event->teams()->sync($request->teams ?? []);
            $event->species()->sync($request->species ?? []);

            if ($request->filled('removed_media_ids')) {
                $ids = explode(',', $request->removed_media_ids);

                foreach ($ids as $id) {
                    $media = $event->media()->where('id', $id)->first();
                    if ($media) {
                        $media->delete();
                    }
                }
            }

            if ($request->hasFile('sponsors')) {
                foreach ($request->file('sponsors') as $file) {
                    $event->addMedia($file)->toMediaCollection('sponsors');
                }
            }

            $event->dates()->delete();

            foreach ($request->date ?? [] as $index => $date) {
                if ($date) {
                    EventDate::create([
                        'event_id' => $event->id,
                        'date' => $date,
                        'start_time' => $request->start_time[$index],
                        'end_time' => $request->end_time[$index],
                    ]);
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
            DB::commit();

            return redirect()->back()->with('success', 'Event updated successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Event update failed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'An error occurred while updating the event.');

        }

    }

    public function destroy($id)
    {
        $event = Event::findOrFail($id);

//        $event->teams()->detach();
        $event->contacts()->delete();
        $event->rules()->delete();
        $event->clearMediaCollection('sponsors');

        $event->delete();

        return redirect()->route('event.index')->with('success', 'Event deleted successfully!');
    }

    public function exportCatch($id)
    {
        $event = Event::find($id);
        return Excel::download(new EventCatchExport($id), 'event-catch-' . $event->name . '.xlsx');
    }

    public function getSpeciesByEvent(Request $request)
    {
        $eventId = $request->event_id;

        if (!$eventId) {
            return response()->json(['species' => []]);
        }

        $event = Event::with('species')->find($eventId);

        if (!$event) {
            return response()->json(['species' => []]);
        }

        return response()->json([
            'species' => $event->species->map(function ($specie) {
                return [
                    'id' => $specie->id,
                    'name' => $specie->name,
                ];
            }),
        ]);
    }

    public function editCatch($id)
    {
        $event = Event::where('id', $id)
            ->with(['catches.specie' , 'catches.team', 'catches.angler' , 'species'])->first();

        return view('portal.events.edit-catch', compact( 'event' ));
    }


    public function updateCatchPoints(Request $request)
    {
        $pointsData = $request->input('points', []);
        $forkData   = $request->input('fork_length', []);

        $catchIds = array_unique(array_merge(
            array_keys($pointsData),
            array_keys($forkData)
        ));

        foreach ($catchIds as $catchId) {
            $catch = EventCatch::find($catchId);
            if (!$catch) {
                continue;
            }

            if (isset($pointsData[$catchId])) {
                $catch->points = $pointsData[$catchId];
            }

            if (isset($forkData[$catchId])) {
                $catch->fork_length = $forkData[$catchId];
            }

            $catch->save();
        }

        return redirect()
            ->back()
            ->with('success', 'Catch points and fork lengths updated successfully.');
    }

    public function deleteSelected(Request $request)
    {
        $ids = $request->catch_ids;

        if (empty($ids)) {
            return response()->json(['message' => 'No catches selected.'], 400);
        }

        EventCatch::whereIn('id', $ids)->delete();

        return response()->json(['message' => 'Selected catches deleted successfully.']);
    }

    /**
     * Page to select an event and delete fish measure photo, glory photo, and/or release video
     * for selected catches. Data is sortable by fish size (fork length).
     */
    public function deleteCatchMediaPage()
    {
        $events = Event::with('dates')->orderByDesc('id')->get();
        return view('portal.events.delete-catch-media', compact('events'));
    }

    /**
     * AJAX: return catches for the given event (for delete-catch-media table).
     */
    public function getCatchesForMediaDelete(Request $request)
    {
        $eventId = $request->event_id;
        if (!$eventId) {
            return response()->json(['catches' => []]);
        }

        $catches = EventCatch::where('event_id', $eventId)
            ->with(['specie', 'team', 'angler'])
            ->orderByRaw('CAST(fork_length AS UNSIGNED) ASC')
            ->get()
            ->map(function ($catch) {
                return [
                    'id' => $catch->id,
                    'team_uid' => $catch->team->team_uid ?? '-',
                    'team_name' => $catch->team->name ?? '-',
                    'angler_name' => $catch->angler->name ?? '-',
                    'specie_name' => $catch->specie->name ?? '-',
                    'fork_length' => $catch->fork_length ?? '-',
                    'fork_length_sort' => is_numeric($catch->fork_length) ? (float) $catch->fork_length : 0,
                    'has_measure_photo' => $catch->getMedia('event_fish_images')->count() > 0,
                    'has_glory_photo' => $catch->getMedia('glory_photos')->count() > 0,
                    'has_release_video' => $catch->getMedia('release_video')->count() > 0,
                ];
            });

        return response()->json(['catches' => $catches]);
    }

    /**
     * Delete selected media types for selected catches.
     */
    public function deleteCatchMedia(Request $request)
    {
        $request->validate([
            'catch_ids' => 'required|array',
            'catch_ids.*' => 'integer|exists:event_catches,id',
            'delete_measure_photo' => 'nullable|boolean',
            'delete_glory_photo' => 'nullable|boolean',
            'delete_release_video' => 'nullable|boolean',
        ]);

        $catchIds = $request->catch_ids;
        $deleteMeasurePhoto = (bool) $request->delete_measure_photo;
        $deleteGloryPhoto = (bool) $request->delete_glory_photo;
        $deleteReleaseVideo = (bool) $request->delete_release_video;

        if (!$deleteMeasurePhoto && !$deleteGloryPhoto && !$deleteReleaseVideo) {
            return response()->json(['message' => 'Please select at least one media type to delete.'], 422);
        }

        $catches = EventCatch::whereIn('id', $catchIds)->get();
        $count = 0;

        foreach ($catches as $catch) {
            if ($deleteMeasurePhoto) {
                $catch->clearMediaCollection('event_fish_images');
                $count++;
            }
            if ($deleteGloryPhoto) {
                $catch->clearMediaCollection('glory_photos');
                $count++;
            }
            if ($deleteReleaseVideo) {
                $catch->clearMediaCollection('release_video');
                $count++;
            }
        }

        return response()->json([
            'message' => 'Selected media deleted successfully.',
            'catches_updated' => $catches->count(),
        ]);
    }
}
