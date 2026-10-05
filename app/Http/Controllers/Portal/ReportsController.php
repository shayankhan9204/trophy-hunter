<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventAttendance;
use App\Models\EventDate;
use App\Models\Specie;
use App\Models\Team;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;

class ReportsController extends Controller
{
    public function teamRankingReport(Request $request)
    {
        $events = Event::all();

        if ($request->ajax()) {
            $eventId = $request->get('event_id');
            $event = Event::where('id', $eventId)->first();

            $catches = EventCatch::with(['angler', 'team', 'specie'])
                ->where('event_id', $eventId)
                ->orderByDesc('fork_length')
                ->get();

            $fishBagSize = $event->fish_bag_size;

            $teamPoints = $catches
                ->groupBy('team_id')
                ->map(function ($group) use ($fishBagSize) {
                    $filtered = $fishBagSize ? $group->take($fishBagSize) : $group;
                    return $filtered->sum('points');
                });

            $sortedTeamIds = $teamPoints->sortDesc()->keys()->values();
            $teamRanks = $sortedTeamIds->flip()->map(fn($index) => $index + 1);

            $finalRows = collect();

            foreach ($sortedTeamIds as $teamId) {
                $teamCatches = $catches->where('team_id', $teamId);
                if (!empty($event->fish_bag_size)) {
                    $teamCatches = $teamCatches->take($event->fish_bag_size);
                }

                $rank = $teamRanks[$teamId] ?? 'N/A';
                $teamNumber = optional($teamCatches->first()->team)->team_uid ?? 'N/A';
                $teamName = optional($teamCatches->first()->team)->name ?? 'N/A';

//                $anglerGrouped = $teamCatches
//                    ->groupBy('angler_id')
//                    ->sortByDesc(fn ($g) => $g->max('fork_length'));

                $totalPoints = 0;

                foreach ($teamCatches as $index => $catch) {
                    $etu = DB::table('event_team_user')
                        ->where('event_id', $eventId)
                        ->where('team_id', $teamId)
                        ->where('user_id', $catch->angler_id)
                        ->first();

                    $photoUrl = $catch->getFirstMediaUrl('event_fish_images');

                    $fishPhoto = $photoUrl
                        ? '<a href="' . e($photoUrl) . '" class="glightbox" data-gallery="team-' . $teamId . '">'
                        . '<img src="' . e($photoUrl) . '" class="img-thumbnail" '
                        . 'style="width:200px;height:130px;object-fit:contain;cursor:pointer;" />'
                        . '</a>'
                        : 'No Photo';

                    $videoUrl = $catch->getFirstMediaUrl('release_video');

                   $releaseVideo = $videoUrl
                    ? '<a href="' . e($videoUrl) . '" class="glightbox">
                        View Video
                    </a>'
                    : 'No Video';

                    $finalRows->push([
                        'rank' => $rank,
                        'team_id' => $teamId,
                        'team_number' => $teamNumber,
                        'team_name' => $teamName,
                        'angler_number' => $etu->angular_uid ?? 'N/A',
                        'angler_name' => $etu->angular_name ?? $catch->angler->name ?? 'N/A',
                        'specie' => $catch->specie->name,
                        'fork_length' => $catch->fork_length,
                        'points' => $catch->points,
                        'fish_photo' => $fishPhoto,
                        'release_video' => $releaseVideo, 
                        'is_summary_row' => false,
                    ]);

                    $totalPoints += $catch->points;

                }

                $finalRows->push([
                    'rank' => '',
                    'team_id' => $teamId,
                    'team_number' => '<strong>' . $teamNumber . '</strong>',
                    'team_name' => '<strong>' . $teamName . '</strong>',
                    'angler_number' => '',
                    'angler_name' => '',
                    'specie' => '',
                    'fork_length' => '<strong>Total Points</strong>',
                    'points' => '<strong>' . $totalPoints . '</strong>',
                    'fish_photo' => '',
                    'release_video' => '',
                    'is_summary_row' => true,
                ]);
            }

            return DataTables::of($finalRows)
                ->rawColumns(['angler_name', 'team_number', 'team_name', 'points', 'fork_length', 'fish_photo'])
                ->make(true);
        }

        return view('portal.reports.ranking-report', compact('events'));
    }

