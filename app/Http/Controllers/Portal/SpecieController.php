<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Specie;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class SpecieController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $species = Specie::orderByDesc('id')->get();

            return DataTables::of($species)
                ->addColumn('validation_rule', function ($row) {
                    return $row->validation_rule ?: 'N/A';
                })
                ->addColumn('min_validation_rule', function ($row) {
                    return $row->min_validation_rule ?: 'N/A';
                })
                ->addColumn('action', function ($row) {
                    $editUrl = route('specie.edit', ['id' => $row->id]);
                    $deleteUrl = route('specie.delete', ['id' => $row->id]);
                    $actions = '';
                    $actions .= '<a href="' . $editUrl . '" class="mr-2" data-toggle="tooltip" title="Edit Event">
                        <i class="fas fa-pencil-alt"></i>
                    </a>';
                    $actions .= '<form action="' . $deleteUrl . '" method="POST" style="display: inline;" onsubmit="return confirm(\'Are you sure you want to delete this Specie?\');">
                        ' . csrf_field() . method_field('DELETE') . '
                        <button type="submit" class="btn btn-link text-danger p-0" data-toggle="tooltip" title="Delete Specie">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </form>';
                    return $actions;
                })->rawColumns(['action'])
                ->make(true);
        }
        return view('portal.species.index');
    }

    public function create()
    {
        return view('portal.species.create');
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'formula' => 'required',
                'validation_rule' => 'nullable',
                'min_validation_rule' => 'nullable',
            ]);

            DB::beginTransaction();

            Specie::create($validated);

            DB::commit();

            return redirect()->route('specie.index')->with('success', 'Specie created successfully.');
        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'Something went wrong: ' . $e->getMessage());
        }
    }

    public function edit($id)
    {
        $specie = Specie::where('id', $id)->first();

        return view('portal.species.edit', compact( 'specie'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'formula' => 'required',
        ]);

        $specie = Specie::findOrFail($request->id);

        $specie->update([
            'name' => $request->name,
            'formula' => $request->formula,
            'validation_rule' => $request->validation_rule ?? null,
            'min_validation_rule' => $request->min_validation_rule ?? null,
        ]);

        return redirect()->back()->with('success', 'Specie updated successfully!');
    }

    public function destroy($id)
    {
        $specie = Specie::findOrFail($id);
        $specie->delete();

        return redirect()->route('specie.index')->with('success', 'Specie deleted successfully!');
    }

}
