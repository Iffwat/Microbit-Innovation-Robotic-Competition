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
        'home_score', 'away_score', 
        'obstacle_time_ms_1', 'obstacle_penalties_1', 
        'obstacle_time_ms_2', 'obstacle_penalties_2', 
        'winner_team_id',
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
        return $this->home_score . ' - ' . $this->away_score;
    }

    public function getFormattedObstacleTimeAttribute(): string
    {
        if ($this->home_score === null) return '-';
        
        $totalMs = $this->home_score;
        $minutes = floor($totalMs / 60000);
        $seconds = floor(($totalMs % 60000) / 1000);
        $ms = $totalMs % 1000;
        
        $timeStr = sprintf('%02d:%02d.%03d', $minutes, $seconds, $ms);
        
        if ($this->obstacle_penalties > 0) {
            $timeStr .= ' (+'.$this->obstacle_penalties.' P)';
        }
        
        return $timeStr;
    }

    // Determine winner from scores and update standings
    public function resolveResult(): void
    {
        $isObstacle = $this->group && $this->group->game_type === 'obstacle';
        
        if (!$isObstacle && ($this->home_score === null || $this->away_score === null)) {
            return;
        }
        
        if ($isObstacle && $this->home_score === null) {
            return;
        }

        if (!$isObstacle) {
            if ($this->home_score > $this->away_score) {
                $this->winner_team_id = $this->home_team_id;
            } elseif ($this->away_score > $this->home_score) {
                $this->winner_team_id = $this->away_team_id;
            } else {
                $this->winner_team_id = null; // Draw
            }
        } else {
            // For obstacle, there is no direct winner_team_id in group stage
            $this->winner_team_id = null;
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
        
        if (!$homeGT) return;

        $isObstacle = $this->group && $this->group->game_type === 'obstacle';

        if ($isObstacle) {
            $homeGT->played++;
            $homeGT->goals_for = $this->home_score ?? 0; // Store best time here for easy sorting later
            $homeGT->recalculate();
            return;
        }

        $awayGT = GroupTeam::where('group_id', $this->group_id)
                           ->where('team_id', $this->away_team_id)->first();

        if (!$awayGT) return;

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
