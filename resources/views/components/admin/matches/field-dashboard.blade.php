<?php
use App\Models\TournamentMatch;
use Livewire\Component;

new class extends Component
{
    public $activeField = null;

    // Sorting & Filtering State
    public $sortBy = 'default'; // 'default', 'interleaved_round', 'group_asc', 'category_asc', 'status_asc'
    public $filterCategory = '';
    public $filterGroup = '';
    public $filterStatus = '';

    // Scoring Modal State
    public $scoringMatchId = null;
    
    // Soccer Scoring
    public $soccerHomeScore = 0;
    public $soccerAwayScore = 0;
    
    // Obstacle Scoring
    public $obsMinutes1 = 0, $obsSeconds1 = 0, $obsMilliseconds1 = 0, $obsPenalties1 = 0;
    public $obsMinutes2 = 0, $obsSeconds2 = 0, $obsMilliseconds2 = 0, $obsPenalties2 = 0;

    // --- Field Transfer State ---
    public $movingMatchId = null;
    public $targetField = null;
    public $showMoveModal = false;

    // Bulk Field Transfer State
    public $bulkMode = false;
    public $selectedMatchIds = [];
    public $bulkTargetField = null;

    // Pull to Empty Field State
    public $showPullModal = false;
    public $pullSelectedMatches = [];
    public $pullFilterField = '';

    public function mount()
    {
        $this->activeField = session('volunteer_field', null);
        if ($this->activeField) {
            $this->sortBy = \Illuminate\Support\Facades\Cache::get('field_sort_' . $this->activeField, str_contains($this->activeField, 'Sky Soccer') ? 'interleaved_round' : 'default');
        }
    }

    public function selectField($field)
    {
        $this->activeField = $field;
        $this->filterCategory = '';
        $this->filterGroup = '';
        $this->filterStatus = '';
        $this->sortBy = \Illuminate\Support\Facades\Cache::get('field_sort_' . $field, str_contains($field, 'Sky Soccer') ? 'interleaved_round' : 'default');
        session(['volunteer_field' => $field]);
    }

    public function clearField()
    {
        $this->activeField = null;
        $this->filterCategory = '';
        $this->filterGroup = '';
        $this->filterStatus = '';
        $this->sortBy = 'default';
        session()->forget('volunteer_field');
    }

    public function updatedSortBy($value)
    {
        if (session('auth_role') !== 'master') {
            $this->sortBy = \Illuminate\Support\Facades\Cache::get('field_sort_' . $this->activeField, str_contains((string)$this->activeField, 'Sky Soccer') ? 'interleaved_round' : 'default');
            return;
        }

        if ($this->activeField) {
            \Illuminate\Support\Facades\Cache::forever('field_sort_' . $this->activeField, $value);
        }
    }

    public function resetFilters()
    {
        $this->filterCategory = '';
        $this->filterGroup = '';
        $this->filterStatus = '';
        if (session('auth_role') === 'master') {
            $this->sortBy = (str_contains((string)$this->activeField, 'Sky Soccer')) ? 'interleaved_round' : 'default';
            if ($this->activeField) {
                \Illuminate\Support\Facades\Cache::forever('field_sort_' . $this->activeField, $this->sortBy);
            }
        } else {
            $this->sortBy = \Illuminate\Support\Facades\Cache::get('field_sort_' . $this->activeField, str_contains((string)$this->activeField, 'Sky Soccer') ? 'interleaved_round' : 'default');
        }
    }

    public function autoInterleaveSkySoccer()
    {
        if (session('auth_role') !== 'master') {
            return;
        }
        if (!$this->activeField) return;

        $matches = TournamentMatch::where('field_number', $this->activeField)
            ->where('status', 'scheduled')
            ->get();

        if ($matches->isEmpty()) {
            $this->js("alert('Tiada perlawanan menunggu untuk disusun semula.');");
            return;
        }

        $sorted = $matches->sort(function ($a, $b) {
            // 1. Round Number FIRST (P1, P2, P3...) so each round contains both categories
            preg_match('/P(\d+)/i', (string)$a->round_name, $matchA);
            preg_match('/P(\d+)/i', (string)$b->round_name, $matchB);
            $rA = isset($matchA[1]) ? (int)$matchA[1] : 999;
            $rB = isset($matchB[1]) ? (int)$matchB[1] : 999;
            if ($rA !== $rB) return $rA <=> $rB;

            // 2. Category SECOND (U12 & PPKI first, then continue with U15 & U20)
            $catA = $a->category->sort_order ?? $a->category_id;
            $catB = $b->category->sort_order ?? $b->category_id;
            if ($catA !== $catB) return $catA <=> $catB;

            // 3. Group Letter THIRD (Kumpulan A, Kumpulan B...)
            $grpA = $a->group->group_letter ?? $a->group->group_name ?? '';
            $grpB = $b->group->group_letter ?? $b->group->group_name ?? '';
            if ($grpA !== $grpB) return strcmp($grpA, $grpB);

            return $a->id <=> $b->id;
        })->values();

        $baseTime = now()->startOfDay()->addHours(8);
        foreach ($sorted as $idx => $m) {
            $m->scheduled_time = (clone $baseTime)->addMinutes($idx * 5);
            $m->save();
        }

        $this->sortBy = 'interleaved_round';
        \Illuminate\Support\Facades\Cache::forever('field_sort_' . $this->activeField, 'interleaved_round');
        $this->js("alert('Perlawanan telah berjaya disusun semula secara berselang-seli mengikut pusingan!');");
    }


    public function getFieldsProperty()
    {
        return TournamentMatch::whereNotNull('field_number')
            ->select('field_number')
            ->distinct()
            ->orderByRaw('LENGTH(field_number)')
            ->orderBy('field_number')
            ->pluck('field_number');
    }

    public function getCategoriesForFieldProperty()
    {
        if (!$this->activeField) return collect();

        $catIds = TournamentMatch::where('field_number', $this->activeField)
            ->distinct()
            ->pluck('category_id');

        return \App\Models\Category::whereIn('id', $catIds)->orderBy('sort_order')->get();
    }

    public function getGroupsForFieldProperty()
    {
        if (!$this->activeField) return collect();

        $query = TournamentMatch::where('field_number', $this->activeField)->whereNotNull('group_id');
        if ($this->filterCategory) {
            $query->where('category_id', $this->filterCategory);
        }
        $groupIds = $query->distinct()->pluck('group_id');

        return \App\Models\Group::whereIn('id', $groupIds)->orderBy('group_name')->get();
    }

    public function getMatchesProperty()
    {
        if (!$this->activeField) return collect();

        if (session('auth_role') !== 'master') {
            $this->sortBy = \Illuminate\Support\Facades\Cache::get('field_sort_' . $this->activeField, str_contains((string)$this->activeField, 'Sky Soccer') ? 'interleaved_round' : 'default');
        }

        $query = TournamentMatch::with(['homeTeam', 'awayTeam', 'category', 'group'])
            ->where('field_number', $this->activeField);

        if ($this->filterCategory) {
            $query->where('category_id', $this->filterCategory);
        }

        if ($this->filterGroup) {
            $query->where('group_id', $this->filterGroup);
        }

        if ($this->filterStatus) {
            $query->where('status', $this->filterStatus);
        }

        $matches = $query->get();

        if ($this->sortBy === 'interleaved_round') {
            return $matches->sort(function ($a, $b) {
                $statusOrder = ['in_progress' => 1, 'scheduled' => 2, 'completed' => 3, 'walkover' => 4, 'bye' => 4];
                $sA = $statusOrder[$a->status] ?? 5;
                $sB = $statusOrder[$b->status] ?? 5;
                if ($sA !== $sB) return $sA <=> $sB;

                // 2. Round Number FIRST (P1, P2, P3...) so each round contains both categories
                preg_match('/P(\d+)/i', (string)$a->round_name, $matchA);
                preg_match('/P(\d+)/i', (string)$b->round_name, $matchB);
                $rA = isset($matchA[1]) ? (int)$matchA[1] : 999;
                $rB = isset($matchB[1]) ? (int)$matchB[1] : 999;
                if ($rA !== $rB) return $rA <=> $rB;

                // 3. Category SECOND (U12 & PPKI first, then continue with U15 & U20)
                $catA = $a->category->sort_order ?? $a->category_id;
                $catB = $b->category->sort_order ?? $b->category_id;
                if ($catA !== $catB) return $catA <=> $catB;

                // 4. Group Letter THIRD (Kumpulan A, Kumpulan B...)
                $grpA = $a->group->group_letter ?? $a->group->group_name ?? '';
                $grpB = $b->group->group_letter ?? $b->group->group_name ?? '';
                if ($grpA !== $grpB) return strcmp($grpA, $grpB);

                if ($a->scheduled_time && $b->scheduled_time) {
                    return $a->scheduled_time <=> $b->scheduled_time;
                }

                return $a->id <=> $b->id;
            })->values();
        } elseif ($this->sortBy === 'group_asc') {
            return $matches->sort(function ($a, $b) {
                $statusOrder = ['in_progress' => 1, 'scheduled' => 2, 'completed' => 3, 'walkover' => 4, 'bye' => 4];
                $sA = $statusOrder[$a->status] ?? 5;
                $sB = $statusOrder[$b->status] ?? 5;
                if ($sA !== $sB) return $sA <=> $sB;

                $grpA = $a->group->group_name ?? '';
                $grpB = $b->group->group_name ?? '';
                if ($grpA !== $grpB) return strcmp($grpA, $grpB);

                preg_match('/P(\d+)/i', (string)$a->round_name, $matchA);
                preg_match('/P(\d+)/i', (string)$b->round_name, $matchB);
                $rA = isset($matchA[1]) ? (int)$matchA[1] : 999;
                $rB = isset($matchB[1]) ? (int)$matchB[1] : 999;
                if ($rA !== $rB) return $rA <=> $rB;

                return $a->id <=> $b->id;
            })->values();
        } elseif ($this->sortBy === 'category_asc') {
            return $matches->sort(function ($a, $b) {
                $statusOrder = ['in_progress' => 1, 'scheduled' => 2, 'completed' => 3, 'walkover' => 4, 'bye' => 4];
                $sA = $statusOrder[$a->status] ?? 5;
                $sB = $statusOrder[$b->status] ?? 5;
                if ($sA !== $sB) return $sA <=> $sB;

                $catA = $a->category->sort_order ?? $a->category_id;
                $catB = $b->category->sort_order ?? $b->category_id;
                if ($catA !== $catB) return $catA <=> $catB;

                return $a->id <=> $b->id;
            })->values();
        } elseif ($this->sortBy === 'status_asc') {
            return $matches->sort(function ($a, $b) {
                $statusOrder = ['in_progress' => 1, 'scheduled' => 2, 'completed' => 3, 'walkover' => 4, 'bye' => 4];
                $sA = $statusOrder[$a->status] ?? 5;
                $sB = $statusOrder[$b->status] ?? 5;
                if ($sA !== $sB) return $sA <=> $sB;

                return $a->id <=> $b->id;
            })->values();
        }

        return $matches->sort(function ($a, $b) {
            $statusOrder = ['in_progress' => 1, 'scheduled' => 2, 'completed' => 3, 'walkover' => 4, 'bye' => 4];
            $sA = $statusOrder[$a->status] ?? 5;
            $sB = $statusOrder[$b->status] ?? 5;
            if ($sA !== $sB) return $sA <=> $sB;

            if ($a->scheduled_time && $b->scheduled_time) {
                return $a->scheduled_time <=> $b->scheduled_time;
            }

            return $a->id <=> $b->id;
        })->values();
    }

    public function getScoringMatch()
    {
        return $this->scoringMatchId ? TournamentMatch::with(['homeTeam', 'awayTeam', 'group', 'category'])->find($this->scoringMatchId) : null;
    }

    public function openScoring($matchId)
    {
        $this->scoringMatchId = $matchId;
        $match = $this->getScoringMatch();
        
        if ($match->status === 'scheduled') {
            $match->status = 'in_progress';
            $match->save();
        }

        if ($match->group && $match->group->game_type === 'obstacle') {
            if ($match->status === 'completed' || $match->obstacle_time_ms_1 !== null) {
                $this->obsMinutes1 = floor($match->obstacle_time_ms_1 / 60000);
                $this->obsSeconds1 = floor(($match->obstacle_time_ms_1 % 60000) / 1000);
                $this->obsMilliseconds1 = $match->obstacle_time_ms_1 % 1000;
                $this->obsPenalties1 = $match->obstacle_penalties_1 ?? 0;
            } else {
                $this->obsMinutes1 = 0; $this->obsSeconds1 = 0; $this->obsMilliseconds1 = 0; $this->obsPenalties1 = 0;
            }
            
            if ($match->status === 'completed' && $match->obstacle_time_ms_2 !== null) {
                $this->obsMinutes2 = floor($match->obstacle_time_ms_2 / 60000);
                $this->obsSeconds2 = floor(($match->obstacle_time_ms_2 % 60000) / 1000);
                $this->obsMilliseconds2 = $match->obstacle_time_ms_2 % 1000;
                $this->obsPenalties2 = $match->obstacle_penalties_2 ?? 0;
            } else {
                $this->obsMinutes2 = 0; $this->obsSeconds2 = 0; $this->obsMilliseconds2 = 0; $this->obsPenalties2 = 0;
            }
        } else {
            $this->soccerHomeScore = $match->home_score ?? 0;
            $this->soccerAwayScore = $match->away_score ?? 0;
        }
        
        $this->dispatch('open-scoring-modal');
    }

    public function closeScoring()
    {
        $this->scoringMatchId = null;
        $this->dispatch('close-scoring-modal');
    }

    public function incrementScore($team) {
        if ($team === 'home') $this->soccerHomeScore++;
        if ($team === 'away') $this->soccerAwayScore++;
        $this->syncLiveSoccerScore();
    }

    public function decrementScore($team) {
        if ($team === 'home' && $this->soccerHomeScore > 0) $this->soccerHomeScore--;
        if ($team === 'away' && $this->soccerAwayScore > 0) $this->soccerAwayScore--;
        $this->syncLiveSoccerScore();
    }

    private function syncLiveSoccerScore() {
        if ($match = $this->getScoringMatch()) {
            if ($match->status !== 'completed') {
                $match->update([
                    'home_score' => $this->soccerHomeScore,
                    'away_score' => $this->soccerAwayScore
                ]);
            }
        }
    }

    public function incrementPenalty($run) { 
        if ($run === 1) $this->obsPenalties1++; else $this->obsPenalties2++; 
    }
    public function decrementPenalty($run) { 
        if ($run === 1 && $this->obsPenalties1 > 0) $this->obsPenalties1--; 
        if ($run === 2 && $this->obsPenalties2 > 0) $this->obsPenalties2--; 
    }

    public function saveScore()
    {
        $match = $this->getScoringMatch();
        if (!$match) return;

        if ($match->group && $match->group->game_type === 'obstacle') {
            // Run 1
            $min1 = (int)($this->obsMinutes1 ?: 0); $sec1 = (int)($this->obsSeconds1 ?: 0); $ms1 = (int)($this->obsMilliseconds1 ?: 0); $pen1 = (int)($this->obsPenalties1 ?: 0);
            $baseMs1 = ($min1 * 60000) + ($sec1 * 1000) + $ms1;
            $totalMs1 = $baseMs1 > 0 ? $baseMs1 + ($pen1 * 1000) : null;

            // Run 2
            $min2 = (int)($this->obsMinutes2 ?: 0); $sec2 = (int)($this->obsSeconds2 ?: 0); $ms2 = (int)($this->obsMilliseconds2 ?: 0); $pen2 = (int)($this->obsPenalties2 ?: 0);
            $baseMs2 = ($min2 * 60000) + ($sec2 * 1000) + $ms2;
            $totalMs2 = $baseMs2 > 0 ? $baseMs2 + ($pen2 * 1000) : null;

            $match->obstacle_time_ms_1 = $baseMs1 > 0 ? $baseMs1 : null;
            $match->obstacle_penalties_1 = $pen1;
            $match->obstacle_time_ms_2 = $baseMs2 > 0 ? $baseMs2 : null;
            $match->obstacle_penalties_2 = $pen2;
            
            if ($totalMs1 && $totalMs2) {
                $match->home_score = min($totalMs1, $totalMs2);
            } elseif ($totalMs1) {
                $match->home_score = $totalMs1;
            } elseif ($totalMs2) {
                $match->home_score = $totalMs2;
            } else {
                $match->home_score = null;
            }
            $match->away_score = null;
        } else {
            if ($this->soccerHomeScore === $this->soccerAwayScore) {
                $this->js("alert('Perhatian: Skor seri (" . $this->soccerHomeScore . " - " . $this->soccerAwayScore . ") tidak dibenarkan! Semua perlawanan mesti ada pemenang. Sila tentukan pemenang penalti / sudden death di padang.');");
                return;
            }
            $match->home_score = $this->soccerHomeScore;
            $match->away_score = $this->soccerAwayScore;
        }

        $match->resolveResult();
        
        session()->flash('success', 'Keputusan berjaya direkodkan!');
        $this->closeScoring();
    }
    
    public function resetMatch($matchId)
    {
        $match = TournamentMatch::find($matchId);
        if ($match) {
            $groupId = $match->group_id;
            $match->update([
                'status' => 'scheduled',
                'home_score' => null,
                'away_score' => null,
                'obstacle_time_ms_1' => null,
                'obstacle_penalties_1' => null,
                'obstacle_time_ms_2' => null,
                'obstacle_penalties_2' => null,
                'winner_team_id' => null,
                'completed_at' => null
            ]);
            if ($match->stage === 'group' && $groupId) {
                TournamentMatch::recalculateGroupStandings($groupId);
            } elseif ($match->isKnockout()) {
                $match->clearPromotedKnockoutSlot();
            }
            session()->flash('success', 'Perlawanan telah di-reset untuk Rematch.');
            $this->closeScoring();
        }
    }

    public function callTeam($matchId)
    {
        $calls = \Illuminate\Support\Facades\Cache::get('live_tv_calls', []);
        
        // Remove expired calls (older than 15 seconds)
        $calls = array_filter($calls, function($call) {
            return $call['expires_at'] > now()->timestamp;
        });
        
        // Add or update the new call
        $calls[$matchId] = [
            'id' => $matchId,
            'expires_at' => now()->addSeconds(15)->timestamp
        ];
        
        \Illuminate\Support\Facades\Cache::put('live_tv_calls', $calls, now()->addMinutes(1));
        session()->flash('success', 'Panggilan siaran langsung telah dihantar ke Live TV!');
    }

    public function openMoveMatchModal($matchId)
    {
        $this->movingMatchId = $matchId;
        $this->targetField = null;
        $this->showMoveModal = true;
    }

    public function closeMoveMatchModal()
    {
        $this->movingMatchId = null;
        $this->targetField = null;
        $this->showMoveModal = false;
    }

    public function moveMatchToField($targetField = null)
    {
        $field = $targetField ?: $this->targetField;
        if (!$field) {
            $this->js("alert('Sila pilih padang sasaran.');");
            return;
        }

        if (!$this->movingMatchId) return;

        $match = TournamentMatch::with(['homeTeam', 'awayTeam'])->find($this->movingMatchId);
        if (!$match) return;

        $match->field_number = $field;
        $match->save();

        $homeName = $match->homeTeam->team_name ?? 'BYE';
        $awayName = $match->awayTeam->team_name ?? 'BYE';
        $fieldLabel = is_numeric($field) ? 'Padang ' . $field : $field;

        session()->flash('success', "Perlawanan ({$homeName} vs {$awayName}) berjaya dipindahkan ke {$fieldLabel}!");

        $this->closeMoveMatchModal();
    }

    public function toggleBulkMode()
    {
        $this->bulkMode = !$this->bulkMode;
        if (!$this->bulkMode) {
            $this->selectedMatchIds = [];
            $this->bulkTargetField = null;
        }
    }

    public function toggleSelectMatch($matchId)
    {
        if (in_array($matchId, $this->selectedMatchIds)) {
            $this->selectedMatchIds = array_values(array_diff($this->selectedMatchIds, [$matchId]));
        } else {
            $this->selectedMatchIds[] = $matchId;
        }
    }

    public function selectAllScheduled()
    {
        $this->selectedMatchIds = $this->matches->where('status', 'scheduled')->pluck('id')->toArray();
    }

    public function clearSelection()
    {
        $this->selectedMatchIds = [];
    }

    public function executeBulkMove()
    {
        if (empty($this->selectedMatchIds)) {
            $this->js("alert('Sila pilih sekurang-kurangnya satu perlawanan.');");
            return;
        }

        if (empty($this->bulkTargetField)) {
            $this->js("alert('Sila pilih padang sasaran.');");
            return;
        }

        $count = count($this->selectedMatchIds);
        TournamentMatch::whereIn('id', $this->selectedMatchIds)
            ->where('status', 'scheduled')
            ->update(['field_number' => $this->bulkTargetField]);

        $fieldLabel = is_numeric($this->bulkTargetField) ? 'Padang ' . $this->bulkTargetField : $this->bulkTargetField;
        session()->flash('success', "Sebanyak {$count} perlawanan berjaya dipindahkan ke {$fieldLabel}!");

        $this->selectedMatchIds = [];
        $this->bulkTargetField = null;
        $this->bulkMode = false;
    }

    public function openPullModal()
    {
        $this->pullSelectedMatches = [];
        $this->pullFilterField = '';
        $this->showPullModal = true;
    }

    public function closePullModal()
    {
        $this->showPullModal = false;
        $this->pullSelectedMatches = [];
    }

    public function pullSingleMatch($matchId)
    {
        if (!$this->activeField) return;

        $match = TournamentMatch::with(['homeTeam', 'awayTeam'])->find($matchId);
        if ($match) {
            $match->field_number = $this->activeField;
            $match->save();

            $homeName = $match->homeTeam->team_name ?? 'BYE';
            $awayName = $match->awayTeam->team_name ?? 'BYE';
            $fieldLabel = is_numeric($this->activeField) ? 'Padang ' . $this->activeField : $this->activeField;
            session()->flash('success', "Perlawanan ({$homeName} vs {$awayName}) berjaya ditarik ke {$fieldLabel}!");
        }
    }

    public function executePullMatches()
    {
        if (!$this->activeField || empty($this->pullSelectedMatches)) {
            $this->js("alert('Sila pilih sekurang-kurangnya satu perlawanan untuk ditarik.');");
            return;
        }

        $count = count($this->pullSelectedMatches);
        TournamentMatch::whereIn('id', $this->pullSelectedMatches)
            ->where('status', 'scheduled')
            ->update(['field_number' => $this->activeField]);

        $fieldLabel = is_numeric($this->activeField) ? 'Padang ' . $this->activeField : $this->activeField;
        session()->flash('success', "Sebanyak {$count} perlawanan berjaya ditarik ke {$fieldLabel}!");

        $this->closePullModal();
    }

    #[\Livewire\Attributes\Computed]
    public function allFieldsSummary()
    {
        $fields = TournamentMatch::whereNotNull('field_number')
            ->select('field_number')
            ->distinct()
            ->orderByRaw('LENGTH(field_number)')
            ->orderBy('field_number')
            ->pluck('field_number');

        $summaries = [];
        foreach ($fields as $f) {
            $scheduled = TournamentMatch::where('field_number', $f)->where('status', 'scheduled')->count();
            $inProgress = TournamentMatch::where('field_number', $f)->where('status', 'in_progress')->count();
            $completed = TournamentMatch::where('field_number', $f)->where('status', 'completed')->count();
            $total = $scheduled + $inProgress + $completed;

            $summaries[] = [
                'field' => $f,
                'scheduled' => $scheduled,
                'in_progress' => $inProgress,
                'completed' => $completed,
                'total' => $total,
                'is_empty' => ($scheduled === 0 && $inProgress === 0),
            ];
        }

        return collect($summaries);
    }

    #[\Livewire\Attributes\Computed]
    public function otherFieldsScheduledMatches()
    {
        if (!$this->activeField) return collect();

        $query = TournamentMatch::with(['homeTeam', 'awayTeam', 'category', 'group'])
            ->where('field_number', '!=', $this->activeField)
            ->where('status', 'scheduled');

        if ($this->pullFilterField) {
            $query->where('field_number', $this->pullFilterField);
        }

        return $query->orderBy('field_number')
            ->orderBy('scheduled_time')
            ->orderBy('id')
            ->get();
    }

    #[\Livewire\Attributes\Computed]
    public function movingMatch()
    {
        return $this->movingMatchId ? TournamentMatch::with(['homeTeam', 'awayTeam', 'category', 'group'])->find($this->movingMatchId) : null;
    }
};
?>

