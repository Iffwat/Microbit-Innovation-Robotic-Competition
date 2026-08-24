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
        return $query->orderByDesc('goals_for')
                     ->orderByDesc('won')
                     ->orderByDesc('points')
                     ->orderByDesc('goal_difference')
                     ->orderBy('goals_against')
                     ->get();
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

