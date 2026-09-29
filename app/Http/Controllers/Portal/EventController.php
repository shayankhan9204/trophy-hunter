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
use App\Services\EventGridMapImporter;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

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
            'species_size_validation' => 'nullable|array',
            'species_size_validation.*' => 'nullable|in:1',

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
            'has_grid_map' => 'nullable|in:1',
            'grid_map_csv' => 'nullable|file|mimes:csv,txt',
        ]);

        if ($request->has('has_grid_map') && !$request->hasFile('grid_map_csv')) {
            return back()
                ->withInput()
                ->withErrors(['grid_map_csv' => 'Please upload a CSV file when grid map is enabled.']);
        }

        try {
            DB::beginTransaction();

            $event = Event::create([
                'name' => $request->name,
                'location' => $request->location,
                'fish_bag_size' => $request->fish_bag_size,
                // 'minimum_release_size' => $request->minimum_release_size,
                'is_tagged' => isset($request->is_tagged) ? $request->is_tagged : 0,
                'has_grid_map' => isset($request->has_grid_map) ? 1 : 0,
                'has_im_safe' => isset($request->has_im_safe) ? 1 : 0,
            ]);

//            if ($request->has('teams')) {
//                $event->teams()->sync($request->teams);
//
//            }

            if ($request->has('species')) {
                $syncData = [];
                foreach ($request->species as $specieId) {
                    $syncData[$specieId] = [
                        'is_size_validation_enabled' => isset($request->species_size_validation[$specieId]) ? 1 : 0,
                    ];
                }
                $event->species()->sync($syncData);
            }

            if ($request->date) {
                foreach ($request->date as $index => $date) {
                    if ($date || $request->start_time[$index] || $request->end_time[$index]) {
                        EventDate::create([
                            'event_id' => $event->id,
                            'date' => $date,
                            'start_time' => $request->start_time[$index],
                            'end_time' => $request->end_time[$index],
                            'im_safe_interval' => $request->im_safe_interval[$index] ?? null,
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

            $importResult = null;
            if ($request->has('has_grid_map') && $request->hasFile('grid_map_csv')) {
                $importResult = app(EventGridMapImporter::class)->importFromCsv($event, $request->file('grid_map_csv'));
            }

            DB::commit();

            $successMessage = 'Event created successfully.';
            if ($importResult && !empty($importResult['errors'])) {
                $successMessage .= ' ' . $this->formatGridMapImportErrors($importResult);
            }

            return redirect()->route('event.index')->with('success', $successMessage);
        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'Something went wrong: ' . $e->getMessage());
        }
    }

    public function edit($id)
    {
//        $teams = Team::get();
        $event = Event::where('id', $id)
            ->with('contacts', 'notifications', 'rules', 'teams', 'dates', 'species', 'locationAreas')->first();
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
            'species.*' => 'exists:species,id',
            'species_size_validation' => 'nullable|array',
            'species_size_validation.*' => 'nullable|in:1',
            'start_time' => 'required|array',
            'end_time' => 'required|array',
            'has_grid_map' => 'nullable|in:1',
            'grid_map_csv' => 'nullable|file|mimes:csv,txt',
        ]);

        $event = Event::withCount('locationAreas')->findOrFail($request->id);
        $hasGridMap = isset($request->has_grid_map);

        if ($hasGridMap && !$request->hasFile('grid_map_csv') && $event->location_areas_count === 0) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['grid_map_csv' => 'Please upload a CSV file when grid map is enabled.']);
        }

        DB::beginTransaction();

        try {

            $event->update([
                'name' => $request->name,
                'date' => $request->date,
                'location' => $request->location,
                'fish_bag_size' => $request->fish_bag_size ?? $event->fish_bag_size,
                // 'minimum_release_size' => $request->minimum_release_size ?? $event->minimum_release_size,
                'start_time' => $request->start_time,
                'end_time' => $request->end_time,
                'is_tagged' => isset($request->is_tagged) ? $request->is_tagged : 0,
                'has_grid_map' => $hasGridMap ? 1 : 0,
                'has_im_safe' => isset($request->has_im_safe) ? 1 : 0,
            ]);

//            $event->teams()->sync($request->teams ?? []);
            $syncData = [];
            foreach ($request->species ?? [] as $specieId) {
                $syncData[$specieId] = [
                    'is_size_validation_enabled' => isset($request->species_size_validation[$specieId]) ? 1 : 0,
                ];
            }
            $event->species()->sync($syncData);

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
                        'im_safe_interval' => $request->im_safe_interval[$index] ?? null,
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

            $importResult = null;
            if (!$hasGridMap) {
                $event->locationAreas()->delete();
            } elseif ($request->hasFile('grid_map_csv')) {
                $importResult = app(EventGridMapImporter::class)->importFromCsv($event, $request->file('grid_map_csv'));
            }

            DB::commit();

            $successMessage = 'Event updated successfully!';
            if ($importResult && !empty($importResult['errors'])) {
                $successMessage .= ' ' . $this->formatGridMapImportErrors($importResult);
            }

            return redirect()->back()->with('success', $successMessage);

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
        $event->locationAreas()->delete();
        $event->catches()->delete();
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
                    'minimum_video_size' => $specie->minimum_video_size,
                    'maximum_video_size' => $specie->maximum_video_size,
                    'is_size_validation_enabled' => (int) ($specie->pivot->is_size_validation_enabled ?? 1),
                ];
            }),
        ]);
    }

    public function editCatch($id)
    {
        $event = Event::where('id', $id)
            ->with(['catches.specie', 'catches.team', 'catches.angler', 'species'])->first();

        if (!$event) {
            abort(404);
        }

        // Teams in this event with their anglers (for angler dropdowns and add-catch form)
        $teamsInEvent = Team::whereHas('events', function ($q) use ($id) {
            $q->where('event_id', $id);
        })
            ->with(['anglers' => function ($q) use ($id) {
                $q->wherePivot('event_id', $id);
            }])
            ->get();

        return view('portal.events.edit-catch', compact('event', 'teamsInEvent'));
    }

    /**
     * AJAX: return anglers (users) for a team in an event (for add-catch angler dropdown).
     */
    public function getAnglersForEventTeam(Request $request)
    {
        $eventId = $request->event_id;
        $teamId = $request->team_id;
        if (!$eventId || !$teamId) {
            return response()->json(['anglers' => []]);
        }

        $team = Team::where('id', $teamId)
            ->whereHas('events', function ($q) use ($eventId) {
                $q->where('event_id', $eventId);
            })
            ->with(['anglers' => function ($q) use ($eventId) {
                $q->wherePivot('event_id', $eventId)->orderBy('name');
            }])
            ->first();

        if (!$team) {
            return response()->json(['anglers' => []]);
        }

        $anglers = $team->anglers->map(function ($u) {
            return ['id' => $u->id, 'name' => $u->name];
        });

        return response()->json(['anglers' => $anglers]);
    }

    public function updateCatchPoints(Request $request)
    {
        $pointsData = $request->input('points', []);
        $forkData   = $request->input('fork_length', []);
        $anglerData = $request->input('angler_id', []);
        
        $catchIds = array_unique(array_merge(
            array_keys($pointsData),
            array_keys($forkData),
            array_keys($anglerData)
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

            if (array_key_exists($catchId, $anglerData) && $anglerData[$catchId]) {
                $catch->angler_id = $anglerData[$catchId];
            }

            $catch->save();
        }

        return redirect()
            ->back()
            ->with('success', 'Catch data updated successfully.');
    }

    /**
     * Store a new catch for an event (from edit-catch page).
     */
    public function storeCatch(Request $request)
    {
        $event = Event::findOrFail($request->event_id);

        $rules = [
            'event_id' => 'required|exists:events,id',
            'team_id' => 'required|exists:teams,id',
            'angler_id' => 'required|exists:users,id',
            'specie_id' => 'required|exists:species,id',
            'fork_length' => 'required',
            'points' => 'nullable',
            'catch_timestamp' => 'nullable|string',
        ];

        if ($event->is_tagged) {
            $rules['tag_type'] = 'nullable|string|max:255';
            $rules['tag_no'] = 'nullable|string|max:255';
            $rules['line_class'] = 'nullable|string|max:255';
        }

        $request->validate($rules);

        // Ensure angler is in this team for this event
        $exists = DB::table('event_team_user')
            ->where('event_id', $request->event_id)
            ->where('team_id', $request->team_id)
            ->where('user_id', $request->angler_id)
            ->whereNull('deleted_at')
            ->exists();

        if (!$exists) {
            return redirect()->back()->with('error', 'Selected angler is not in the selected team for this event.');
        }

        EventCatch::create([
            'event_id' => $request->event_id,
            'team_id' => $request->team_id,
            'angler_id' => $request->angler_id,
            'specie_id' => $request->specie_id,
            'fork_length' => $request->fork_length,
            'tag_type' => $event->is_tagged ? ($request->tag_type ?? '') : null,
            'tag_no' => $event->is_tagged ? ($request->tag_no ?? '') : null,
            'line_class' => $event->is_tagged ? ($request->line_class ?? '') : null,
            'points' => $request->points ?? null,
            'catch_timestamp' => $request->catch_timestamp ?? now()->toDateTimeString(),
        ]);

        return redirect()
            ->back()
            ->with('success', 'Catch record created successfully.');
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

    public function importCatchData(Request $request)
    {
        $request->validate([
            'event_id' => 'required|exists:events,id',
            'catch_file' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        if (!$request->hasFile('catch_file')) {
            return redirect()->back()->with('error', 'Please upload a valid xlsx/xls/csv file.');
        }

        $event = Event::with('species')->findOrFail($request->event_id);

        if ($event->species->isEmpty()) {
            return redirect()->back()->with('error', 'No species are attached to this event. Please attach at least one species first.');
        }

        $normalize = function ($value): string {
            return Str::of((string) $value)
                ->lower()
                ->replaceMatches('/[^a-z0-9]+/', '_')
                ->trim('_')
                ->value();
        };

        $toNumeric = function ($value) {
            if ($value === null || $value === '') {
                return null;
            }

            $sanitized = preg_replace('/[^0-9.\-]/', '', (string) $value);
            if ($sanitized === '' || !is_numeric($sanitized)) {
                return null;
            }

            return (float) $sanitized;
        };
        
        $uploadedFile = $request->file('catch_file');
        if (!$uploadedFile || !$uploadedFile->isValid()) {
            return redirect()->back()->with('error', 'Invalid uploaded file. Please choose the file again.');
        }

        $tmpImportFile = null;
        try {
            $sourcePath = $uploadedFile->getPathname();
            if (empty($sourcePath) || !file_exists($sourcePath)) {
                return redirect()->back()->with('error', 'Uploaded temp file is not accessible.');
            }

            $tmpDir = storage_path('app/tmp-imports');
            if (!is_dir($tmpDir)) {
                mkdir($tmpDir, 0777, true);
            }

            $ext = strtolower($uploadedFile->getClientOriginalExtension() ?: 'xlsx');
            $tmpImportFile = $tmpDir . DIRECTORY_SEPARATOR . Str::uuid() . '.' . $ext;

            if (!copy($sourcePath, $tmpImportFile)) {
                return redirect()->back()->with('error', 'Unable to copy uploaded file for import.');
            }
        } catch (\Exception $exception) {
            return redirect()->back()->with('error', 'Unable to prepare uploaded file: ' . $exception->getMessage());
        }

        $importSource = $tmpImportFile;
        if (empty($importSource) || !file_exists($importSource)) {
            if ($tmpImportFile && file_exists($tmpImportFile)) {
                @unlink($tmpImportFile);
            }
            return redirect()->back()->with('error', 'Unable to prepare uploaded file for import. Please try again.');
        }

        try {
            $sheets = Excel::toArray([], $importSource);
        } finally {
            if ($tmpImportFile && file_exists($tmpImportFile)) {
                @unlink($tmpImportFile);
            }
        }
        $rows = $sheets[0] ?? [];

        if (count($rows) < 2) {
            return redirect()->back()->with('error', 'The uploaded file is empty or does not contain data rows.');
        }

        $headers = array_map($normalize, $rows[0]);
        $headerMap = array_flip($headers);

        $findHeader = function (array $candidates) use ($headerMap) {
            foreach ($candidates as $candidate) {
                if (array_key_exists($candidate, $headerMap)) {
                    return $headerMap[$candidate];
                }
            }
            return null;
        };

        $teamIndex = $findHeader(['team_name', 'team', 'teamname','Team Name']);
        $anglerIndex = $findHeader(['angler_name', 'angler', 'anglername' , 'Angler Name']);
        $forkLengthIndex = $findHeader(['fork_length_x_x', 'fork_length', 'forklength', 'fork_length_mm' , 'Fork Length']);
        $pointsIndex = $findHeader(['points', 'point', 'Points']);
        $speciesIndex = $findHeader(['species', 'specie', 'species_name', 'specie_name']);
        $catchTimeIndex = $findHeader(['catch_time', 'catch_timestamp', 'timestamp', 'date_time', 'datetime']);

        if ($teamIndex === null || $anglerIndex === null || $forkLengthIndex === null) {
            return redirect()->back()->with(
                'error',
                'Missing required columns. Required: Team Name, Angler Name, Fork Length. Optional: Points, Species, Catch Time.'
            );
        }

        $teamsInEvent = Team::whereHas('events', function ($q) use ($event) {
            $q->where('event_id', $event->id);
        })->get();

        $teamMap = [];
        foreach ($teamsInEvent as $team) {
            $teamMap[$normalize($team->name)] = $team;
        }

        $anglerRows = DB::table('event_team_user')
            ->join('users', 'event_team_user.user_id', '=', 'users.id')
            ->where('event_team_user.event_id', $event->id)
            ->whereNull('event_team_user.deleted_at')
            ->select('event_team_user.team_id', 'users.id as user_id', 'users.name as user_name')
            ->get();

        $anglerMap = [];
        foreach ($anglerRows as $anglerRow) {
            $anglerMap[$anglerRow->team_id][$normalize($anglerRow->user_name)] = $anglerRow->user_id;
        }

        $eventSpeciesMap = [];
        foreach ($event->species as $specie) {
            $eventSpeciesMap[$normalize($specie->name)] = $specie->id;
        }
        $defaultSpecieId = $event->species->first()->id;

        $imported = 0;
        $skipped = 0;
        $errors = [];

        DB::beginTransaction();
        try {
            foreach (array_slice($rows, 1) as $line => $row) {
                $rowNumber = $line + 2; // +1 for zero-index and +1 for header

                $teamName = trim((string) ($row[$teamIndex] ?? ''));
                $anglerName = trim((string) ($row[$anglerIndex] ?? ''));
                $forkLengthRaw = $row[$forkLengthIndex] ?? null;
                $pointsRaw = $pointsIndex !== null ? ($row[$pointsIndex] ?? null) : null;
                $speciesRaw = $speciesIndex !== null ? trim((string) ($row[$speciesIndex] ?? '')) : '';
                $catchTimeRaw = $catchTimeIndex !== null ? trim((string) ($row[$catchTimeIndex] ?? '')) : '';

                if ($teamName === '' && $anglerName === '' && ($forkLengthRaw === null || $forkLengthRaw === '')) {
                    continue;
                }

                $team = $teamMap[$normalize($teamName)] ?? null;
                if (!$team) {
                    $skipped++;
                    $errors[] = "Row {$rowNumber}: Team '{$teamName}' is not attached to this event.";
                    continue;
                }

                $anglerId = $anglerMap[$team->id][$normalize($anglerName)] ?? null;
                if (!$anglerId) {
                    $skipped++;
                    $errors[] = "Row {$rowNumber}: Angler '{$anglerName}' not found in team '{$team->name}' for this event.";
                    continue;
                }

                $forkLength = $toNumeric($forkLengthRaw);
                if ($forkLength === null) {
                    $skipped++;
                    $errors[] = "Row {$rowNumber}: Invalid fork length value.";
                    continue;
                }

                $points = $toNumeric($pointsRaw);

                $specieId = $defaultSpecieId;
                if ($speciesRaw !== '') {
                    $specieId = $eventSpeciesMap[$normalize($speciesRaw)] ?? null;
                    if (!$specieId) {
                        $skipped++;
                        $errors[] = "Row {$rowNumber}: Species '{$speciesRaw}' is not available in this event.";
                        continue;
                    }
                }

                EventCatch::create([
                    'event_id' => $event->id,
                    'team_id' => $team->id,
                    'angler_id' => $anglerId,
                    'specie_id' => $specieId,
                    'fork_length' => $forkLength,
                    'points' => $points,
                    'catch_timestamp' => $catchTimeRaw !== '' ? $catchTimeRaw : now()->toDateTimeString(),
                ]);

                $imported++;
            }

            DB::commit();
        } catch (\Exception $exception) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Import failed: ' . $exception->getMessage());
        }

        if (!empty($errors)) {
            $previewErrors = implode(' | ', array_slice($errors, 0, 5));
            if (count($errors) > 5) {
                $previewErrors .= ' | ...';
            }

            return redirect()->back()->with(
                'success',
                "Import completed. Imported {$imported} row(s), skipped {$skipped} row(s). {$previewErrors}"
            );
        }

        return redirect()->back()->with('success', "Import completed. Imported {$imported} row(s), skipped {$skipped} row(s).");
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

    private function formatGridMapImportErrors(array $importResult): string
    {
        $errors = $importResult['errors'] ?? [];
        $preview = implode(' | ', array_slice($errors, 0, 5));

        if (count($errors) > 5) {
            $preview .= ' | ...';
        }

        return "Grid map import: imported {$importResult['imported']} row(s). {$preview}";
    }
}