    public function customTeamRankingReport(Request $request)
    {
        $events = Event::all();

        if ($request->ajax()) {
            $eventId = $request->get('event_id');
            $speciesIds = array_map('intval', array_filter((array) $request->get('species', [])));
            $speciesFishCounts = collect((array) $request->get('species_fish_counts', []))
                ->mapWithKeys(fn ($count, $id) => [(int) $id => (int) $count])
                ->all();

            if (!$eventId || empty($speciesIds)) {
                return DataTables::of(collect())->make(true);
            }

            $catches = EventCatch::with(['angler', 'team', 'specie'])
                ->where('event_id', $eventId)
                ->whereIn('specie_id', $speciesIds)
                ->get();

            $getIncludedCatches = function (Collection $teamCatches) use ($speciesIds, $speciesFishCounts) {
                $included = collect();

                foreach ($speciesIds as $specieId) {
                    $limit = (int) ($speciesFishCounts[$specieId] ?? 0);
                    if ($limit <= 0) {
                        continue;
                    }

                    $specieCatches = $teamCatches
                        ->where('specie_id', (int) $specieId)
                        ->sortByDesc('fork_length')
                        ->values()
                        ->take($limit);

                    $included = $included->merge($specieCatches);
                }

                return $included->sortByDesc('fork_length')->values();
            };

            $teamPoints = $catches
                ->groupBy('team_id')
                ->map(fn ($teamCatches) => (float) $getIncludedCatches($teamCatches)->sum('points'));

            $sortedTeamIds = $teamPoints->sort(function ($pointsA, $pointsB) {
                $pointsCmp = $pointsB <=> $pointsA;
                if ($pointsCmp !== 0) {
                    return $pointsCmp;
                }

                return 0;
            })->keys()->values();

            $teamRanks = $sortedTeamIds->flip()->map(fn ($index) => $index + 1);
            $finalRows = collect();

            foreach ($sortedTeamIds as $teamId) {
                $teamCatches = $catches->where('team_id', $teamId);
                $includedCatches = $getIncludedCatches($teamCatches);

                if ($includedCatches->isEmpty()) {
                    continue;
                }

                $rank = $teamRanks[$teamId] ?? 'N/A';
                $teamNumber = optional($includedCatches->first()->team)->team_uid ?? 'N/A';
                $teamName = optional($includedCatches->first()->team)->name ?? 'N/A';
                $totalPoints = 0;

                foreach ($includedCatches as $catch) {
                    $etu = DB::table('event_team_user')
                        ->where('event_id', $eventId)
                        ->where('team_id', $teamId)
                        ->where('user_id', $catch->angler_id)
                        ->first();

                    $photoUrl = $catch->getFirstMediaUrl('event_fish_images');

                    $fishPhoto = $photoUrl
                        ? '<a href="' . e($photoUrl) . '" class="glightbox" data-gallery="team-' . $teamId . '">'
                        . '<img src="' . e($photoUrl) . '" class="img-thumbnail" '
                        . 'style="width:200px;height:130px;object-fit:contain;cursor:pointer;" />'
                        . '</a>'
                        : 'No Photo';

                    $videoUrl = $catch->getFirstMediaUrl('release_video');

                    $releaseVideo = $videoUrl
                        ? '<a href="' . e($videoUrl) . '" class="glightbox">View Video</a>'
                        : 'No Video';

                    $finalRows->push([
                        'rank' => $rank,
                        'team_id' => $teamId,
                        'team_number' => $teamNumber,
                        'team_name' => $teamName,
                        'angler_number' => $etu->angular_uid ?? 'N/A',
                        'angler_name' => $etu->angular_name ?? $catch->angler->name ?? 'N/A',
                        'specie' => $catch->specie->name,
                        'fork_length' => $catch->fork_length,
                        'points' => $catch->points,
                        'fish_photo' => $fishPhoto,
                        'release_video' => $releaseVideo,
                        'is_summary_row' => false,
                    ]);

                    $totalPoints += $catch->points;
                }

                $finalRows->push([
                    'rank' => '',
                    'team_id' => $teamId,
                    'team_number' => '<strong>' . $teamNumber . '</strong>',
                    'team_name' => '<strong>' . $teamName . '</strong>',
                    'angler_number' => '',
                    'angler_name' => '',
                    'specie' => '',
                    'fork_length' => '<strong>Total Points</strong>',
                    'points' => '<strong>' . $totalPoints . '</strong>',
                    'fish_photo' => '',
                    'release_video' => '',
                    'is_summary_row' => true,
                ]);
            }

            return DataTables::of($finalRows)
                ->rawColumns(['angler_name', 'team_number', 'team_name', 'points', 'fork_length', 'fish_photo', 'release_video'])
                ->make(true);
        }

        return view('portal.reports.custom-team-ranking-report', compact('events'));
    }

