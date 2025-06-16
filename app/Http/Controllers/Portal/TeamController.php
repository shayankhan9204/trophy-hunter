<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Imports\TeamMembersImport;
use App\Models\Event;
use App\Models\EventTeamUser;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TeamController extends Controller
{
    public function index(Request $request)
    {
        $events = Event::all();

        if ($request->has('event_id') && $request->event_id) {
            $eventId = $request->event_id;

            $teams = Team::whereHas('events', function ($query) use ($eventId) {
                $query->where('event_id', $eventId);
            })
                ->with(['anglers' => function ($query) use ($eventId) {
                    $query->wherePivot('event_id', $eventId)->distinct('users.id');
                }])
                ->get();
        } else {
            $teams = Team::whereHas('events')
                ->with('anglers')
                ->get()
                ->map(function ($team) {
                    $team->setRelation('anglers', $team->anglers->unique('id')->values());
                    return $team;
                });
        }

        return view('portal.teams.index', compact('teams', 'events'));
    }

    public function edit($id)
    {
        $team = Team::where('id', $id)
            ->with('anglers')
            ->first();

        if ($team) {
            $team->setRelation('anglers', $team->anglers->unique('id')->values());
        }

        return view('portal.teams.edit', compact('team'));
    }


    public function update(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:teams,id',
            'name' => 'required|string|max:255',
            'angler_id' => 'array',
            'angler_name' => 'array',
            'angler_category' => 'array',
            'angler_email' => 'array',
            'angler_phone' => 'array',
            'angler_uid' => 'array',
        ]);

        DB::beginTransaction();

        try {
            $team = Team::findOrFail($request->id);
            $team->name = $request->name;
            $team->save();

            $submittedAnglerIds = [];

            foreach ($request->angler_name as $index => $name) {
                $anglerId = $request->angler_id[$index] ?? null;
                $category = $request->angler_category[$index] ?? 'adult';
                $email = $request->angler_email[$index] ?? null;
                $phone = $request->angler_phone[$index] ?? null;
                $angularUid = $request->angler_uid[$index] ?? null;

                if (!$name) continue;

                if ($anglerId) {
                    $angler = $team->anglers()->where('users.id', $anglerId)->first();

                    if ($angler) {
                        $angler->update([
                            'name' => $name,
                            'email' => $email,
                            'phone' => $phone,
                            'category' => $category,
                        ]);

                        $team->anglers()->updateExistingPivot($angler->id, [
                            'angular_uid' => $angularUid,
                        ]);

                        $submittedAnglerIds[] = $angler->id;
                    }
                } else {
                    $angler = User::firstOrCreate(
                        ['email' => $email],
                        [
                            'name' => $name,
                            'phone' => $phone,
                            'category' => $category,
                            'password' => Hash::make($phone),
                        ]
                    );

                    $eventIds = \DB::table('event_team_user')
                        ->where('team_id', $team->id)
                        ->distinct()
                        ->pluck('event_id');

                    foreach ($eventIds as $eventId) {
                        $alreadyExists = \DB::table('event_team_user')
                            ->where('team_id', $team->id)
                            ->where('user_id', $angler->id)
                            ->where('event_id', $eventId)
                            ->exists();

                        if (!$alreadyExists) {
                            $angler->team()->attach($team->id, [
                                'event_id' => $eventId,
                                'angular_uid' => $angularUid ?? 'AGL-' . str_pad($angler->id, 3, '0', STR_PAD_LEFT),
                            ]);
                        }
                    }

                    $submittedAnglerIds[] = $angler->id;
                }
            }

            $toDeleteAnglers = $team->anglers()
                ->whereNotIn('users.id', $submittedAnglerIds)
                ->get();

            foreach ($toDeleteAnglers as $angler) {
                $angler->team()->detach($team->id);
                $angler->delete();
            }

            DB::commit();

            return back()->with('success', 'Team updated successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to update team: ' . $e->getMessage());
        }
    }

    public function uploadAnglers(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt',
            'event_id' => 'required',
        ]);

        $file = $request->file('csv_file');

        if (!$file) {
            return back()->with('error', 'No file was uploaded.');
        }

        $filename = time() . '_' . $file->getClientOriginalName();
        $destination = storage_path('app/temp');

        if (!file_exists($destination)) {
            mkdir($destination, 0755, true);
        }

        $file->move($destination, $filename);

        $fullPath = $destination . '/' . $filename;

        if (!file_exists($fullPath)) {
            return back()->with('error', 'Uploaded file could not be found at ' . $fullPath);
        }

        Excel::import(new TeamMembersImport($request->event_id), $fullPath);

        return back()->with('success', 'Anglers uploaded and assigned to event successfully!');

    }


    public function downloadSampleCsv()
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="teams_sample.csv"',
        ];

        $callback = function () {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Team Name' , 'Angler Number', 'Angler Name', 'Category', 'Email', 'Phone']);
            fputcsv($handle, ['Shark Masters' ,'AGL-001' ,'John Doe', 'adult', 'john@example.com', '555-1234']);
            fputcsv($handle, ['Ocean Kings' ,'AGL-002' ,'Jane Roe', 'junior', 'jane@example.com', '555-5678']);
            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function deleteEventTeams(Request $request)
    {
        $request->validate([
            'event_id' => 'required|exists:events,id',
        ]);

        EventTeamUser::where('event_id', $request->event_id)->delete();

        return redirect()->route('team.index')
            ->with('success', 'All teams have been detached from this event.');

    }

    public function detachTeamFromEvent(Request $request)
    {
        $request->validate([
            'team_id' => 'required|exists:teams,id',
            'event_id' => 'required|exists:events,id',
        ]);

        EventTeamUser::where('team_id', $request->team_id)
            ->where('event_id', $request->event_id)
            ->delete();

        return redirect()->route('team.index', ['event_id' => $request->event_id])
            ->with('success', 'Team removed from the event successfully.');
    }

    public function export()
    {
        $teams = Team::whereHas('events')->with('anglers')->get();

        $headers = [
            "Content-type" => "text/csv",
            "Content-Disposition" => "attachment; filename=teams_export.csv",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        ];

        $columns = ['Team Name', 'Team Number', 'Angler Name', 'Angler Number', 'Email', 'Phone', 'Category'];

        $callback = function () use ($teams, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($teams as $team) {
                foreach ($team->anglers->unique('id') as $angler) {
                    fputcsv($file, [
                        $team->name ?? 'N/A',
                        $team->team_uid ?? 'N/A',
                        $angler->name ?? 'N/A',
                        optional($angler->pivot)->angular_uid ?? 'N/A',
                        $angler->email ?? 'N/A',
                        $angler->phone ?? 'N/A',
                        $angler->category ?? 'N/A',
                    ]);
                }
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

}
