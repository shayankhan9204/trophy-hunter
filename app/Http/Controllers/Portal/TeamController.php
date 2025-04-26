<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Imports\TeamMembersImport;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Response;

class TeamController extends Controller
{
    public function index()
    {
        $teams = Team::with('anglers')->get();
        return view('portal.teams.index', compact('teams'));
    }

    public function edit($id)
    {
        $team = Team::where('id', $id)->with('anglers')->first();
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

                if (!$name) continue;

                if ($anglerId) {
                    $angler = $team->anglers()->where('id', $anglerId)->first();
                    if ($angler) {
                        $angler->update([
                            'name' => $name,
                            'email' => $email,
                            'phone' => $phone,
                            'category' => $category,
                        ]);
                        $submittedAnglerIds[] = $angler->id;
                    }
                } else {
                    $angler = $team->anglers()->create([
                        'name' => $name,
                        'email' => $email,
                        'phone' => $phone,
                        'category' => $category,
                        'password' => Hash::make('12345678'),
                        'team_id' => $team->id,
                    ]);
                    $submittedAnglerIds[] = $angler->id;
                }
            }

            $team->anglers()->whereNotIn('id', $submittedAnglerIds)->delete();

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
            'csv_file' => 'required|file|mimes:csv,txt|max:2048',
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

        Excel::import(new TeamMembersImport, $fullPath);

        return back()->with('success', 'Anglers uploaded successfully!');

    }


    public function downloadSampleCsv()
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="teams_sample.csv"',
        ];

        $callback = function () {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Team Name', 'Angler Name', 'Category', 'Email', 'Phone']);
            fputcsv($handle, ['Shark Masters', 'John Doe', 'adult', 'john@example.com', '555-1234']);
            fputcsv($handle, ['Ocean Kings', 'Jane Roe', 'junior', 'jane@example.com', '555-5678']);
            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

}