    public function individualFishReport(Request $request)
    {
        $events = Event::all();
        $filteredCatches = [];

        if ($request->filled('event_id') && $request->filled('species')) {

            $eventId = $request->event_id;
            $species = $request->species;
            $categories = $request->angler_category ?? [];
            $rankNumber = $request->rank_number;

            foreach ($species as $specieId) {

                $collection = EventCatch::with(['angler', 'team', 'specie'])
                    ->where('event_id', $eventId)
                    ->where('specie_id', $specieId)
                    ->get();

                if (!empty($categories)) {
                    $collection = $collection->filter(function ($catch) use ($categories) {
                        // in_array returns true if catch's angler category is allowed
                        return in_array($catch->angler->category ?? null, $categories, true);
                    });
                }

                $collection = $collection
                    ->sortByDesc('fork_length')
                    ->values();

                if (!empty($rankNumber)) {
                    $collection = $collection->take((int)$rankNumber);
                }

                $filteredCatches[$specieId] = $collection;
            }
        }

        return view('portal.reports.individual-fish-report',
            compact('events', 'filteredCatches'));
    }

    public function extraPhotoReport(Request $request)
    {
        $events = Event::all();
        $eventId = $request->get('event_id');
        $specieIds = $request->get('species');
        $species = [];

        if (isset($eventId)) {
            $event = Event::with('species')->find($eventId);
            $species = $event->species->map(function ($specie) {
                return [
                    'id' => $specie->id,
                    'name' => $specie->name,
                ];
            });
        }

        if ($request->ajax()) {
            $catchesQuery = EventCatch::with(['angler', 'team', 'specie'])
                ->where('event_id', $eventId)
                ->whereHas('media', function ($query) {
                    $query->where('collection_name', 'glory_photos');
                });

            if (!empty($specieIds)) {
                $catchesQuery->whereIn('specie_id', (array)$specieIds);
            }
            $categories = $request->get('categories');
            if (!empty($categories)) {
                $catchesQuery->whereHas('angler', function ($query) use ($categories) {
                    $query->whereIn('category', $categories);
                });
            }

            $catches = $catchesQuery->orderByDesc('created_at')->get();
            $finalRows = collect();

            foreach ($catches as $index => $catch) {
                $teamCatches = $catches->where('team_id', $catch->team_id);

                $mediaItems = $catch->getMedia('glory_photos');
                if ($mediaItems->count() == 0) {
                    continue;
                }

                $teamNumber = optional($teamCatches->first()->team)->team_uid ?? 'N/A';
                $teamName = optional($teamCatches->first()->team)->name ?? 'N/A';

                $etu = DB::table('event_team_user')
                    ->where('event_id', $eventId)
                    ->where('team_id', $catch->team_id)
                    ->where('user_id', $catch->angler_id)
                    ->first();

                $photoUrl = $catch->getFirstMediaUrl('glory_photos');

                $fishPhoto = $photoUrl
                    ? '<a href="' . e($photoUrl) . '" class="glightbox" data-gallery="team-' . $catch->team_id . '">'
                    . '<img src="' . e($photoUrl) . '" class="img-thumbnail" '
                    . 'style="width:200px;height:130px;object-fit:contain;cursor:pointer;" />'
                    . '</a>'
                    : 'No Photo';

                $extraPhoto = '';
                $measurePhotos = $catch->getMedia('event_fish_images');

                if ($mediaItems->count() > 0) {
                    $extraItems = $mediaItems->slice(1);
                    $measurePhotoUrl = $catch->getFirstMediaUrl('event_fish_images');

                    $extraPhoto .= '<a href="' . e($measurePhotoUrl) . '" class="glightbox" data-gallery="team-' . $catch->team_id . '">';
                    $extraPhoto .= '<img src="' . e($measurePhotoUrl) . '" class="img-thumbnail m-1" '
                        . 'style="width:130px;height:90px;object-fit:contain;cursor:pointer;" />';
                    $extraPhoto .= '</a>';
                } else {
                    $extraPhoto = 'No Measure Photos';
                }

                $finalRows->push([
                    'team_id' => $catch->team_id,
                    'team_number' => $teamNumber,
                    'team_name' => $teamName,
                    'angler_number' => $etu->angular_uid ?? 'N/A',
                    'angler_name' => $etu->angular_name ?? $catch->angler->name ?? 'N/A',
                    'category' => $catch->angler->category ?? 'N/A',
                    'specie' => $catch->specie->name,
                    'fork_length' => $catch->fork_length,
                    'date_time' => $catch->created_at->format('F j, Y g:i A'),
                    'points' => $catch->points,
                    'fish_photo' => $fishPhoto,
                    'extra_fish_photo' => $extraPhoto,
                ]);

            }

            return DataTables::of($finalRows)
                ->rawColumns(['angler_name', 'team_number', 'team_name', 'points', 'fork_length', 'fish_photo', 'extra_fish_photo'])
                ->make(true);
        }

        return view('portal.reports.extra-photo-report', compact('events', 'species'));
    }

