<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $fillable = [
        'name', 'slug', 'format', 'teams_per_group',
        'fields_count', 'is_active', 'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Format label
    public function getFormatLabelAttribute(): string
    {
        return match($this->format) {
            'group_knockout'    => 'Kumpulan → Knockout',
            'round_robin_only'  => 'Round Robin Sahaja',
            default             => $this->format,
        };
    }

    public function isRoundRobinOnly(): bool
    {
        return $this->format === 'round_robin_only';
    }

    // Relations
    public function teams(): HasMany
    {
        return $this->hasMany(Team::class);
    }

    public function groups(): HasMany
    {
        return $this->hasMany(Group::class);
    }

    public function matches(): HasMany
    {
        return $this->hasMany(TournamentMatch::class);
    }

    public function knockoutSlots(): HasMany
    {
        return $this->hasMany(KnockoutSlot::class);
    }

    // Stats
    public function getRegisteredCountAttribute(): int
    {
        return $this->teams()->where('status', 'registered')->count();
    }

    public function getPendingCountAttribute(): int
    {
        return $this->teams()->where('status', 'registered')->count();
    }

    public function getCheckedInCountAttribute(): int
    {
        return $this->teams()->where('status', 'checked_in')->count();
    }

    public function getAbsentCountAttribute(): int
    {
        return $this->teams()->where('status', 'absent')->count();
    }
}