<div class="space-y-6 animate-slide-up">
    @if(session('success'))
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-xl text-sm font-bold flex items-center gap-2">
            <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            {{ session('success') }}
        </div>
    @endif

    @if(!$activeField)
        <div class="text-center mb-8">
            <h2 class="text-2xl font-black text-base-content">{{ __('Pilih Padang Anda') }}</h2>
            <p class="text-base-content/50 mt-1">{{ __('Sila klik pada nombor padang di mana anda bertugas sekarang.') }}</p>
        </div>

        @if($this->fields->isEmpty())
            <div class="bg-amber-50 border border-amber-200 rounded-2xl p-8 text-center text-amber-800">
                <svg class="w-12 h-12 mx-auto mb-3 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <h3 class="font-bold text-lg">{{ __('Tiada Perlawanan Dijadualkan') }}</h3>
                <p class="text-sm mt-1">{{ __('Sila pastikan Admin telah menjana Jadual Perlawanan (Fixtures) di halaman Kumpulan.') }}</p>
                <a href="{{ route('admin.groups.index') }}" class="inline-block mt-4 bg-amber-500 text-white px-5 py-2 rounded-xl text-sm font-bold hover:bg-amber-600 transition-colors">{{ __('Pergi Ke Kumpulan') }}</a>
            </div>
        @else
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-4">
                @foreach($this->fields as $field)
                    <button wire:click="selectField('{{ $field }}')" class="bg-white border-2 border-base-200 hover:border-primary hover:bg-primary/5 rounded-2xl p-6 text-center transition-all group active:scale-95 shadow-sm">
                        <div class="w-12 h-12 mx-auto bg-base-200 group-hover:bg-primary group-hover:text-white rounded-full flex items-center justify-center text-xl font-black text-base-content/50 transition-colors mb-3">
                            @if(is_numeric($field))
                                {{ $field }}
                            @elseif($field === 'Arena Sky Soccer')
                                🚁
                            @elseif(str_contains($field, 'Course'))
                                🏁
                            @else
                                📍
                            @endif
                        </div>
                        @if(is_numeric($field))
                            <h3 class="font-extrabold text-lg text-base-content">{{ __('Padang') }} {{ $field }}</h3>
                        @else
                            <h3 class="font-extrabold text-lg text-base-content">{{ $field }}</h3>
                        @endif
                        @php
                            $pending = \App\Models\TournamentMatch::where('field_number', $field)->where('status', 'scheduled')->count();
                            $inProg = \App\Models\TournamentMatch::where('field_number', $field)->where('status', 'in_progress')->count();
                        @endphp
                        @if($pending === 0 && $inProg === 0)
                            <span class="inline-block mt-1.5 text-[10px] font-black bg-emerald-100 text-emerald-700 px-2.5 py-0.5 rounded-full uppercase tracking-wider">
                                🟢 {{ __('Kosong / Selesai') }}
                            </span>
                        @elseif($inProg > 0)
                            <span class="inline-block mt-1.5 text-[10px] font-black bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full uppercase tracking-wider animate-pulse">
                                🔴 {{ __('Sedang Berlangsung') }}
                            </span>
                            <p class="text-[10px] font-bold text-base-content/50 mt-0.5">{{ $pending }} {{ __('menunggu') }}</p>
                        @else
                            <span class="inline-block mt-1.5 text-[10px] font-black bg-amber-100 text-amber-700 px-2.5 py-0.5 rounded-full uppercase tracking-wider">
                                ⏳ {{ $pending }} {{ __('Menunggu') }}
                            </span>
                        @endif
                    </button>
                @endforeach
            </div>
        @endif
    @else
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 bg-white px-6 py-4 rounded-2xl border border-base-200 shadow-sm">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-primary text-white rounded-xl flex items-center justify-center text-xl font-black shadow-lg shadow-primary/20">
                    @if(is_numeric($activeField))
                        {{ $activeField }}
                    @elseif($activeField === 'Arena Sky Soccer')
                        🚁
                    @elseif(str_contains($activeField, 'Course'))
                        🏁
                    @else
                        📍
                    @endif
                </div>
                <div>
                    @if(is_numeric($activeField))
                        <h2 class="text-xl font-extrabold text-base-content">{{ __('Padang') }} {{ $activeField }}</h2>
                    @else
                        <h2 class="text-xl font-extrabold text-base-content">{{ $activeField }}</h2>
                    @endif
                    <p class="text-xs font-bold text-primary tracking-widest uppercase mt-0.5">{{ __('Dashboard Pengadil') }}</p>
                </div>
            </div>
            <div class="flex items-center gap-2 flex-wrap">
                @php
                    $fieldScheduledCount = $this->matches->where('status', 'scheduled')->count();
                @endphp
                @if($fieldScheduledCount > 0)
                    <button wire:click="toggleBulkMode" 
                            class="inline-flex items-center gap-1.5 {{ $bulkMode ? 'bg-indigo-600 text-white' : 'bg-indigo-50 border-2 border-indigo-200 text-indigo-700 hover:bg-indigo-100' }} text-xs font-bold px-3 py-2 rounded-xl transition-colors shadow-2xs">
                        <span>{{ $bulkMode ? '✕ Tutup Pilihan' : '☑️ Pindah Banyak' }}</span>
                    </button>
                @endif
                <button wire:click="openPullModal" 
                        class="inline-flex items-center gap-1.5 bg-emerald-50 border-2 border-emerald-300 text-emerald-700 hover:bg-emerald-100 text-xs font-bold px-3 py-2 rounded-xl transition-colors shadow-2xs"
                        title="{{ __('Tarik perlawanan dari padang lain yang masih sibuk ke padang ini') }}">
                    <span>📥</span>
                    <span>{{ __('Tarik Perlawanan') }}</span>
                </button>
                @if(session('auth_role') === 'master' && str_contains($activeField, 'Sky Soccer'))
                    <button wire:click="autoInterleaveSkySoccer" 
                            wire:confirm="{{ __('Adakah anda pasti mahu menyusun semula perlawanan Sky Soccer secara berselang-seli mengikut pusingan?') }}"
                            class="inline-flex items-center gap-1.5 bg-violet-50 border-2 border-violet-200 text-violet-700 hover:bg-violet-100 text-sm font-bold px-3.5 py-2 rounded-xl transition-colors shadow-2xs">
                        <span>🔄</span>
                        <span>{{ __('Auto-Interleave Sky Soccer') }}</span>
                    </button>
                @endif
                <button wire:click="clearField" class="bg-base-200 hover:bg-base-300 text-base-content text-sm font-bold px-4 py-2 rounded-xl transition-colors">
                    {{ __('Tukar Padang') }}
                </button>
            </div>
        </div>

        @if($fieldScheduledCount === 0)
            <div class="bg-gradient-to-r from-emerald-50 via-teal-50 to-blue-50 border-2 border-emerald-300 rounded-3xl p-6 text-center space-y-3 shadow-xs animate-fade-in">
                <div class="w-12 h-12 bg-emerald-500 text-white rounded-2xl flex items-center justify-center mx-auto text-xl font-black shadow-md shadow-emerald-500/20">
                    ✓
                </div>
                <div>
                    <h3 class="text-lg font-black text-emerald-900 leading-tight">
                        {{ is_numeric($activeField) ? __('Padang') . ' ' . $activeField : $activeField }} {{ __('Telah Selesai Semua Perlawanan!') }}
                    </h3>
                    <p class="text-xs text-emerald-700 max-w-lg mx-auto mt-1">
                        {{ __('Padang ini kini kosong. Jika padang lain masih mempunyai banyak perlawanan yang menunggu giliran, anda boleh menarik perlawanan ke padang ini untuk mempercepatkan perjalanan pertandingan.') }}
                    </p>
                </div>
                <div>
                    <button wire:click="openPullModal" class="inline-flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs px-5 py-2.5 rounded-xl shadow-md transition-all active:scale-95">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        <span>{{ __('Tarik Perlawanan dari Padang Lain ke Sini') }}</span>
                    </button>
                </div>
            </div>
        @endif

        {{-- Toolbar Susunan & Penapis Perlawanan (Khusus Sky Soccer & Padang Lain) --}}
        <div class="bg-white rounded-2xl border border-base-200 shadow-sm p-4 space-y-3">
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="text-xs font-bold text-base-content/60 uppercase tracking-wider">
                        ⚡ {{ __('Susunan & Penapis Perlawanan') }}
                    </span>
                    <span class="text-xs font-black bg-primary/10 text-primary px-2.5 py-0.5 rounded-full">
                        {{ $this->matches->count() }} {{ __('Perlawanan Dipaparkan') }}
                    </span>
                </div>

                @if(str_contains($activeField, 'Sky Soccer'))
                    <span class="text-[11px] font-bold bg-violet-100 text-violet-700 px-2.5 py-1 rounded-lg border border-violet-200 flex items-center gap-1">
                        🚁 {{ __('Sky Soccer: Pusingan Bergilir (U12 ➔ U15 setiap Pusingan)') }}
                    </span>
                @endif
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3 pt-3 border-t border-base-100">
                {{-- Sort By --}}
                <div class="space-y-1">
                    <label class="text-[10px] font-bold text-base-content/50 uppercase tracking-widest">{{ __('Susun Mengikut:') }}</label>
                    @if(session('auth_role') === 'master')
                        <select wire:model.live="sortBy" class="select select-bordered select-sm w-full rounded-xl text-xs font-semibold bg-white focus:border-primary">
                            <option value="interleaved_round">🔄 {{ __('Selang-Seli Pusingan (U12 ➔ U15 setiap Pusingan)') }}</option>
                            <option value="default">📋 {{ __('Status & Giliran Asal (Lalai)') }}</option>
                            <option value="group_asc">👥 {{ __('Mengikut Kumpulan (A, B, C...)') }}</option>
                            <option value="category_asc">🏷️ {{ __('Mengikut Kategori') }}</option>
                            <option value="status_asc">⏱️ {{ __('Mengikut Status Perlawanan') }}</option>
                        </select>
                    @else
                        <div class="h-[2rem] px-3 bg-base-100 border border-base-200 rounded-xl text-xs font-bold text-primary flex items-center gap-1.5 shadow-2xs" title="{{ __('Hanya Admin Master boleh mengubah susunan perlawanan.') }}">
                            <span class="text-xs">🔒</span>
                            <span class="truncate">
                                @if($this->sortBy === 'interleaved_round') {{ __('Selang-Seli Pusingan') }}
                                @elseif($this->sortBy === 'group_asc') {{ __('Mengikut Kumpulan') }}
                                @elseif($this->sortBy === 'category_asc') {{ __('Mengikut Kategori') }}
                                @elseif($this->sortBy === 'status_asc') {{ __('Mengikut Status') }}
                                @else {{ __('Giliran Asal (Lalai)') }}
                                @endif
                            </span>
                        </div>
                    @endif
                </div>

                {{-- Filter Category --}}
                <div class="space-y-1">
                    <label class="text-[10px] font-bold text-base-content/50 uppercase tracking-widest">{{ __('Tapis Kategori:') }}</label>
                    <select wire:model.live="filterCategory" class="select select-bordered select-sm w-full rounded-xl text-xs font-semibold bg-white focus:border-primary">
                        <option value="">-- {{ __('Semua Kategori') }} --</option>
                        @foreach($this->categoriesForField as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Filter Group --}}
                <div class="space-y-1">
                    <label class="text-[10px] font-bold text-base-content/50 uppercase tracking-widest">{{ __('Tapis Kumpulan:') }}</label>
                    <select wire:model.live="filterGroup" class="select select-bordered select-sm w-full rounded-xl text-xs font-semibold bg-white focus:border-primary">
                        <option value="">-- {{ __('Semua Kumpulan') }} --</option>
                        @foreach($this->groupsForField as $grp)
                            <option value="{{ $grp->id }}">{{ $grp->group_name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Filter Status --}}
                <div class="space-y-1">
                    <label class="text-[10px] font-bold text-base-content/50 uppercase tracking-widest">{{ __('Tapis Status:') }}</label>
                    <div class="flex gap-1.5">
                        <select wire:model.live="filterStatus" class="select select-bordered select-sm flex-1 rounded-xl text-xs font-semibold bg-white focus:border-primary">
                            <option value="">-- {{ __('Semua Status') }} --</option>
                            <option value="in_progress">🔴 {{ __('Sedang Berlangsung') }}</option>
                            <option value="scheduled">⏳ {{ __('Menunggu') }}</option>
                            <option value="completed">✓ {{ __('Selesai') }}</option>
                        </select>
                        @if($filterCategory || $filterGroup || $filterStatus || $sortBy !== (str_contains((string)$activeField, 'Sky Soccer') ? 'interleaved_round' : 'default'))
                            <button wire:click="resetFilters" class="btn btn-sm btn-ghost text-red-500 text-xs px-2.5 rounded-xl hover:bg-red-50" title="{{ __('Set Semula Penapis') }}">
                                ✕
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="space-y-4">
            @forelse($this->matches as $match)
                <div class="bg-white rounded-2xl border {{ $match->status === 'in_progress' ? 'border-primary ring-2 ring-primary/20' : 'border-base-200' }} shadow-sm overflow-hidden flex flex-col md:flex-row">
                    @if($bulkMode && $match->status === 'scheduled')
                        <div class="p-4 bg-indigo-50/50 flex items-center justify-center border-b md:border-b-0 md:border-r border-indigo-100 shrink-0">
                            <input type="checkbox" 
                                   wire:click="toggleSelectMatch({{ $match->id }})"
                                   {{ in_array($match->id, $selectedMatchIds) ? 'checked' : '' }}
                                   class="checkbox checkbox-primary checkbox-md rounded-lg" />
                        </div>
                    @endif
                    
                    {{-- Status/Info Sidebar --}}
                    <div class="md:w-48 bg-base-200/50 p-4 border-b md:border-b-0 md:border-r border-base-200 flex flex-col justify-center">
                        <span class="text-[10px] font-black uppercase tracking-widest text-primary mb-1">{{ $match->category->name }}</span>
                        <h4 class="font-extrabold text-base-content text-sm leading-tight">{{ $match->group ? $match->group->group_name : $match->stage_label }}</h4>
                        <p class="text-xs font-bold text-base-content/50 mt-1">{{ $match->round_name }}</p>
                        
                        <div class="mt-3">
                            @if($match->status === 'scheduled')
                                <span class="inline-block bg-amber-100 text-amber-700 text-[10px] font-bold px-2 py-1 rounded-md uppercase tracking-wider">{{ __('Menunggu') }}</span>
                            @elseif($match->status === 'in_progress')
                                <span class="inline-block bg-blue-100 text-blue-700 text-[10px] font-bold px-2 py-1 rounded-md uppercase tracking-wider animate-pulse">{{ __('Sedang Berlangsung') }}</span>
                            @else
                                <span class="inline-block bg-emerald-100 text-emerald-700 text-[10px] font-bold px-2 py-1 rounded-md uppercase tracking-wider">{{ __('Selesai') }}</span>
                            @endif
                        </div>
                    </div>

                    {{-- Match Teams & Score --}}
                    <div class="flex-1 p-5 flex flex-col justify-center">
                        <div class="flex items-center justify-between gap-4">
                            
                            {{-- Home Team --}}
                            <div class="flex-1 {{ ($match->group ? $match->group->game_type : '') === 'obstacle' ? 'text-center' : 'text-right' }}">
                                <h3 class="font-extrabold text-base-content text-lg leading-tight">{{ $match->homeTeam ? $match->homeTeam->team_name : 'BYE' }}</h3>
                                <p class="text-[10px] text-base-content/50 font-bold uppercase mt-1 line-clamp-1">{{ $match->homeTeam ? $match->homeTeam->school_name : '-' }}</p>
                            </div>

                            @if(($match->group ? $match->group->game_type : '') === 'obstacle')
                                @if($match->status === 'completed')
                                    <div class="shrink-0 flex flex-col items-center justify-center px-6 border-l border-base-200">
                                        <p class="text-[10px] font-bold text-base-content/50 uppercase mb-1">{{ __('Masa Rasmi') }}</p>
                                        <span class="text-xl font-black text-emerald-600">{{ $match->formatted_obstacle_time }}</span>
                                    </div>
                                @endif
                            @else
                            {{-- Score / VS --}}
                            <div class="shrink-0 flex flex-col items-center justify-center px-4">
                                @if($match->status === 'completed')
                                    <div class="flex items-center gap-2">
                                        <span class="text-2xl font-black {{ $match->home_score > $match->away_score ? 'text-emerald-600' : 'text-base-content' }}">{{ $match->home_score }}</span>
                                        <span class="text-base-content/30 font-bold text-sm">-</span>
                                        <span class="text-2xl font-black {{ $match->away_score > $match->home_score ? 'text-emerald-600' : 'text-base-content' }}">{{ $match->away_score }}</span>
                                    </div>
                                    <span class="text-[10px] font-bold text-base-content/40 uppercase mt-1">{{ __('Keputusan Rasmi') }}</span>
                                @else
                                    <div class="w-10 h-10 rounded-full bg-base-200 flex items-center justify-center text-xs font-black text-base-content/40">{{ __('VS') }}</div>
                                @endif
                            </div>

                            {{-- Away Team --}}
                            <div class="flex-1 text-left">
                                <h3 class="font-extrabold text-base-content text-lg leading-tight">{{ $match->awayTeam ? $match->awayTeam->team_name : 'BYE' }}</h3>
                                <p class="text-[10px] text-base-content/50 font-bold uppercase mt-1 line-clamp-1">{{ $match->awayTeam ? $match->awayTeam->school_name : '-' }}</p>
                            </div>
                            @endif
                        </div>
                    </div>

                    {{-- Action Button --}}
                    <div class="p-4 bg-base-50 border-t md:border-t-0 md:border-l border-base-200 flex flex-col md:flex-row items-center justify-center gap-2 md:w-56">
                        @if((!$match->homeTeam || !$match->awayTeam) && ($match->group ? $match->group->game_type : '') !== 'obstacle')
                            <button class="w-full bg-base-200 text-base-content/40 text-xs font-bold py-3 rounded-xl cursor-not-allowed">{{ __('Auto-Bye') }}</button>
                        @else
                            <div class="flex flex-col gap-2 w-full">
                                @if($match->status === 'completed')
                                    <button wire:click="openScoring({{ $match->id }})" class="bg-base-200 hover:bg-base-300 text-base-content font-bold px-4 py-2.5 rounded-xl text-sm transition-colors w-full">
                                        {{ __('Kemaskini') }}
                                    </button>
                                @else
                                    @if($match->status === 'scheduled')
                                        <div class="grid grid-cols-2 gap-1.5">
                                            <button wire:click="callTeam({{ $match->id }})" class="bg-amber-100 hover:bg-amber-200 text-amber-700 font-bold px-2 py-2 rounded-xl text-xs transition-colors flex items-center justify-center gap-1">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                                                <span>{{ __('Panggil') }}</span>
                                            </button>
                                            <button wire:click="openMoveMatchModal({{ $match->id }})" class="bg-sky-100 hover:bg-sky-200 text-sky-700 font-bold px-2 py-2 rounded-xl text-xs transition-colors flex items-center justify-center gap-1" title="{{ __('Pindah ke padang lain') }}">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                                                <span>{{ __('Pindah') }}</span>
                                            </button>
                                        </div>
                                    @endif
                                    <button wire:click="openScoring({{ $match->id }})" class="bg-primary hover:bg-primary/90 text-white font-bold px-4 py-2.5 rounded-xl text-sm shadow-md shadow-primary/20 transition-all w-full">
                                        @if(($match->group ? $match->group->game_type : '') === 'obstacle')
                                            {{ __('Rekod Masa') }}
                                        @else
                                            {{ __('Rekod Markah') }}
                                        @endif
                                    </button>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            @empty
                <div class="text-center py-12 text-base-content/40">
                    <p class="font-bold">{{ __('Tiada perlawanan dijumpai untuk padang ini.') }}</p>
                </div>
            @endforelse
        </div>
    @endif

    {{-- Scoring Modal --}}
    <div x-data="{ 
        open: false,
        stopwatch: {
            time: 0,
            interval: null,
            running: false,
            start() {
                if(!this.running) {
                    this.running = true;
                    this.interval = setInterval(() => { this.time++ }, 1000);
                }
            },
            pause() {
                this.running = false;
                if(this.interval) clearInterval(this.interval);
            },
            reset() {
                this.pause();
                this.time = 0;
            },
            format() {
                let m = Math.floor(this.time / 60).toString().padStart(2, '0');
                let s = (this.time % 60).toString().padStart(2, '0');
                return m + ':' + s;
            }
        }
    }" 
         x-on:open-scoring-modal.window="open = true" 
         x-on:close-scoring-modal.window="open = false; stopwatch.reset();">
        
        <div x-show="open" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                
                {{-- Backdrop --}}
                <div x-show="open" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 transition-opacity bg-black/50 backdrop-blur-sm" aria-hidden="true" wire:click="closeScoring"></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                {{-- Modal Panel --}}
                <div x-show="open" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" class="inline-block w-full max-w-lg overflow-hidden text-left align-bottom transition-all transform bg-base-100 rounded-3xl shadow-2xl sm:my-8 sm:align-middle border border-base-200">
                    
                    @if($sm = $this->getScoringMatch())
                        @php
                            $isObs = ($sm->group && $sm->group->game_type === 'obstacle');
                        @endphp
                        
                        <div class="px-6 py-5 bg-gradient-to-r from-primary to-secondary text-white flex justify-between items-center">
                            <div>
                                <h3 class="text-xl font-black">{{ $isObs ? __('Rekod Masa (Time Trial)') : __('Rekod Markah Perlawanan') }}</h3>
                                <p class="text-white/70 text-xs mt-0.5 font-medium uppercase tracking-widest">{{ $sm->round_name }} • {{ __('Padang') }} {{ $sm->field_number }}</p>
                            </div>
                            <button wire:click="closeScoring" class="text-white/50 hover:text-white bg-white/10 hover:bg-white/20 p-2 rounded-xl transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                        
                        <div class="p-6 space-y-6">
                            @if($isObs)
                                {{-- OBSTACLE SCORING --}}
                                <div class="text-center">
                                    <h4 class="font-extrabold text-2xl text-base-content">{{ $sm->homeTeam->team_name ?? 'BYE' }}</h4>
                                    <p class="text-sm font-bold text-base-content/50">{{ $sm->homeTeam->school_name ?? '-' }}</p>
                                </div>
                                
                                <div x-data="{ activeTab: 1 }" class="space-y-4">
                                    <div class="flex bg-base-200/50 p-1 rounded-xl">
                                        <button x-on:click="activeTab = 1" :class="activeTab === 1 ? 'bg-white shadow-sm text-primary font-bold' : 'text-base-content/60 font-medium hover:text-base-content'" class="flex-1 py-2 rounded-lg text-sm transition-all">
                                            {{ __('Larian 1') }}<br><span class="text-[10px] font-normal uppercase">{{ $sm->homeTeam->player_1 ?? __('Peserta 1') }}</span>
                                        </button>
                                        <button x-on:click="activeTab = 2" :class="activeTab === 2 ? 'bg-white shadow-sm text-primary font-bold' : 'text-base-content/60 font-medium hover:text-base-content'" class="flex-1 py-2 rounded-lg text-sm transition-all">
                                            {{ __('Larian 2') }}<br><span class="text-[10px] font-normal uppercase">{{ $sm->homeTeam->player_2 ?? __('Peserta 2') }}</span>
                                        </button>
                                    </div>
                                    
                                    {{-- TAB 1 --}}
                                    <div x-show="activeTab === 1" class="bg-base-200/50 p-5 rounded-2xl border border-base-200 space-y-4">
                                        <div>
                                            <label class="block text-xs font-bold text-base-content/50 uppercase tracking-widest mb-2">{{ __('Masa Larian 1') }}</label>
                                            <div class="flex gap-2">
                                                <input type="number" wire:model.live="obsMinutes1" min="0" placeholder="{{ __('Min') }}" class="w-full text-center text-xl font-black py-3 rounded-xl border-2 border-base-300 focus:border-primary focus:ring-4 focus:ring-primary/10">
                                                <span class="text-2xl font-bold text-base-content/30 py-2">:</span>
                                                <input type="number" wire:model.live="obsSeconds1" min="0" max="59" placeholder="{{ __('Sec') }}" class="w-full text-center text-xl font-black py-3 rounded-xl border-2 border-base-300 focus:border-primary focus:ring-4 focus:ring-primary/10">
                                                <span class="text-2xl font-bold text-base-content/30 py-2">.</span>
                                                <input type="number" wire:model.live="obsMilliseconds1" min="0" max="999" placeholder="{{ __('Ms') }}" class="w-full text-center text-xl font-black py-3 rounded-xl border-2 border-base-300 focus:border-primary focus:ring-4 focus:ring-primary/10">
                                            </div>
                                        </div>
                                        <div class="pt-4 border-t border-base-200">
                                            <label class="block text-xs font-bold text-base-content/50 uppercase tracking-widest mb-2">{{ __('Penalti (+1 Saat)') }}</label>
                                            <div class="flex items-center justify-between bg-white rounded-xl border-2 border-base-300 p-2">
                                                <button wire:click="decrementPenalty(1)" class="w-12 h-12 flex items-center justify-center bg-red-100 text-red-600 hover:bg-red-200 rounded-lg font-bold text-2xl transition-colors">-</button>
                                                <span class="text-3xl font-black text-base-content">{{ $obsPenalties1 }}</span>
                                                <button wire:click="incrementPenalty(1)" class="w-12 h-12 flex items-center justify-center bg-emerald-100 text-emerald-600 hover:bg-emerald-200 rounded-lg font-bold text-2xl transition-colors">+</button>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    {{-- TAB 2 --}}
                                    <div x-show="activeTab === 2" style="display: none;" class="bg-base-200/50 p-5 rounded-2xl border border-base-200 space-y-4">
                                        <div>
                                            <label class="block text-xs font-bold text-base-content/50 uppercase tracking-widest mb-2">{{ __('Masa Larian 2') }}</label>
                                            <div class="flex gap-2">
                                                <input type="number" wire:model.live="obsMinutes2" min="0" placeholder="{{ __('Min') }}" class="w-full text-center text-xl font-black py-3 rounded-xl border-2 border-base-300 focus:border-primary focus:ring-4 focus:ring-primary/10">
                                                <span class="text-2xl font-bold text-base-content/30 py-2">:</span>
                                                <input type="number" wire:model.live="obsSeconds2" min="0" max="59" placeholder="{{ __('Sec') }}" class="w-full text-center text-xl font-black py-3 rounded-xl border-2 border-base-300 focus:border-primary focus:ring-4 focus:ring-primary/10">
                                                <span class="text-2xl font-bold text-base-content/30 py-2">.</span>
                                                <input type="number" wire:model.live="obsMilliseconds2" min="0" max="999" placeholder="{{ __('Ms') }}" class="w-full text-center text-xl font-black py-3 rounded-xl border-2 border-base-300 focus:border-primary focus:ring-4 focus:ring-primary/10">
                                            </div>
                                        </div>
                                        <div class="pt-4 border-t border-base-200">
                                            <label class="block text-xs font-bold text-base-content/50 uppercase tracking-widest mb-2">{{ __('Penalti (+1 Saat)') }}</label>
                                            <div class="flex items-center justify-between bg-white rounded-xl border-2 border-base-300 p-2">
                                                <button wire:click="decrementPenalty(2)" class="w-12 h-12 flex items-center justify-center bg-red-100 text-red-600 hover:bg-red-200 rounded-lg font-bold text-2xl transition-colors">-</button>
                                                <span class="text-3xl font-black text-base-content">{{ $obsPenalties2 }}</span>
                                                <button wire:click="incrementPenalty(2)" class="w-12 h-12 flex items-center justify-center bg-emerald-100 text-emerald-600 hover:bg-emerald-200 rounded-lg font-bold text-2xl transition-colors">+</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="bg-primary/5 border border-primary/20 rounded-xl p-4 text-center">
                                    <p class="text-xs font-bold text-primary uppercase tracking-widest">{{ __('Masa Rasmi (Terpantas)') }}</p>
                                    @php
                                        // Run 1
                                        $min1 = (int)($obsMinutes1 ?: 0); $sec1 = (int)($obsSeconds1 ?: 0); $ms1 = (int)($obsMilliseconds1 ?: 0); $pen1 = (int)($obsPenalties1 ?: 0);
                                        $baseMs1 = ($min1 * 60000) + ($sec1 * 1000) + $ms1;
                                        $totalMs1 = $baseMs1 > 0 ? $baseMs1 + ($pen1 * 1000) : null;
                                        
                                        // Run 2
                                        $min2 = (int)($obsMinutes2 ?: 0); $sec2 = (int)($obsSeconds2 ?: 0); $ms2 = (int)($obsMilliseconds2 ?: 0); $pen2 = (int)($obsPenalties2 ?: 0);
                                        $baseMs2 = ($min2 * 60000) + ($sec2 * 1000) + $ms2;
                                        $totalMs2 = $baseMs2 > 0 ? $baseMs2 + ($pen2 * 1000) : null;
                                        
                                        if ($totalMs1 && $totalMs2) $bestMs = min($totalMs1, $totalMs2);
                                        elseif ($totalMs1) $bestMs = $totalMs1;
                                        elseif ($totalMs2) $bestMs = $totalMs2;
                                        else $bestMs = 0;
                                        
                                        if ($bestMs > 0) {
                                             $tM = floor($bestMs / 60000);
                                             $tS = floor(($bestMs % 60000) / 1000);
                                             $tMs = $bestMs % 1000;
                                             $timeStr = sprintf('%02d:%02d.%03d', $tM, $tS, $tMs);
                                        } else {
                                             $timeStr = '-';
                                        }
                                    @endphp
                                    <p class="text-3xl font-black text-primary mt-1">{{ $timeStr }}</p>
                                </div>
                                
                            @else
                                {{-- SOCCER SCORING --}}
                                <div class="bg-base-200/50 rounded-2xl p-4 border border-base-200 text-center">
                                    <p class="text-xs font-bold text-base-content/50 uppercase tracking-widest mb-2">{{ __('Jam Perlawanan (Bantuan)') }}</p>
                                    <div class="text-5xl font-black text-base-content tabular-nums mb-3" x-text="stopwatch.format()">00:00</div>
                                    <div class="flex items-center justify-center gap-2">
                                        <button x-on:click="stopwatch.start()" x-show="!stopwatch.running" class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-1.5 rounded-lg text-sm font-bold shadow-sm">{{ __('Start') }}</button>
                                        <button x-on:click="stopwatch.pause()" x-show="stopwatch.running" style="display:none;" class="bg-amber-500 hover:bg-amber-600 text-white px-4 py-1.5 rounded-lg text-sm font-bold shadow-sm">{{ __('Pause') }}</button>
                                        <button x-on:click="stopwatch.reset()" class="bg-rose-500 hover:bg-rose-600 text-white px-4 py-1.5 rounded-lg text-sm font-bold shadow-sm">{{ __('Stop') }}</button>
                                    </div>
                                </div>
                                
                                <div class="grid grid-cols-2 gap-4">
                                    {{-- Home --}}
                                    <div class="bg-white border-2 border-base-200 rounded-2xl p-4 text-center shadow-sm">
                                        <h4 class="font-extrabold text-base-content leading-tight h-10">{{ $sm->homeTeam->team_name ?? 'BYE' }}</h4>
                                        <div class="flex items-center justify-between mt-4">
                                            <button wire:click="decrementScore('home')" class="w-10 h-10 bg-base-200 hover:bg-base-300 rounded-xl font-bold text-xl flex items-center justify-center">-</button>
                                            <span class="text-4xl font-black text-primary">{{ $soccerHomeScore }}</span>
                                            <button wire:click="incrementScore('home')" class="w-10 h-10 bg-primary/10 text-primary hover:bg-primary hover:text-white rounded-xl font-bold text-xl flex items-center justify-center">+</button>
                                        </div>
                                    </div>
                                    
                                    {{-- Away --}}
                                    <div class="bg-white border-2 border-base-200 rounded-2xl p-4 text-center shadow-sm">
                                        <h4 class="font-extrabold text-base-content leading-tight h-10">{{ $sm->awayTeam->team_name ?? 'BYE' }}</h4>
                                        <div class="flex items-center justify-between mt-4">
                                            <button wire:click="decrementScore('away')" class="w-10 h-10 bg-base-200 hover:bg-base-300 rounded-xl font-bold text-xl flex items-center justify-center">-</button>
                                            <span class="text-4xl font-black text-primary">{{ $soccerAwayScore }}</span>
                                            <button wire:click="incrementScore('away')" class="w-10 h-10 bg-primary/10 text-primary hover:bg-primary hover:text-white rounded-xl font-bold text-xl flex items-center justify-center">+</button>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                        
                        <div class="px-6 py-4 bg-base-200/50 flex justify-between gap-2">
                            <div>
                                @if($sm->status === 'completed')
                                    <button wire:click="resetMatch({{ $sm->id }})" wire:confirm="Anda pasti mahu buang semua markah dan reset perlawanan ini untuk Rematch?" class="px-5 py-2.5 rounded-xl font-bold bg-red-100 text-red-600 hover:bg-red-200 transition-colors flex items-center gap-2">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                        {{ __('Rematch') }}
                                    </button>
                                @endif
                            </div>
                            <div class="flex gap-2">
                                <button wire:click="closeScoring" class="px-5 py-2.5 rounded-xl font-bold text-base-content/60 hover:bg-base-200 transition-colors">{{ __('Batal') }}</button>
                                <button wire:click="saveScore" wire:loading.attr="disabled" class="px-6 py-2.5 rounded-xl font-bold bg-primary hover:bg-primary/90 text-white shadow-md shadow-primary/20 transition-all flex items-center gap-2">
                                    <span wire:loading.remove wire:target="saveScore">{{ __('Sahkan & Simpan') }}</span>
                                    <span wire:loading wire:target="saveScore">{{ __('Menyimpan...') }}</span>
                                </button>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Floating Bulk Move Bar --}}
    @if($bulkMode)
        <div class="fixed bottom-6 left-1/2 -translate-x-1/2 z-40 bg-slate-900 text-white px-6 py-4 rounded-2xl shadow-2xl flex flex-col sm:flex-row items-center gap-4 border border-white/10 animate-slide-up max-w-2xl w-[90%]">
            <div class="flex items-center gap-2">
                <span class="text-xs font-black bg-primary px-2.5 py-1 rounded-full text-white">{{ count($selectedMatchIds) }}</span>
                <span class="text-xs font-bold">{{ __('Perlawanan Dipilih') }}</span>
                <button wire:click="selectAllScheduled" class="text-[11px] text-sky-300 hover:underline font-medium ml-2">{{ __('Pilih Semua') }}</button>
            </div>
            <div class="flex items-center gap-2 flex-1 w-full sm:w-auto">
                <label class="text-xs text-white/70 whitespace-nowrap">{{ __('Pindah ke:') }}</label>
                <select wire:model.live="bulkTargetField" class="select select-sm select-bordered rounded-xl bg-slate-800 text-white text-xs font-bold border-white/20 flex-1">
                    <option value="">-- {{ __('Pilih Padang Sasaran') }} --</option>
                    @foreach($this->allFieldsSummary as $fs)
                        @if($fs['field'] != $activeField)
                            <option value="{{ $fs['field'] }}">
                                {{ is_numeric($fs['field']) ? __('Padang') . ' ' . $fs['field'] : $fs['field'] }}
                                ({{ $fs['is_empty'] ? '🟢 ' . __('Kosong') : $fs['scheduled'] . ' ' . __('menunggu') }})
                            </option>
                        @endif
                    @endforeach
                </select>
            </div>
            <div class="flex items-center gap-2">
                <button wire:click="executeBulkMove" 
                        @if(empty($selectedMatchIds) || empty($bulkTargetField)) disabled @endif
                        class="btn btn-sm bg-emerald-500 hover:bg-emerald-600 text-white font-bold rounded-xl border-none disabled:bg-slate-700">
                    {{ __('Pindahkan') }}
                </button>
                <button wire:click="toggleBulkMode" class="btn btn-sm btn-ghost text-white/60 hover:text-white rounded-xl">
                    {{ __('Batal') }}
                </button>
            </div>
        </div>
    @endif

    {{-- Move Single Match Modal --}}
    @if($showMoveModal && $this->movingMatch)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4 animate-fade-in">
            <div class="bg-white rounded-3xl shadow-2xl w-full max-w-md overflow-hidden animate-slide-up border border-base-200">
                <div class="p-6 border-b border-base-200 bg-base-50 flex justify-between items-center">
                    <div class="flex items-center gap-2.5">
                        <div class="w-9 h-9 rounded-xl bg-sky-100 text-sky-600 flex items-center justify-center text-base font-bold">
                            🔁
                        </div>
                        <div>
                            <h3 class="font-black text-lg text-base-content leading-tight">{{ __('Pindahkan Perlawanan') }}</h3>
                            <p class="text-xs text-base-content/50">{{ __('Tukar perlawanan ini ke padang yang kosong.') }}</p>
                        </div>
                    </div>
                    <button wire:click="closeMoveMatchModal" class="w-8 h-8 rounded-full bg-base-200 text-base-content/50 hover:text-base-content flex items-center justify-center font-bold">✕</button>
                </div>

                <div class="p-6 space-y-4">
                    <div class="bg-base-100 rounded-2xl p-4 border border-base-200 space-y-1">
                        <span class="text-[10px] font-extrabold uppercase tracking-widest text-primary">{{ $this->movingMatch->category->name ?? '' }} &middot; {{ $this->movingMatch->group ? $this->movingMatch->group->group_name : $this->movingMatch->round_name }}</span>
                        <p class="font-extrabold text-base-content text-sm">
                            {{ $this->movingMatch->homeTeam->team_name ?? 'BYE' }}
                            <span class="text-primary mx-1">VS</span>
                            {{ $this->movingMatch->awayTeam->team_name ?? 'BYE' }}
                        </p>
                        <p class="text-xs text-base-content/60 mt-1">
                            {{ __('Padang Semasa:') }} <strong class="text-amber-600">{{ is_numeric($this->movingMatch->field_number) ? __('Padang') . ' ' . $this->movingMatch->field_number : $this->movingMatch->field_number }}</strong>
                        </p>
                    </div>

                    <div class="space-y-2">
                        <label class="text-xs font-bold text-base-content uppercase tracking-wider">{{ __('Klik Padang Sasaran:') }}</label>
                        <div class="grid grid-cols-2 gap-2 max-h-60 overflow-y-auto pr-1">
                            @foreach($this->allFieldsSummary as $fs)
                                @php $isCurrent = ($fs['field'] == $this->movingMatch->field_number); @endphp
                                <button type="button"
                                        wire:click="moveMatchToField('{{ $fs['field'] }}')"
                                        @if($isCurrent) disabled @endif
                                        class="p-3 rounded-xl border text-left transition-all {{ $isCurrent ? 'bg-base-100 border-base-200 opacity-40 cursor-not-allowed' : ($fs['is_empty'] ? 'border-emerald-300 bg-emerald-50/50 hover:bg-emerald-100 hover:border-emerald-500' : 'border-base-200 hover:border-primary hover:bg-primary/5') }}">
                                    <div class="flex items-center justify-between">
                                        <span class="font-extrabold text-sm text-base-content">
                                            {{ is_numeric($fs['field']) ? __('Padang') . ' ' . $fs['field'] : $fs['field'] }}
                                        </span>
                                        @if($fs['is_empty'])
                                            <span class="text-[9px] font-black text-emerald-700 bg-emerald-100 px-1.5 py-0.5 rounded">🟢 Kosong</span>
                                        @else
                                            <span class="text-[9px] font-bold text-base-content/50">{{ $fs['scheduled'] }} m'tunggu</span>
                                        @endif
                                    </div>
                                </button>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="p-4 border-t border-base-200 bg-base-50 flex justify-end">
                    <button wire:click="closeMoveMatchModal" class="px-5 py-2.5 bg-base-200 hover:bg-base-300 text-base-content font-bold rounded-xl transition-colors text-sm">
                        {{ __('Batal') }}
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Pull Matches Modal --}}
    @if($showPullModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4 animate-fade-in">
            <div class="bg-white rounded-3xl shadow-2xl w-full max-w-2xl overflow-hidden animate-slide-up border border-base-200 max-h-[85vh] flex flex-col">
                <div class="p-6 border-b border-base-200 bg-base-50 flex justify-between items-center shrink-0">
                    <div class="flex items-center gap-2.5">
                        <div class="w-9 h-9 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center text-base font-bold">
                            📥
                        </div>
                        <div>
                            <h3 class="font-black text-lg text-base-content">
                                {{ __('Tarik Perlawanan ke') }} {{ is_numeric($activeField) ? __('Padang') . ' ' . $activeField : $activeField }}
                            </h3>
                            <p class="text-xs text-base-content/50">{{ __('Pilih perlawanan dari padang lain yang masih sibuk untuk dipindahkan ke sini.') }}</p>
                        </div>
                    </div>
                    <button wire:click="closePullModal" class="w-8 h-8 rounded-full bg-base-200 text-base-content/50 hover:text-base-content flex items-center justify-center font-bold">✕</button>
                </div>

                {{-- Filter Bar --}}
                <div class="p-4 border-b border-base-200 bg-base-100/50 flex gap-2 shrink-0">
                    <select wire:model.live="pullFilterField" class="select select-bordered select-sm rounded-xl text-xs flex-1 bg-white">
                        <option value="">-- {{ __('Semua Padang Asal') }} --</option>
                        @foreach($this->allFieldsSummary as $fs)
                            @if($fs['field'] != $activeField && $fs['scheduled'] > 0)
                                <option value="{{ $fs['field'] }}">
                                    {{ is_numeric($fs['field']) ? __('Padang') . ' ' . $fs['field'] : $fs['field'] }} ({{ $fs['scheduled'] }} menunggu)
                                </option>
                            @endif
                        @endforeach
                    </select>
                </div>

                {{-- Matches List --}}
                <div class="p-6 overflow-y-auto flex-1 space-y-2.5">
                    @php $otherMatches = $this->otherFieldsScheduledMatches; @endphp
                    @forelse($otherMatches as $om)
                        <div class="bg-base-50 border border-base-200 hover:border-primary/40 rounded-2xl p-4 flex items-center justify-between gap-3 transition-colors">
                            <div class="flex items-center gap-3">
                                <input type="checkbox"
                                       wire:model.live="pullSelectedMatches"
                                       value="{{ $om->id }}"
                                       class="checkbox checkbox-primary checkbox-sm rounded-lg" />
                                <div>
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="text-[10px] font-black bg-amber-100 text-amber-800 px-2 py-0.5 rounded-md">
                                            {{ is_numeric($om->field_number) ? __('Padang') . ' ' . $om->field_number : $om->field_number }}
                                        </span>
                                        <span class="text-[10px] font-bold text-base-content/50 uppercase">
                                            {{ $om->category->name }} &middot; {{ $om->group ? $om->group->group_name : $om->round_name }}
                                        </span>
                                    </div>
                                    <p class="font-extrabold text-sm text-base-content mt-1">
                                        {{ $om->homeTeam->team_name ?? 'BYE' }}
                                        <span class="text-primary font-bold mx-1">vs</span>
                                        {{ $om->awayTeam->team_name ?? 'BYE' }}
                                    </p>
                                </div>
                            </div>
                            <button type="button" 
                                    wire:click="pullSingleMatch({{ $om->id }})"
                                    class="btn btn-sm bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-xs shadow-xs shrink-0">
                                + {{ __('Tarik ke Sini') }}
                            </button>
                        </div>
                    @empty
                        <div class="text-center py-10 text-base-content/40">
                            <p class="font-bold text-sm">{{ __('Tiada perlawanan menunggu di padang lain.') }}</p>
                        </div>
                    @endforelse
                </div>

                <div class="p-4 border-t border-base-200 bg-base-50 flex justify-between items-center shrink-0">
                    <span class="text-xs font-bold text-base-content/60">
                        {{ count($pullSelectedMatches) }} {{ __('perlawanan dipilih') }}
                    </span>
                    <div class="flex gap-2">
                        <button wire:click="closePullModal" class="px-4 py-2 bg-base-200 hover:bg-base-300 text-base-content font-bold rounded-xl text-xs">
                            {{ __('Batal') }}
                        </button>
                        <button wire:click="executePullMatches" 
                                @if(empty($pullSelectedMatches)) disabled @endif
                                class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-xs shadow-md disabled:bg-base-300 disabled:text-base-content/30">
                            {{ __('Pindahkan Terpilih ke Sini') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>