    public function eventLoginReport(Request $request)
    {
        $events = Event::all();
        $eventId = $request->get('event_id');
        $dates = [];

        if (isset($eventId)) {
            $event = Event::with('species')->find($eventId);
            $dates = $event->dates->map(function ($date) {
                return [
                    'date' => $date->date,
                ];
            });
        }

        if ($request->ajax()) {
            $attendancesQuery = EventAttendance::with(['angler', 'team'])
                ->where('event_id', $eventId);

            $selectedDates = $request->get('dates');
            if (!empty($selectedDates)) {
                $attendancesQuery->whereIn('date', $selectedDates);
            }

            $checkType = $request->get('check_type');
            if (isset($checkType)) {
                $attendancesQuery->whereNull('time_out');
            }

            $attendances = $attendancesQuery->orderByDesc('created_at')->get();

            $finalRows = collect();

            foreach ($attendances as $index => $attendance) {

               $formatDMS = function ($lat, $lng) {
                if (!$lat || !$lng) return 'N/A';

                // Latitude
                $latDir = $lat < 0 ? 'S' : 'N';
                $lat = abs($lat);
                $latDeg = floor($lat);
                $latMinFloat = ($lat - $latDeg) * 60;
                $latMin = floor($latMinFloat);
                $latSec = round(($latMinFloat - $latMin) * 60);

                // Longitude
                $lngDir = $lng < 0 ? 'W' : 'E';
                $lng = abs($lng);
                $lngDeg = floor($lng);
                $lngMinFloat = ($lng - $lngDeg) * 60;
                $lngMin = floor($lngMinFloat);
                $lngSec = round(($lngMinFloat - $lngMin) * 60);

                return sprintf(
                    "%s%02d %02d %03d, %s%02d %02d %03d",
                    $latDir, $latDeg, $latMin, $latSec,
                    $lngDir, $lngDeg, $lngMin, $lngSec
                );
            };

                $teamNumber = optional($attendance->team)->team_uid ?? 'N/A';
                $teamName = optional($attendance->team)->name ?? 'N/A';

                $etu = DB::table('event_team_user')
                    ->where('event_id', $eventId)
                    ->where('team_id', $attendance->team_id)
                    ->where('user_id', $attendance->user_id)
                    ->first();

                $finalRows->push([
                    'team_number' => $teamNumber,
                    'team_name' => $teamName,
                    'angler_number' => $etu->angular_uid ?? 'N/A',
                    'angler_name' => $etu->angular_name ?? $attendance->angler->name ?? 'N/A',
                    'angler_phone_number' => $attendance->angler->phone ?? 'N/A',
                    'date' => Carbon::parse($attendance->date)->format('l, F j, Y') ?? 'N/A',
                    'check_time_in' => $attendance->time_in
                        ? Carbon::parse("$attendance->date $attendance->time_in")->format('g:i A')
                        : 'Check-in not recorded',
                    'check_time_out' => $attendance->time_out
                        ? Carbon::parse("$attendance->date $attendance->time_out")->format('g:i A')
                        : 'Check-out not recorded',

                    'check_in_location' => $formatDMS(
                        $attendance->time_in_latitude,
                        $attendance->time_in_longitude
                    ),

                    'check_out_location' => $formatDMS(
                        $attendance->time_out_latitude,
                        $attendance->time_out_longitude
                    ),
                ]);

            }

            return DataTables::of($finalRows)
                ->rawColumns(['angler_name', 'team_number', 'team_name', 'points', 'fork_length', 'fish_photo', 'extra_fish_photo'])
                ->make(true);
        }

        return view('portal.reports.event-login-report', compact('events' , 'dates'));
    }

