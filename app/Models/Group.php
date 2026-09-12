<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Group extends Model
{
    protected $fillable = [
        'category_id', 'game_type', 'group_name', 'group_letter',
        'field_number', 'is_complete',
    ];

    protected $casts = [
        'is_complete' => 'boolean',
    ];

    // Relations
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'group_teams')
                    ->withPivot(['played','won','drawn','lost','goals_for','goals_against','goal_difference','points','position','qualification'])
                    ->withTimestamps();
    }

    public function groupTeams(): HasMany
    {
        return $this->hasMany(GroupTeam::class);
    }

    public function matches(): HasMany
    {
        return $this->hasMany(TournamentMatch::class);
    }

    // Get standings sorted dynamically based on game type
    public function getStandings()
    {
        $query = $this->groupTeams()->with('team');
        
        if ($this->game_type === 'obstacle') {
            // For obstacle, goals_for stores the total time in milliseconds
            // We want to sort by fastest time ascending, but push 0 (no time recorded yet) to the bottom
            return $query->orderByRaw('CASE WHEN goals_for > 0 THEN 0 ELSE 1 END')
                         ->orderBy('goals_for', 'asc')
                         ->get();
        }

        // For soccer, priority order: Total Goals (goals_for) -> Total Wins (won) -> Points -> Goal Difference -> Goals Against
        // When teams are tied across all criteria, break the tie via:
        // 1. Play-off / Penentuan match result (if played)
        // 2. Manual position override (if set via swap)
        // 3. Head-to-head match result
        $teams = $query->get();

        return $teams->sort(function($a, $b) {
            // 1. Total Goals
            if ($b->goals_for !== $a->goals_for) return $b->goals_for <=> $a->goals_for;
            // 2. Total Wins
            if ($b->won !== $a->won) return $b->won <=> $a->won;
            // 3. Total Points
            if ($b->points !== $a->points) return $b->points <=> $a->points;
            // 4. Goal Difference
            if ($b->goal_difference !== $a->goal_difference) return $b->goal_difference <=> $a->goal_difference;
            // 5. Goals Against (fewer is better)
            if ($a->goals_against !== $b->goals_against) return $a->goals_against <=> $b->goals_against;

            // --- TIE-BREAKER 1: Manual Position Override (if explicitly set by Admin via Swap) ---
            if ($a->position > 0 && $b->position > 0 && $a->position !== $b->position) {
                return $a->position <=> $b->position;
            }

            // --- TIE-BREAKER 2: Completed Play-off / Penentuan Match ---
            $playoff = TournamentMatch::where('group_id', $this->id)
                ->where('status', 'completed')
                ->where(function($q) {
                    $q->where('round_name', 'like', '%Play-off%')
                      ->orWhere('round_name', 'like', '%Penentuan%');
                })
                ->where(function($q) use ($a, $b) {
                    $q->where(function($sub) use ($a, $b) {
                        $sub->where('home_team_id', $a->team_id)->where('away_team_id', $b->team_id);
                    })->orWhere(function($sub) use ($a, $b) {
                        $sub->where('home_team_id', $b->team_id)->where('away_team_id', $a->team_id);
                    });
                })
                ->whereNotNull('winner_team_id')
                ->first();

            if ($playoff) {
                if ($playoff->winner_team_id == $a->team_id) return -1;
                if ($playoff->winner_team_id == $b->team_id) return 1;
            }

            // --- TIE-BREAKER 3: Regular Head-to-Head Result ---
            $h2h = TournamentMatch::where('group_id', $this->id)
                ->where('status', 'completed')
                ->where(function($q) use ($a, $b) {
                    $q->where(function($sub) use ($a, $b) {
                        $sub->where('home_team_id', $a->team_id)->where('away_team_id', $b->team_id);
                    })->orWhere(function($sub) use ($a, $b) {
                        $sub->where('home_team_id', $b->team_id)->where('away_team_id', $a->team_id);
                    });
                })
                ->whereNotNull('winner_team_id')
                ->first();

            if ($h2h) {
                if ($h2h->winner_team_id == $a->team_id) return -1;
                if ($h2h->winner_team_id == $b->team_id) return 1;
            }

            return $a->id <=> $b->id;
        })->values();
    }

    public function getTotalMatchesAttribute(): int
    {
        $n = $this->groupTeams()->count();
        return ($n * ($n - 1)) / 2;
    }

    public function getCompletedMatchesAttribute(): int
    {
        return $this->matches()->where('status', 'completed')->count();
    }
}

