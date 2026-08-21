<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Team;

class DashboardController extends Controller
{
    public function index(\Illuminate\Http\Request $request)
    {
        $gameType = $request->input('game_type');

        $categories = Category::withCount([
            'teams' => function ($q) use ($gameType) {
                if ($gameType) $q->where('game_type', $gameType);
            },
            'teams as checked_in_count' => function ($q) use ($gameType) {
                $q->where('status', 'checked_in');
                if ($gameType) $q->where('game_type', $gameType);
            },
            'teams as absent_count' => function ($q) use ($gameType) {
                $q->where('status', 'absent');
                if ($gameType) $q->where('game_type', $gameType);
            },
            'teams as registered_count' => function ($q) use ($gameType) {
                $q->where('status', 'registered');
                if ($gameType) $q->where('game_type', $gameType);
            },
        ])->orderBy('sort_order')->get();

        $teamQuery = Team::query();
        if ($gameType) $teamQuery->where('game_type', $gameType);
        
        $totalTeams      = (clone $teamQuery)->count();
        $totalCheckedIn  = (clone $teamQuery)->where('status', 'checked_in')->count();
        $totalAbsent     = (clone $teamQuery)->where('status', 'absent')->count();
        $totalRegistered = (clone $teamQuery)->where('status', 'registered')->count();

        return view('admin.dashboard', compact(
            'categories', 'totalTeams', 'totalCheckedIn', 'totalAbsent', 'totalRegistered', 'gameType'
        ));
    }
}