    /**
     * Teams Profiles Report: shows profile data and photos for each team.
     * Can show more than one user per team when multiple users are signed in for that team.
     */
    public function teamProfilesReport(Request $request)
    {
        $events = Event::orderByDesc('id')->get();
        $eventId = $request->get('event_id');
        $teamSearch = $request->get('team_search');
        $teamsWithUsers = collect();

        if ($eventId) {
            $teamUserIds = DB::table('event_team_user')
                ->where('event_id', $eventId)
                ->whereNull('deleted_at')
                ->select('team_id', 'user_id', 'angular_uid')
                ->get()
                ->groupBy('team_id');

            foreach ($teamUserIds as $teamId => $pivots) {
                $team = Team::find($teamId);
                if (!$team) {
                    continue;
                }

                if ($teamSearch) {
                    $search = strtolower(trim($teamSearch));
                    $nameMatch = str_contains(strtolower($team->name ?? ''), $search);
                    $uidMatch = str_contains(strtolower($team->team_uid ?? ''), $search);
                    if (!$nameMatch && !$uidMatch) {
                        continue;
                    }
                }

                $users = collect();
                $seenUserIds = [];
                foreach ($pivots as $pivot) {
                    if (in_array($pivot->user_id, $seenUserIds)) {
                        continue;
                    }
                    $seenUserIds[] = $pivot->user_id;
                    $user = User::with('profile')->find($pivot->user_id);
                    if ($user) {
                        $users->push((object) [
                            'user' => $user,
                            'angular_uid' => $pivot->angular_uid,
                        ]);
                    }
                }

                if ($users->isNotEmpty()) {
                    $teamsWithUsers->push((object) [
                        'team' => $team,
                        'users' => $users,
                    ]);
                }
            }
        }

        return view('portal.reports.team-profiles-report', compact('events', 'teamsWithUsers', 'eventId', 'teamSearch'));
    }

    /**
     * Catch Data Report: grouped by TEAM → SPECIES → FORK LENGTH → ANGLER → DATE/TIME.
     * Shows all catch data including timestamp and measure photo. Allows selecting and deleting rows.
     * Uses server-side pagination for faster loading.
     */
    public function catchDataReport(Request $request)
    {
        $events = Event::orderByDesc('id')->get();
        $eventId = $request->get('event_id');
        $event = null;

        if ($request->ajax()) {
            if (!$eventId) {
                return DataTables::of(collect())->make(true);
            }
            $event = Event::find($eventId);
            if (!$event) {
                return DataTables::of(collect())->make(true);
            }

            $query = EventCatch::where('event_id', $eventId)
                ->with(['team', 'specie', 'angler'])
                ->orderBy('team_id')
                ->orderBy('specie_id')
                ->orderByRaw('CAST(fork_length AS UNSIGNED) ASC')
                ->orderBy('angler_id')
                ->orderBy('catch_timestamp');

            return DataTables::eloquent($query)
                ->addColumn('select', function ($catch) {
                    return '<input type="checkbox" class="catch-checkbox" value="' . $catch->id . '">';
                })
                ->addColumn('team_uid', function ($catch) {
                    return $catch->team->team_uid ?? '-';
                })
                ->addColumn('team_name', function ($catch) {
                    return $catch->team->name ?? '-';
                })
                ->addColumn('specie_name', function ($catch) {
                    return $catch->specie->name ?? '-';
                })
                ->addColumn('angler_name', function ($catch) {
                    return $catch->angler->name ?? '-';
                })
                ->addColumn('measure_photo', function ($catch) {
                    $photoUrl = $catch->getFirstMediaUrl('event_fish_images');
                    if ($photoUrl) {
                        return '<a href="' . e($photoUrl) . '" class="glightbox" data-gallery="catch-report">' .
                            '<img src="' . e($photoUrl) . '" alt="Measure" class="img-thumbnail" style="width:80px;height:60px;object-fit:contain;cursor:pointer;"></a>';
                    }
                    return '<span class="text-muted">-</span>';
                })
                ->addColumn('release_video', function ($catch) {
                    $videoUrl = $catch->getFirstMediaUrl('release_video');
                    if ($videoUrl) {
                        return '<a href="' . e($videoUrl) . '" class="glightbox">
                        View Video
                    </a>';
                    }
                    return '<span class="text-muted">-</span>';
                })
                ->removeColumn('event_id')
                ->removeColumn('team_id')
                ->removeColumn('angler_id')
                ->removeColumn('specie_id')
                ->removeColumn('created_at')
                ->removeColumn('updated_at')
                ->rawColumns(['select', 'measure_photo', 'measure_photo'])
                ->make(true);
        }

        if ($eventId) {
            $event = Event::find($eventId);
        }

        return view('portal.reports.catch-data-report', compact('events', 'event', 'eventId'));
    }

