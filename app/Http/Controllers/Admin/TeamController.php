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

    public function show(Team $team)
    {
        $team->load(['category', 'groupTeams.group']);
        return view('admin.teams.show', compact('team'));
    }
}
