<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventAttendance;
use App\Models\EventCatch;
use App\Models\Team;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
                    'is_summary_row' => true,
                ]);
            }

            return DataTables::of($finalRows)
                ->rawColumns(['angler_name', 'team_number', 'team_name', 'points', 'fork_length', 'fish_photo'])
                ->make(true);
        }

        return view('portal.reports.ranking-report', compact('events'));
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

                $photoUrl = $mediaItems->first()?->getUrl();

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
                    $measurePhotoUrl = $measurePhotos->first()?->getUrl();

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
     */
    public function catchDataReport(Request $request)
    {
        $events = Event::orderByDesc('id')->get();
        $eventId = $request->get('event_id');
        $event = null;
        $catches = collect();

        if ($eventId) {
            $event = Event::find($eventId);
            if ($event) {
                $catches = EventCatch::where('event_id', $eventId)
                    ->with(['team', 'specie', 'angler'])
                    ->orderBy('team_id')
                    ->orderBy('specie_id')
                    ->orderByRaw('CAST(fork_length AS UNSIGNED) ASC')
                    ->orderBy('angler_id')
                    ->orderBy('catch_timestamp')
                    ->get();
            }
        }

        return view('portal.reports.catch-data-report', compact('events', 'event', 'catches', 'eventId'));
    }
}