    /**
     * Multi Event Bag Report:
     * Per team: individual rows for each top-N fish per selected event (up to max fish per
     * event), then a summary total row. Teams are ranked by combined bag points (high to low).
     */
    public function multiEventRankingReport(Request $request)
    {
        if ($request->ajax()) {
            $selectedEventIds = array_values(array_filter((array) $request->get('event_ids', [])));
            $specieId = (int) $request->get('specie_id', 0);
            $maxFishPerEvent = (int) $request->get('max_fish_per_event', 1);

            $validator = Validator::make(
                [
                    'event_ids' => $selectedEventIds,
                    'specie_id' => $specieId,
                    'max_fish_per_event' => $maxFishPerEvent,
                ],
                [
                    'event_ids' => 'required|array|min:1',
                    'event_ids.*' => 'integer|exists:events,id',
                    'specie_id' => 'required|integer|exists:species,id',
                    'max_fish_per_event' => 'required|integer|min:1',
                ]
            );

            if ($validator->fails()) {
                return DataTables::of(collect())->make(true);
            }

            $finalRows = $this->buildMultiEventBagReportRows($selectedEventIds, $specieId, $maxFishPerEvent);

            return DataTables::of($finalRows)
                ->rawColumns(['angler_name', 'team_number', 'team_name', 'points', 'fork_length', 'fish_photo', 'release_video'])
                ->make(true);
        }

        $events = Event::orderByDesc('id')->get();
        $species = Specie::orderBy('name')->get();

        $selectedEventIds = array_values(array_filter((array) $request->get('event_ids', [])));
        $specieId = $request->get('specie_id');
        $maxFishPerEvent = (int) $request->get('max_fish_per_event', 1);

        return view('portal.reports.multi-event-ranking-report', compact(
            'events',
            'species',
            'selectedEventIds',
            'specieId',
            'maxFishPerEvent'
        ));
    }

