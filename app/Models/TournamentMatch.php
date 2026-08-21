<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TournamentMatch extends Model
{
    protected $table = 'matches';

    protected $fillable = [
        'category_id', 'stage', 'group_id', 'round_name', 'bracket_position',
        'home_team_id', 'away_team_id',
        'home_score', 'away_score', 'winner_team_id',
        'status', 'field_number', 'scheduled_time', 'completed_at', 'notes',
    ];

    protected $casts = [
        'home_score'     => 'integer',
        'away_score'     => 'integer',
        'scheduled_time' => 'datetime',
        'completed_at'   => 'datetime',
    ];

    // Relations
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function homeTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'home_team_id');
    }

    public function awayTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'away_team_id');
    }

    public function winner(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'winner_team_id');
    }

    // Helpers
    public function getStageLabelAttribute(): string
    {
        return match($this->stage) {
            'group'            => 'Peringkat Kumpulan',
            'trophy_knockout'  => 'Pusingan Trofi',
            'cup_knockout'     => 'Pusingan Piala',
            default            => $this->stage,
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'scheduled'   => 'Dijadualkan',
            'in_progress' => 'Sedang Berlangsung',
            'completed'   => 'Selesai',
            'walkover'    => 'Walkover',
            'bye'         => 'Bye',
            default       => $this->status,
        };
    }

    public function isCompleted(): bool
    {
        return in_array($this->status, ['completed', 'walkover', 'bye']);
    }

    public function getScoreDisplayAttribute(): string
    {
        if ($this->home_score === null) return 'vs';
        return $this->home_score . ' – ' . $this->away_score;
    }

    // Determine winner from scores and update standings
    public function resolveResult(): void
    {
        if ($this->home_score === null || $this->away_score === null) return;

        if ($this->home_score > $this->away_score) {
            $this->winner_team_id = $this->home_team_id;
        } elseif ($this->away_score > $this->home_score) {
            $this->winner_team_id = $this->away_team_id;
        } else {
            $this->winner_team_id = null; // Draw
        }

        $this->status = 'completed';
        $this->completed_at = now();
        $this->save();

        // Update group standings if group match
        if ($this->stage === 'group' && $this->group_id) {
            $this->updateGroupStandings();
        }
    }

    private function updateGroupStandings(): void
    {
        $homeGT = GroupTeam::where('group_id', $this->group_id)
                           ->where('team_id', $this->home_team_id)->first();
        $awayGT = GroupTeam::where('group_id', $this->group_id)
                           ->where('team_id', $this->away_team_id)->first();

        if (!$homeGT || !$awayGT) return;

        $homeGT->played++;
        $awayGT->played++;
        $homeGT->goals_for      += $this->home_score;
        $homeGT->goals_against  += $this->away_score;
        $awayGT->goals_for      += $this->away_score;
        $awayGT->goals_against  += $this->home_score;

        if ($this->home_score > $this->away_score) {
            $homeGT->won++;
            $awayGT->lost++;
        } elseif ($this->away_score > $this->home_score) {
            $awayGT->won++;
            $homeGT->lost++;
        } else {
            $homeGT->drawn++;
            $awayGT->drawn++;
        }

        $homeGT->recalculate();
        $awayGT->recalculate();
    }
}
