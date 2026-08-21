<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GroupTeam extends Model
{
    protected $table = 'group_teams';

    protected $fillable = [
        'group_id', 'team_id', 'played', 'won', 'drawn', 'lost',
        'goals_for', 'goals_against', 'goal_difference', 'points',
        'position', 'qualification',
    ];

    protected $casts = [
        'played'          => 'integer',
        'won'             => 'integer',
        'drawn'           => 'integer',
        'lost'            => 'integer',
        'goals_for'       => 'integer',
        'goals_against'   => 'integer',
        'goal_difference' => 'integer',
        'points'          => 'integer',
        'position'        => 'integer',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function getQualificationLabelAttribute(): string
    {
        return match($this->qualification) {
            'trophy'  => '🏆 Trofi',
            'cup'     => '🥈 Piala',
            'none'    => '—',
            'pending' => 'Belum Selesai',
            default   => $this->qualification,
        };
    }

    // Recalculate stats after a match result is entered
    public function recalculate(): void
    {
        $this->goal_difference = $this->goals_for - $this->goals_against;
        $this->points = ($this->won * 3) + ($this->drawn * 1);
        $this->save();
    }
}