    /**
     * @param  array<int, int|string>  $selectedEventIds
     * @return Collection<int, array<string, mixed>>
     */
    private function buildMultiEventBagReportRows(array $selectedEventIds, int $specieId, int $maxFishPerEvent): Collection
    {
        $eventsById = Event::whereIn('id', $selectedEventIds)->get()->keyBy('id');

        $participations = DB::table('event_team_user')
            ->whereIn('event_id', $selectedEventIds)
            ->whereNull('deleted_at')
            ->select('event_id', 'team_id')
            ->distinct()
            ->get();

        $participatedTeamIds = $participations->pluck('team_id')->unique()->values();
        $participatedEventIdsByTeam = $participations
            ->groupBy('team_id')
            ->map(fn ($rows) => $rows->pluck('event_id')->unique()->values()->all());

        if ($participatedTeamIds->isEmpty()) {
            return collect();
        }

        $teamsById = Team::whereIn('id', $participatedTeamIds)->get()->keyBy('id');

        $allCatches = EventCatch::with(['angler', 'team', 'specie'])
            ->whereIn('event_id', $selectedEventIds)
            ->whereIn('team_id', $participatedTeamIds)
            ->where('specie_id', $specieId)
            ->get();

        $teamTotals = [];
        $teamCatchesByEvent = [];

        foreach ($participatedTeamIds as $teamId) {
            $teamCatchesByEvent[$teamId] = [];
            $teamTotals[$teamId] = 0;
            $teamEventIdSet = $participatedEventIdsByTeam->get($teamId, []);

            foreach ($selectedEventIds as $eventId) {
                if (!in_array((int) $eventId, array_map('intval', $teamEventIdSet), true)) {
                    continue;
                }

                $eventCatches = $allCatches
                    ->where('team_id', $teamId)
                    ->where('event_id', (int) $eventId)
                    ->sort(function ($a, $b) {
                        $pointsCmp = ((float) $b->points) <=> ((float) $a->points);
                        if ($pointsCmp !== 0) {
                            return $pointsCmp;
                        }

                        return ((int) $b->fork_length) <=> ((int) $a->fork_length);
                    })
                    ->values()
                    ->take($maxFishPerEvent);

                $teamCatchesByEvent[$teamId][$eventId] = $eventCatches;
                $teamTotals[$teamId] += (float) $eventCatches->sum('points');
            }
        }

        $sortedTeamIds = $participatedTeamIds
            ->filter(fn ($teamId) => ($teamTotals[$teamId] ?? 0) > 0)
            ->sort(function ($teamIdA, $teamIdB) use ($teamTotals, $teamsById) {
                $pointsCmp = ($teamTotals[$teamIdB] ?? 0) <=> ($teamTotals[$teamIdA] ?? 0);
                if ($pointsCmp !== 0) {
                    return $pointsCmp;
                }

                $teamNumberA = (string) ($teamsById->get($teamIdA)->team_uid ?? '');
                $teamNumberB = (string) ($teamsById->get($teamIdB)->team_uid ?? '');

                return strcmp($teamNumberA, $teamNumberB);
            })
            ->values();

        $teamRanks = $sortedTeamIds->flip()->map(fn ($index) => $index + 1);
        $finalRows = collect();

        foreach ($sortedTeamIds as $teamId) {
            $team = $teamsById->get($teamId);
            if (!$team) {
                continue;
            }

            $rank = $teamRanks[$teamId] ?? 'N/A';
            $teamNumber = $team->team_uid ?? 'N/A';
            $teamName = $team->name ?? 'N/A';
            $teamEventIdSet = $participatedEventIdsByTeam->get($teamId, []);
            $totalPoints = 0;

            foreach ($selectedEventIds as $eventId) {
                if (!in_array((int) $eventId, array_map('intval', $teamEventIdSet), true)) {
                    continue;
                }

                $eventCatches = $teamCatchesByEvent[$teamId][$eventId] ?? collect();
                $eventName = $eventsById->get($eventId)->name ?? 'Unknown';

                foreach ($eventCatches as $catch) {
                    $etu = DB::table('event_team_user')
                        ->where('event_id', $eventId)
                        ->where('team_id', $teamId)
                        ->where('user_id', $catch->angler_id)
                        ->first();

                    $photoUrl = $catch->getFirstMediaUrl('event_fish_images');
                    $fishPhoto = $photoUrl
                        ? '<a href="' . e($photoUrl) . '" class="glightbox" data-gallery="team-' . $teamId . '-event-' . $eventId . '">'
                        . '<img src="' . e($photoUrl) . '" class="img-thumbnail" '
                        . 'style="width:200px;height:130px;object-fit:contain;cursor:pointer;" />'
                        . '</a>'
                        : 'No Photo';

                    $videoUrl = $catch->getFirstMediaUrl('release_video');
                    $releaseVideo = $videoUrl
                        ? '<a href="' . e($videoUrl) . '" class="glightbox">View Video</a>'
                        : 'No Video';

                    $finalRows->push([
                        'rank' => $rank,
                        'event' => $eventName,
                        'team_id' => $teamId,
                        'team_number' => $teamNumber,
                        'team_name' => $teamName,
                        'angler_number' => $etu->angular_uid ?? 'N/A',
                        'angler_name' => $etu->angular_name ?? $catch->angler->name ?? 'N/A',
                        'specie' => $catch->specie->name ?? 'N/A',
                        'fork_length' => $catch->fork_length,
                        'points' => $catch->points,
                        'fish_photo' => $fishPhoto,
                        'release_video' => $releaseVideo,
                        'is_summary_row' => false,
                    ]);

                    $totalPoints += (float) $catch->points;
                }
            }

            if ($totalPoints <= 0) {
                continue;
            }

            $finalRows->push([
                'rank' => '',
                'event' => '',
                'team_id' => $teamId,
                'team_number' => '<strong>' . e($teamNumber) . '</strong>',
                'team_name' => '<strong>' . e($teamName) . '</strong>',
                'angler_number' => '',
                'angler_name' => '',
                'specie' => '',
                'fork_length' => '<strong>Total Points</strong>',
                'points' => '<strong>' . $totalPoints . '</strong>',
                'fish_photo' => '',
                'release_video' => '',
                'is_summary_row' => true,
            ]);
        }

        return $finalRows;
    }

