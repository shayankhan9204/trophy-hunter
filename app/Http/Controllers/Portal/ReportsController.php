<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventCatch;
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

            $catches = EventCatch::with(['angler', 'team'])
                ->where('event_id', $eventId)
                ->get();

            $teamPoints = $catches->groupBy('team_id')->map(fn($group) => $group->sum('points'));

            $sortedTeamIds = $teamPoints->sortDesc()->keys()->values();
            $teamRanks = $sortedTeamIds->flip()->map(fn($index) => $index + 1);

            $finalRows = collect();

            foreach ($sortedTeamIds as $teamId) {
                $teamCatches = $catches->where('team_id', $teamId);
                $rank = $teamRanks[$teamId] ?? 'N/A';
                $teamNumber = optional($teamCatches->first()->team)->team_uid ?? 'N/A';

                $anglerGrouped = $teamCatches->groupBy('angler_id');

                $totalFish = 0;
                $totalPoints = 0;

                foreach ($anglerGrouped as $anglerId => $anglerCatches) {
                    $first = $anglerCatches->first();

                    $etu = DB::table('event_team_user')
                        ->where('event_id', $eventId)
                        ->where('team_id', $teamId)
                        ->where('user_id', $anglerId)
                        ->first();

                    $fishCount = $anglerCatches->count();
                    $points = $anglerCatches->sum('points');
                    $largestSize = $anglerCatches->max('fork_length');
                    $largestPoints = optional($anglerCatches->where('fork_length', $largestSize)->first())->points ?? 0;
                    $avgSize = round($anglerCatches->avg('fork_length'), 2);

                    $finalRows->push([
                        'rank' => $rank,
                        'team_id' => $teamId,
                        'team_number' => $teamNumber,
                        'angler_number' => $etu->angular_uid ?? 'N/A',
                        'angler_name' => $etu->angular_name ?? $first->angler->name ?? 'N/A',
                        'total_fish' => $fishCount,
                        'total_points' => $points,
                        'largest_fish_size' => $largestSize,
                        'largest_fish_points' => $largestPoints,
                        'avg_fish_size' => $avgSize,
                        'is_summary_row' => false,
                    ]);

                    $totalFish += $fishCount;
                    $totalPoints += $points;
                }

                $finalRows->push([
                    'rank' => '',
                    'team_id' => $teamId,
                    'team_number' => '<strong>' . $teamNumber . '</strong>',
                    'angler_number' => '',
                    'angler_name' => '<strong>Team Total</strong>',
                    'total_fish' => '<strong>' . $totalFish . '</strong>' ,
                    'total_points' => '<strong>' . $totalPoints . '</strong>' ,
                    'largest_fish_size' => '',
                    'largest_fish_points' => '',
                    'avg_fish_size' => '',
                    'is_summary_row' => true,
                ]);
            }

            return DataTables::of($finalRows)
                ->rawColumns(['angler_name' , 'team_number' , 'total_fish' , 'total_points'])
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
                $query = EventCatch::with(['angler', 'team', 'specie'])
                    ->where('event_id', $eventId)
                    ->where('specie_id', $specieId);

                if (!empty($categories)) {
                    $query->whereHas('angler', function ($q) use ($categories) {
                        $q->whereIn('category', $categories);
                    });
                }

                if (!empty($rankNumber)) {
                    $query->orderBy('fork_length', 'asc');
                    $query->limit((int)$rankNumber);
                }

                $filteredCatches[$specieId] = $query->get();
            }
        }

        return view('portal.reports.individual-fish-report', compact('events', 'filteredCatches'));
    }


}
