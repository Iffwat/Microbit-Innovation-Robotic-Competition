<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Team;
use Illuminate\Http\Request;

class TeamController extends Controller
{
    public function index(Request $request)
    {
        $query = Team::with(['category'])
                     ->orderBy('category_id')
                     ->orderBy('team_name');

        // Filter by category
        if ($request->filled('category')) {
            $query->whereHas('category', fn($q) => $q->where('slug', $request->category));
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by game type
        if ($request->filled('game_type')) {
            $query->where('game_type', $request->game_type);
        }

        // Search by name or school
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('team_name', 'like', "%$search%")
                  ->orWhere('school_name', 'like', "%$search%");
            });
        }

        $teams      = $query->paginate(25)->withQueryString();
        $categories = Category::orderBy('sort_order')->get();

        return view('admin.teams.index', compact('teams', 'categories'));
    }

    public function create()
    {
        $categories = Category::orderBy('sort_order')->get();
        return view('admin.teams.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'team_name'    => 'required|string|max:255',
            'school_name'  => 'required|string|max:255',
            'category_id'  => 'required|exists:categories,id',
            'game_type'    => 'required|in:isobot,sky_soccer,obstacle',
            'player_1'     => 'nullable|string|max:255',
            'player_2'     => 'nullable|string|max:255',
            'player_3'     => 'nullable|string|max:255',
            'mentor_name'  => 'nullable|string|max:255',
            'mentor_email' => 'nullable|string|max:255',
            'status'       => 'nullable|in:registered,checked_in,absent',
        ]);

        $validated['status'] = $validated['status'] ?? 'registered';
        $validated['registered_at'] = now();

        $team = Team::create($validated);

        return redirect()->route('admin.teams.index')
            ->with('success', "Pasukan {$team->team_name} berjaya ditambah.");
    }

    public function show(Team $team)
    {
        $team->load(['category', 'groupTeams.group']);
        return view('admin.teams.show', compact('team'));
    }

    public function edit(Team $team)
    {
        $categories = Category::orderBy('sort_order')->get();
        return view('admin.teams.edit', compact('team', 'categories'));
    }

    public function update(Request $request, Team $team)
    {
        $validated = $request->validate([
            'team_name'    => 'required|string|max:255',
            'school_name'  => 'required|string|max:255',
            'category_id'  => 'required|exists:categories,id',
            'game_type'    => 'required|in:isobot,sky_soccer,obstacle',
            'player_1'     => 'nullable|string|max:255',
            'player_2'     => 'nullable|string|max:255',
            'player_3'     => 'nullable|string|max:255',
            'mentor_name'  => 'nullable|string|max:255',
            'mentor_email' => 'nullable|string|max:255',
            'status'       => 'required|in:registered,checked_in,absent',
        ]);

        if ($validated['status'] === 'checked_in' && !$team->checked_in_at) {
            $validated['checked_in_at'] = now();
        } elseif ($validated['status'] !== 'checked_in') {
            $validated['checked_in_at'] = null;
        }

        $team->update($validated);

        return redirect()->route('admin.teams.index')
            ->with('success', "Maklumat pasukan {$team->team_name} berjaya dikemaskini.");
    }

    public function destroy(Team $team)
    {
        $name = $team->team_name;
        $team->delete();

        return redirect()->route('admin.teams.index')
            ->with('success', "Pasukan {$name} berjaya dipadam.");
    }

    public function destroyAll()
    {
        \Illuminate\Support\Facades\DB::beginTransaction();
        try {
            \App\Models\TournamentMatch::truncate();
            \App\Models\GroupTeam::truncate();
            \App\Models\Group::truncate();
            Team::truncate();
            \Illuminate\Support\Facades\DB::commit();

            return redirect()->route('admin.teams.index')
                ->with('success', 'Semua rekod pasukan, kumpulan, dan perlawanan telah berjaya dipadam.');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return back()->with('error', 'Ralat semasa memadam: ' . $e->getMessage());
        }
    }
}