    public function getEventDates($eventId)
    {
        $dates = Event::findOrFail($eventId)
            ->dates()
            ->orderBy('date')
            ->get(['id', 'date']);

        return response()->json($dates);
    }

    public function getEventDateIntervals($dateId)
    {
        $date = EventDate::findOrFail($dateId);
        $intervals = [];

        if ($date->start_time && $date->end_time && $date->im_safe_interval) {
            $start = Carbon::parse($date->start_time);
            $end = Carbon::parse($date->end_time);
            
            $current = $start->copy();
            while ($current->lte($end)) {
                $intervals[] = $current->format('H:i');
                if ($current->eq($end)) {
                    break;
                }
                $current->addMinutes($date->im_safe_interval);
                if ($current->gt($end)) {
                    $intervals[] = $end->format('H:i');
                    break;
                }
            }
        }
        
        $intervals = array_unique($intervals);
        return response()->json(array_values($intervals));
    }

    public function imSafeReport(Request $request)
    {
        $events = Event::where('has_im_safe', 1)->get();
        $eventId = $request->get('event_id');
        $dateId = $request->get('date_id');
        $intervals = array_filter((array) $request->input('intervals', []));
        
        $dates = [];
        if ($eventId) {
            $event = Event::find($eventId);
            if ($event) {
                $dates = $event->dates;
            }
        }

        if ($request->ajax()) {
            if (!$eventId || !$dateId) {
                return response()->json(['data' => []]);
            }
            
            $date = EventDate::where('event_id', $eventId)->find($dateId);
            if (!$date) {
                return response()->json(['data' => []]);
            }
            $targetDate = $date->date;

            $safetyChecks = DB::table('event_safety_checks')
                ->join('users', 'event_safety_checks.angler_id', '=', 'users.id')
                ->join('teams', 'event_safety_checks.team_id', '=', 'teams.id')
                ->where('event_safety_checks.event_id', $eventId)
                ->whereDate('event_safety_checks.time_stamp', $targetDate)
                ->select(
                    'event_safety_checks.id',
                    'event_safety_checks.angler_id',
                    'event_safety_checks.location_code',
                    'event_safety_checks.time_stamp',
                    'users.name as angler_name',
                    'users.phone as angler_phone',
                    'teams.name as team_name'
                )
                ->get();

            if (empty($intervals)) {
                $matchedChecks = $safetyChecks;
            } else {
                $intervalMinutes = max(1, (int) ($date->im_safe_interval ?? 120));

                $matchedChecks = $safetyChecks->filter(function ($check) use ($intervals, $targetDate, $intervalMinutes) {
                    $checkTime = Carbon::parse($check->time_stamp);

                    foreach ($intervals as $interval) {
                        $intervalStart = Carbon::parse($targetDate . ' ' . $interval);
                        $intervalEnd = $intervalStart->copy()->addMinutes($intervalMinutes);

                        if ($checkTime->gte($intervalStart) && $checkTime->lt($intervalEnd)) {
                            return true;
                        }
                    }

                    return false;
                });
            }

            $finalRows = $matchedChecks
                ->sortBy([
                    ['team_name', 'asc'],
                    ['angler_name', 'asc'],
                    ['time_stamp', 'asc'],
                ])
                ->values()
                ->map(fn ($check) => [
                    'team_name' => $check->team_name,
                    'angler_name' => $check->angler_name,
                    'phone_number' => $check->angler_phone,
                    'location_code' => $check->location_code,
                    'time_stamp' => Carbon::parse($check->time_stamp)->format('h:i A'),
                ]);

            return response()->json(['data' => $finalRows]);
        }

        return view('portal.reports.im-safe-report', compact('events', 'dates', 'eventId', 'dateId'));
    }
}
