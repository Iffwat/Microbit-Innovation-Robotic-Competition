<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Team extends Model
{
    protected $fillable = [
        'category_id', 'game_type', 'team_name', 'school_name',
        'player_1', 'player_2', 'player_3',
        'mentor_name', 'mentor_email',
        'status', 'registered_at', 'checked_in_at',
    ];

    protected $casts = [
        'registered_at' => 'datetime',
        'checked_in_at' => 'datetime',
    ];

    // Status mapping for views
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'registered' => 'Berdaftar',
            'checked_in' => 'Hadir',
            'absent'     => 'Tidak Hadir',
            default      => $this->status,
        };
    }

    public function getGameTypeLabelAttribute(): string
    {
        return match($this->game_type) {
            'isobot'     => 'Isobot Soccer',
            'sky_soccer' => 'Drone Sky Soccer',
            'obstacle'   => 'Drone Obstacle',
            default      => $this->game_type,
        };
    }

    // Relations
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function groupTeams(): HasMany
    {
        return $this->hasMany(GroupTeam::class);
    }
}
