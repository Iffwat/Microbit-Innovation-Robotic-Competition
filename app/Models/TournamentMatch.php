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

    public function isKnockout(): bool
    {
        return in_array($this->stage, ['trophy_knockout', 'cup_knockout']);
    }

    // Determine winner from scores and update standings / advance knockout winners
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

        // 1. Update group standings if group match
        if ($this->stage === 'group' && $this->group_id) {
            $this->updateGroupStandings();
        }

        // 2. Auto-advance winner to the next round if knockout match
        if ($this->isKnockout() && $this->winner_team_id) {
            $this->advanceKnockoutWinner();
        }
    }

    public function advanceKnockoutWinner(): void
    {
        if (!$this->winner_team_id || !$this->category_id || !$this->stage) {
            return;
        }

        $roundFlow = [
            'Pusingan ke-32' => 'Pusingan ke-16',
            'Pusingan 32'    => 'Pusingan ke-16',
            'Pusingan ke-16' => 'Suku Akhir',
            'Pusingan 16'    => 'Suku Akhir',
            'Suku Akhir'     => 'Separuh Akhir',
        ];

        $pos = (int)$this->bracket_position;
        $winnerId = $this->winner_team_id;
        $loserId = ($this->winner_team_id == $this->home_team_id) ? $this->away_team_id : $this->home_team_id;

        // CASE 1: Standard Feeder Rounds (P32 -> P16 -> Suku Akhir -> Separuh Akhir)
        if (isset($roundFlow[$this->round_name])) {
            $nextRoundName = $roundFlow[$this->round_name];
            $nextPos = (int)ceil($pos / 2);
            $isHomeSlot = ($pos % 2 !== 0);

            $nextMatch = self::where('category_id', $this->category_id)
                ->where('stage', $this->stage)
                ->where('round_name', $nextRoundName)
                ->where('bracket_position', $nextPos)
                ->first();

            if ($nextMatch) {
                if ($isHomeSlot) {
                    $nextMatch->home_team_id = $winnerId;
                } else {
                    $nextMatch->away_team_id = $winnerId;
                }
                $nextMatch->save();
            }

            // 🎖️ If 5th place playoff bracket exists, advance Suku Akhir loser to 'Separuh Akhir Tempat Ke-5'
            if (in_array($this->round_name, ['Suku Akhir', 'Quarter Final']) && $loserId) {
                $sf5Pos = (int)ceil($pos / 2);
                $sf5HomeSlot = ($pos % 2 !== 0);

                $sf5Match = self::where('category_id', $this->category_id)
                    ->where('stage', $this->stage)
                    ->where('round_name', 'Separuh Akhir Tempat Ke-5')
                    ->where('bracket_position', $sf5Pos)
                    ->first();

                if ($sf5Match) {
                    if ($sf5HomeSlot) {
                        $sf5Match->home_team_id = $loserId;
                    } else {
                        $sf5Match->away_team_id = $loserId;
                    }
                    $sf5Match->save();
                }
            }
        }
        // CASE 2: Semi-Finals (Separuh Akhir -> Akhir & Penentuan Tempat Ke-3)
        elseif (in_array($this->round_name, ['Separuh Akhir', 'Semi Final'])) {
            $isHomeSlot = ($pos === 1); // SF Match 1 -> Home slot, SF Match 2 -> Away slot

            // 🏆 Winner goes to FINAL (Akhir)
            $finalMatch = self::where('category_id', $this->category_id)
                ->where('stage', $this->stage)
                ->where('round_name', 'Akhir')
                ->where('bracket_position', 1)
                ->first();

            if ($finalMatch) {
                if ($isHomeSlot) {
                    $finalMatch->home_team_id = $winnerId;
                } else {
                    $finalMatch->away_team_id = $winnerId;
                }
                $finalMatch->save();
            }

            // 🥉 Loser goes to 3RD PLACE (Penentuan Tempat Ke-3)
            if ($loserId) {
                $thirdMatch = self::where('category_id', $this->category_id)
                    ->where('stage', $this->stage)
                    ->where('round_name', 'Penentuan Tempat Ke-3')
                    ->where('bracket_position', 1)
                    ->first();

                if ($thirdMatch) {
                    if ($isHomeSlot) {
                        $thirdMatch->home_team_id = $loserId;
                    } else {
                        $thirdMatch->away_team_id = $loserId;
                    }
                    $thirdMatch->save();
                }
            }
        }
        // CASE 3: 5th Place Semi-Finals (Separuh Akhir Tempat Ke-5 -> Penentuan Tempat Ke-5)
        elseif ($this->round_name === 'Separuh Akhir Tempat Ke-5') {
            $isHomeSlot = ($pos === 1); // SF5 Match 1 -> Home slot, SF5 Match 2 -> Away slot

            // 🎖️ Winner goes to 5th PLACE FINAL (Penentuan Tempat Ke-5)
            $fifthMatch = self::where('category_id', $this->category_id)
                ->where('stage', $this->stage)
                ->where('round_name', 'Penentuan Tempat Ke-5')
                ->where('bracket_position', 1)
                ->first();

            if ($fifthMatch) {
                if ($isHomeSlot) {
                    $fifthMatch->home_team_id = $winnerId;
                } else {
                    $fifthMatch->away_team_id = $winnerId;
                }
                $fifthMatch->save();
            }
        }
    }

    public function clearPromotedKnockoutSlot(): void
    {
        if (!$this->category_id || !$this->stage) return;

        $roundFlow = [
            'Pusingan ke-32' => 'Pusingan ke-16',
            'Pusingan 32'    => 'Pusingan ke-16',
            'Pusingan ke-16' => 'Suku Akhir',
            'Pusingan 16'    => 'Suku Akhir',
            'Suku Akhir'     => 'Separuh Akhir',
        ];

        $pos = (int)$this->bracket_position;

        if (isset($roundFlow[$this->round_name])) {
            $nextRoundName = $roundFlow[$this->round_name];
            $nextPos = (int)ceil($pos / 2);
            $isHomeSlot = ($pos % 2 !== 0);

            $nextMatch = self::where('category_id', $this->category_id)
                ->where('stage', $this->stage)
                ->where('round_name', $nextRoundName)
                ->where('bracket_position', $nextPos)
                ->first();

            if ($nextMatch && $nextMatch->status === 'scheduled') {
                if ($isHomeSlot) {
                    $nextMatch->home_team_id = null;
                } else {
                    $nextMatch->away_team_id = null;
                }
                $nextMatch->save();
            }

            // Also clear loser from Separuh Akhir Tempat Ke-5 if applicable
            if (in_array($this->round_name, ['Suku Akhir', 'Quarter Final'])) {
                $sf5Match = self::where('category_id', $this->category_id)
                    ->where('stage', $this->stage)
                    ->where('round_name', 'Separuh Akhir Tempat Ke-5')
                    ->where('bracket_position', $nextPos)
                    ->first();

                if ($sf5Match && $sf5Match->status === 'scheduled') {
                    if ($isHomeSlot) {
                        $sf5Match->home_team_id = null;
                    } else {
                        $sf5Match->away_team_id = null;
                    }
                    $sf5Match->save();
                }
            }
        } elseif (in_array($this->round_name, ['Separuh Akhir', 'Semi Final'])) {
            $isHomeSlot = ($pos === 1);

            $finalMatch = self::where('category_id', $this->category_id)
                ->where('stage', $this->stage)
                ->where('round_name', 'Akhir')
                ->where('bracket_position', 1)
                ->first();

            if ($finalMatch && $finalMatch->status === 'scheduled') {
                if ($isHomeSlot) {
                    $finalMatch->home_team_id = null;
                } else {
                    $finalMatch->away_team_id = null;
                }
                $finalMatch->save();
            }

            $thirdMatch = self::where('category_id', $this->category_id)
                ->where('stage', $this->stage)
                ->where('round_name', 'Penentuan Tempat Ke-3')
                ->where('bracket_position', 1)
                ->first();

            if ($thirdMatch && $thirdMatch->status === 'scheduled') {
                if ($isHomeSlot) {
                    $thirdMatch->home_team_id = null;
                } else {
                    $thirdMatch->away_team_id = null;
                }
                $thirdMatch->save();
            }
        } elseif ($this->round_name === 'Separuh Akhir Tempat Ke-5') {
            $isHomeSlot = ($pos === 1);

            $fifthMatch = self::where('category_id', $this->category_id)
                ->where('stage', $this->stage)
                ->where('round_name', 'Penentuan Tempat Ke-5')
                ->where('bracket_position', 1)
                ->first();

            if ($fifthMatch && $fifthMatch->status === 'scheduled') {
                if ($isHomeSlot) {
                    $fifthMatch->home_team_id = null;
                } else {
                    $fifthMatch->away_team_id = null;
                }
                $fifthMatch->save();
            }
        }
    }

    /**
     * Self-healing sync: ensures 5th place classification matches exist and
     * auto-promotes losers from already-completed Suku Akhir matches.
     */
    public static function syncFifthPlaceBracket(int $categoryId, ?string $stage = null): void
    {
        $stages = $stage ? [$stage] : ['trophy_knockout', 'cup_knockout'];

        foreach ($stages as $stg) {
            $hasQF = self::where('category_id', $categoryId)
                ->where('stage', $stg)
                ->whereIn('round_name', ['Suku Akhir', 'Quarter Final'])
                ->exists();

            if (!$hasQF) {
                continue;
            }

            // 1. Ensure 2 placeholder matches for 'Separuh Akhir Tempat Ke-5' exist
            for ($m = 1; $m <= 2; $m++) {
                $sf5 = self::where('category_id', $categoryId)
                    ->where('stage', $stg)
                    ->where('round_name', 'Separuh Akhir Tempat Ke-5')
                    ->where('bracket_position', $m)
                    ->first();

                if (!$sf5) {
                    self::create([
                        'category_id'      => $categoryId,
                        'stage'            => $stg,
                        'round_name'       => 'Separuh Akhir Tempat Ke-5',
                        'bracket_position' => $m,
                        'home_team_id'     => null,
                        'away_team_id'     => null,
                        'status'           => 'scheduled',
                        'field_number'     => $m + 2, // Field 3 or 4
                    ]);
                }
            }

            // 2. Ensure 1 placeholder match for 'Penentuan Tempat Ke-5' exists
            $p5 = self::where('category_id', $categoryId)
                ->where('stage', $stg)
                ->where('round_name', 'Penentuan Tempat Ke-5')
                ->where('bracket_position', 1)
                ->first();

            if (!$p5) {
                self::create([
                    'category_id'      => $categoryId,
                    'stage'            => $stg,
                    'round_name'       => 'Penentuan Tempat Ke-5',
                    'bracket_position' => 1,
                    'home_team_id'     => null,
                    'away_team_id'     => null,
                    'status'           => 'scheduled',
                    'field_number'     => 3,
                ]);
            }

            // 3. For any already-completed Suku Akhir matches, populate the losers into SF5!
            $qfMatches = self::where('category_id', $categoryId)
                ->where('stage', $stg)
                ->whereIn('round_name', ['Suku Akhir', 'Quarter Final'])
                ->get();

            foreach ($qfMatches as $qf) {
                if ($qf->winner_team_id) {
                    $pos = (int)$qf->bracket_position;
                    $loserId = ($qf->winner_team_id == $qf->home_team_id) ? $qf->away_team_id : $qf->home_team_id;

                    if ($loserId) {
                        $sf5Pos = (int)ceil($pos / 2);
                        $sf5HomeSlot = ($pos % 2 !== 0);

                        $sf5Match = self::where('category_id', $categoryId)
                            ->where('stage', $stg)
                            ->where('round_name', 'Separuh Akhir Tempat Ke-5')
                            ->where('bracket_position', $sf5Pos)
                            ->first();

                        if ($sf5Match && $sf5Match->status === 'scheduled') {
                            if ($sf5HomeSlot && !$sf5Match->home_team_id) {
                                $sf5Match->home_team_id = $loserId;
                                $sf5Match->save();
                            } elseif (!$sf5HomeSlot && !$sf5Match->away_team_id) {
                                $sf5Match->away_team_id = $loserId;
                                $sf5Match->save();
                            }
                        }
                    }
                }
            }

            // 4. For any already-completed SF Ke-5 matches, populate the winners into Penentuan Tempat Ke-5!
            $sf5Matches = self::where('category_id', $categoryId)
                ->where('stage', $stg)
                ->where('round_name', 'Separuh Akhir Tempat Ke-5')
                ->get();

            foreach ($sf5Matches as $sf5) {
                if ($sf5->winner_team_id) {
                    $pos = (int)$sf5->bracket_position;
                    $isHomeSlot = ($pos === 1);

                    $fifthMatch = self::where('category_id', $categoryId)
                        ->where('stage', $stg)
                        ->where('round_name', 'Penentuan Tempat Ke-5')
                        ->where('bracket_position', 1)
                        ->first();

                    if ($fifthMatch && $fifthMatch->status === 'scheduled') {
                        if ($isHomeSlot && !$fifthMatch->home_team_id) {
                            $fifthMatch->home_team_id = $sf5->winner_team_id;
                            $fifthMatch->save();
                        } elseif (!$isHomeSlot && !$fifthMatch->away_team_id) {
                            $fifthMatch->away_team_id = $sf5->winner_team_id;
                            $fifthMatch->save();
                        }
                    }
                }
            }
        }
    }

    public function updateGroupStandings(): void
    {
        if (!$this->group_id) return;
        self::recalculateGroupStandings((int)$this->group_id);
    }

    public static function recalculateGroupStandings(int $groupId): void
    {
        $group = Group::find($groupId);
        if (!$group) return;

        $isObstacle = $group->game_type === 'obstacle';
        $groupTeams = GroupTeam::where('group_id', $groupId)->get();

        if ($isObstacle) {
            foreach ($groupTeams as $gt) {
                $completedMatches = self::where('group_id', $groupId)
                    ->where('home_team_id', $gt->team_id)
                    ->where('status', 'completed')
                    ->whereNotNull('home_score')
                    ->get();

                $played = $completedMatches->count();
                $bestTime = $completedMatches->min('home_score') ?? 0;

                $gt->update([
                    'played' => $played,
                    'won' => 0,
                    'drawn' => 0,
                    'lost' => 0,
                    'goals_for' => $bestTime,
                    'goals_against' => 0,
                    'goal_difference' => 0,
                    'points' => 0,
                ]);
            }
            return;
        }

        // For soccer: recalculate cleanly from all completed regular matches in this group
        // (Excludes Play-off matches so they act purely as head-to-head tie-breakers without corrupting regular league GD/Pts)
        $completedMatches = self::where('group_id', $groupId)
            ->where('status', 'completed')
            ->whereNotNull('home_score')
            ->whereNotNull('away_score')
            ->where('round_name', 'not like', '%Play-off%')
            ->where('round_name', 'not like', '%Penentuan%')
            ->get();

        foreach ($groupTeams as $gt) {
            $teamId = $gt->team_id;
            $played = 0;
            $won = 0;
            $drawn = 0;
            $lost = 0;
            $goalsFor = 0;
            $goalsAgainst = 0;

            foreach ($completedMatches as $m) {
                if ($m->home_team_id == $teamId) {
                    $played++;
                    $goalsFor += (int)$m->home_score;
                    $goalsAgainst += (int)$m->away_score;

                    if ($m->home_score > $m->away_score) {
                        $won++;
                    } elseif ($m->home_score < $m->away_score) {
                        $lost++;
                    } else {
                        $drawn++;
                    }
                } elseif ($m->away_team_id == $teamId) {
                    $played++;
                    $goalsFor += (int)$m->away_score;
                    $goalsAgainst += (int)$m->home_score;

                    if ($m->away_score > $m->home_score) {
                        $won++;
                    } elseif ($m->away_score < $m->home_score) {
                        $lost++;
                    } else {
                        $drawn++;
                    }
                }
            }

            $goalDiff = $goalsFor - $goalsAgainst;
            $points = ($won * 3) + ($drawn * 1);

            $gt->update([
                'played' => $played,
                'won' => $won,
                'drawn' => $drawn,
                'lost' => $lost,
                'goals_for' => $goalsFor,
                'goals_against' => $goalsAgainst,
                'goal_difference' => $goalDiff,
                'points' => $points,
            ]);
        }
    }
}
