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

    // Get standings sorted by points, goal_difference, goals_for
    public function getStandings()
    {
        return $this->groupTeams()
                    ->with('team')
                    ->orderByDesc('points')
                    ->orderByDesc('goal_difference')
                    ->orderByDesc('goals_for')
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

