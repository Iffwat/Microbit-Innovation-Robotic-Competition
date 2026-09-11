<?php

use App\Models\TournamentMatch;
use App\Models\Category;
use App\Models\Group;
use App\Models\GroupTeam;
use App\Models\Team;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component {
    
    #[Computed]
    public function fieldChunks()
    {
        $fields = TournamentMatch::whereNotNull('field_number')
            ->select('field_number')
            ->distinct()
            ->orderByRaw('LENGTH(field_number)')
            ->orderBy('field_number')
            ->pluck('field_number');

        $chunks = [];

        foreach ($fields->chunk(5) as $fieldChunk) {
            $chunkData = [
                'active'   => collect(),
                'upcoming' => collect()
            ];

            foreach ($fieldChunk as $field) {
                $activeMatch = TournamentMatch::with(['homeTeam', 'awayTeam', 'group.category'])
                    ->where('field_number', $field)
                    ->where('status', 'in_progress')
                    ->first();
                    
                if ($activeMatch) {
                    $chunkData['active']->push($activeMatch);
                }

                $upcomingMatch = TournamentMatch::with(['homeTeam', 'awayTeam', 'group.category'])
                    ->where('field_number', $field)
                    ->where('status', 'scheduled')
                    ->orderBy('scheduled_time')
                    ->orderBy('id')
                    ->first();
                    
                if ($upcomingMatch) {
                    $chunkData['upcoming']->push($upcomingMatch);
                }
            }

            if ($chunkData['active']->isNotEmpty() || $chunkData['upcoming']->isNotEmpty()) {
                $chunks[] = $chunkData;
            }
        }

        return collect($chunks);
    }

    #[Computed]
    public function slides()
    {
        $slides = [];

        $knockoutCategories = Category::whereHas('matches', function($q) {
            $q->whereIn('stage', ['trophy_knockout', 'cup_knockout']);
        })->get();
        $knockoutCategoryIds = $knockoutCategories->pluck('id')->toArray();

        // 1. OBSTACLE: Category-wide Top 10 (Auto-hide when all matches are completed)
        $obstacleCategories = Category::whereHas('groups', function($q) {
            $q->where('game_type', 'obstacle');
        })->get();

        foreach($obstacleCategories as $cat) {
            $hasMatches = TournamentMatch::where('category_id', $cat->id)
                ->whereHas('group', function($q) {
                    $q->where('game_type', 'obstacle');
                })
                ->exists();

            $hasPendingMatches = TournamentMatch::where('category_id', $cat->id)
                ->whereHas('group', function($q) {
                    $q->where('game_type', 'obstacle');
                })
                ->whereIn('status', ['scheduled', 'in_progress'])
                ->exists();

            // If matches exist and all are finished, auto-hide obstacle slide
            if ($hasMatches && !$hasPendingMatches) {
                continue;
            }

            $standings = GroupTeam::whereHas('group', function($q) use ($cat) {
                $q->where('category_id', $cat->id)->where('game_type', 'obstacle');
            })
            ->with('team')
            ->orderByRaw('CASE WHEN goals_for > 0 THEN 0 ELSE 1 END')
            ->orderBy('goals_for', 'asc')
            ->limit(10)
            ->get();

            if ($standings->isNotEmpty()) {
                $slides[] = [
                    'type'     => 'obstacle',
                    'title'    => 'DRONE OBSTACLE',
                    'category' => $cat->name,
                    'teams'    => $standings
                ];
            }
        }

        // 2. SOCCER: Group Standings (skip knockout categories)
        $soccerGroups = Group::where('game_type', '!=', 'obstacle')
            ->whereNotIn('category_id', $knockoutCategoryIds)
            ->with(['category', 'groupTeams.team'])
            ->get()
            ->groupBy('game_type');

        foreach($soccerGroups as $gameType => $groups) {
            $gameName = $gameType === 'isobot' ? 'ISOBOT SOCCER' : 'DRONE SKY SOCCER';
            foreach($groups->chunk(2) as $chunk) {
                $slides[] = [
                    'type'   => 'soccer',
                    'game'   => $gameType,
                    'title'  => $gameName,
                    'groups' => $chunk->values()
                ];
            }
        }

        // 3. KNOCKOUT: Smart Adaptive Bracket with Top 5 Official Ranking calculation
        $roundOrder = [
            'Pusingan ke-32' => 1,
            'Pusingan 32' => 1,
            'Pusingan ke-16' => 2,
            'Pusingan 16' => 2,
            'Suku Akhir' => 3,
            'Separuh Akhir' => 4,
            'Akhir' => 5,
            'Penentuan Tempat Ke-3' => 6,
        ];

        foreach($knockoutCategories as $cat) {
            $soccerGroup = Group::where('category_id', $cat->id)
                ->where('game_type', '!=', 'obstacle')
                ->first();
            $gameType = $soccerGroup ? $soccerGroup->game_type : 'isobot';
            $gameName = $gameType === 'isobot' ? 'ISOBOT SOCCER' : 'DRONE SKY SOCCER';

            $brackets = TournamentMatch::with(['homeTeam', 'awayTeam'])
                ->where('category_id', $cat->id)
                ->whereIn('stage', ['trophy_knockout', 'cup_knockout'])
                ->orderBy('bracket_position')
                ->orderBy('id')
                ->get()
                ->groupBy('stage');
            
            foreach($brackets as $stage => $matchesInStage) {
                $allRounds = $matchesInStage->groupBy('round_name');
                $finalMatch = $allRounds->get('Akhir') ? $allRounds->get('Akhir')->first() : null;
                $thirdMatch = $allRounds->get('Penentuan Tempat Ke-3') ? $allRounds->get('Penentuan Tempat Ke-3')->first() : null;

                // --- TOP 5 RANKINGS CALCULATION ---
                // 1st Place (Juara)
                $firstId = ($finalMatch && $finalMatch->status === 'completed') ? $finalMatch->winner_team_id : null;
                
                // 2nd Place (Naib Juara)
                $secondId = null;
                if ($finalMatch && $finalMatch->status === 'completed' && $firstId) {
                    $secondId = ($firstId == $finalMatch->home_team_id) ? $finalMatch->away_team_id : $finalMatch->home_team_id;
                }

                // 3rd Place
                $thirdId = ($thirdMatch && $thirdMatch->status === 'completed') ? $thirdMatch->winner_team_id : null;

                // 4th Place
                $fourthId = null;
                if ($thirdMatch && $thirdMatch->status === 'completed' && $thirdId) {
                    $fourthId = ($thirdId == $thirdMatch->home_team_id) ? $thirdMatch->away_team_id : $thirdMatch->home_team_id;
                }

                // 5th Place: Best QF Loser with highest cumulative goals scored across all knockout matches
                $qfMatches = $matchesInStage->filter(fn($m) => in_array($m->round_name, ['Suku Akhir', 'Quarter Final']));
                $qfLosers = [];

                foreach ($qfMatches as $m) {
                    if ($m->status === 'completed' && $m->winner_team_id) {
                        $loserId = ($m->winner_team_id == $m->home_team_id) ? $m->away_team_id : $m->home_team_id;
                        
                        // Cumulative knockout matches for this loser in this stage
                        $loserMatches = $matchesInStage->filter(function($match) use ($loserId) {
                            return $match->status === 'completed' && ($match->home_team_id == $loserId || $match->away_team_id == $loserId);
                        });

                        $totalGoals = 0;
                        $totalConceded = 0;

                        foreach ($loserMatches as $lm) {
                            if ($lm->home_team_id == $loserId) {
                                $totalGoals += (int)($lm->home_score ?? 0);
                                $totalConceded += (int)($lm->away_score ?? 0);
                            } elseif ($lm->away_team_id == $loserId) {
                                $totalGoals += (int)($lm->away_score ?? 0);
                                $totalConceded += (int)($lm->home_score ?? 0);
                            }
                        }

                        $totalGoalDiff = $totalGoals - $totalConceded;

                        $qfLosers[] = [
                            'team_id'         => $loserId,
                            'total_goals'     => $totalGoals,
                            'total_goal_diff' => $totalGoalDiff,
                            'matches_count'   => $loserMatches->count(),
                        ];
                    }
                }

                usort($qfLosers, function($a, $b) {
                    if ($b['total_goals'] !== $a['total_goals']) {
                        return $b['total_goals'] <=> $a['total_goals']; // Highest cumulative goals first
                    }
                    return $b['total_goal_diff'] <=> $a['total_goal_diff']; // Best goal difference
                });

                $fifthId = !empty($qfLosers) ? $qfLosers[0]['team_id'] : null;
                $fifthGoals = !empty($qfLosers) ? $qfLosers[0]['total_goals'] : null;

                $allTeamsMap = Team::whereIn('id', array_filter([$firstId, $secondId, $thirdId, $fourthId, $fifthId]))->get()->keyBy('id');

                $rankings = [
                    'first'       => $firstId ? ($allTeamsMap[$firstId] ?? null) : null,
                    'second'      => $secondId ? ($allTeamsMap[$secondId] ?? null) : null,
                    'third'       => $thirdId ? ($allTeamsMap[$thirdId] ?? null) : null,
                    'fourth'      => $fourthId ? ($allTeamsMap[$fourthId] ?? null) : null,
                    'fifth'       => $fifthId ? ($allTeamsMap[$fifthId] ?? null) : null,
                    'fifth_goals' => $fifthGoals,
                    'isFinished'  => ($firstId !== null && $thirdId !== null)
                ];

                // Check if this tournament has Pusingan ke-32 with pending early matches
                $hasRound32 = $allRounds->has('Pusingan ke-32') || $allRounds->has('Pusingan 32');
                $hasPendingEarlyRounds = false;

                if ($hasRound32) {
                    $hasPendingEarlyRounds = $matchesInStage
                        ->whereIn('round_name', ['Pusingan ke-32', 'Pusingan 32', 'Pusingan ke-16', 'Pusingan 16'])
                        ->whereIn('status', ['scheduled', 'in_progress'])
                        ->isNotEmpty();
                }

                $allFeederRounds = $allRounds->filter(function($matches, $roundName) {
                    return !in_array($roundName, ['Akhir', 'Penentuan Tempat Ke-3']);
                })->sortBy(function($matches, $roundName) use ($roundOrder) {
                    return $roundOrder[$roundName] ?? 99;
                });

                // SCENARIO A: Large Tournament (P32/P16 active) -> SPLIT INTO 2 SLIDES (BLOK KIRI & BLOK KANAN)
                if ($hasRound32 && $hasPendingEarlyRounds) {
                    // Slide 1: Blok Kiri (Left Side)
                    $slides[] = [
                        'type'         => 'knockout_split',
                        'branch'       => 'left',
                        'branchTitle'  => 'BLOK KIRI',
                        'game'         => $gameType,
                        'gameName'     => $gameName,
                        'stage'        => $stage,
                        'title'        => $gameName,
                        'category'     => $cat->name,
                        'feederRounds' => $allFeederRounds,
                        'finalMatch'   => $finalMatch,
                        'thirdMatch'   => $thirdMatch,
                        'rankings'     => $rankings
                    ];

                    // Slide 2: Blok Kanan (Right Side)
                    $slides[] = [
                        'type'         => 'knockout_split',
                        'branch'       => 'right',
                        'branchTitle'  => 'BLOK KANAN',
                        'game'         => $gameType,
                        'gameName'     => $gameName,
                        'stage'        => $stage,
                        'title'        => $gameName,
                        'category'     => $cat->name,
                        'feederRounds' => $allFeederRounds,
                        'finalMatch'   => $finalMatch,
                        'thirdMatch'   => $thirdMatch,
                        'rankings'     => $rankings
                    ];
                } 
                // SCENARIO B: Suku Akhir onwards OR Tournaments <= 16 teams -> UNIFIED DUAL-WING BRACKET (1 SLIDE WITH TOP 5 RANKING)
                else {
                    $feederRounds = $allFeederRounds;
                    if ($hasRound32) {
                        $feederRounds = $allFeederRounds->filter(function($m, $rName) {
                            return in_array($rName, ['Suku Akhir', 'Separuh Akhir']);
                        });
                    }

                    $slides[] = [
                        'type'         => 'knockout',
                        'game'         => $gameType,
                        'gameName'     => $gameName,
                        'stage'        => $stage,
                        'title'        => $gameName,
                        'category'     => $cat->name,
                        'feederRounds' => $feederRounds,
                        'finalMatch'   => $finalMatch,
                        'thirdMatch'   => $thirdMatch,
                        'rankings'     => $rankings
                    ];
                }
            }
        }

        return $slides;
    }

    #[Computed]
    public function calledMatches()
    {
        $calls = \Illuminate\Support\Facades\Cache::get('live_tv_calls', []);
        $activeMatches = [];
        
        foreach ($calls as $matchId => $callData) {
            if ($callData['expires_at'] > now()->timestamp) {
                $match = TournamentMatch::with(['homeTeam', 'awayTeam', 'group.category'])->find($matchId);
                if ($match && $match->status === 'scheduled') {
                    $activeMatches[] = $match;
                }
            }
        }
        
        return $activeMatches;
    }
};
?>

<div class="h-full grid grid-cols-[380px_1fr] gap-6" wire:poll.5s>

    {{-- ========================================================== --}}
    {{-- LEFT PANEL: LIVE MATCHES & UPCOMING                        --}}
    {{-- ========================================================== --}}
    <div class="flex flex-col gap-4 h-full min-h-0"
         x-data="{
            activeLeft: 0,
            totalLeft: {{ $this->fieldChunks()->count() }},
            init() {
                if (this.totalLeft > 1) {
                    setInterval(() => {
                        this.activeLeft = (this.activeLeft + 1) % this.totalLeft;
                    }, 10000);
                }
            }
         }">

        {{-- Left Panel Header --}}
        <div class="flex items-center justify-between shrink-0 bg-white/5 border border-white/10 px-4 py-2.5 rounded-2xl backdrop-blur-md">
            <div class="flex items-center gap-2.5">
                <div class="w-2.5 h-2.5 rounded-full bg-red-500 animate-ping"></div>
                <h2 class="text-xs font-black text-white tracking-[0.2em] uppercase">Status Padang</h2>
            </div>
            @if($this->fieldChunks()->count() > 1)
                <div class="flex items-center gap-1.5">
                    <template x-for="i in totalLeft" :key="i">
                        <div class="rounded-full h-1.5 transition-all duration-300"
                             :class="activeLeft === (i-1) ? 'w-5 bg-primary' : 'w-1.5 bg-white/20'"></div>
                    </template>
                </div>
            @endif
        </div>

        {{-- Match Feed --}}
        <div class="flex-1 min-h-0 relative">
            @if($this->fieldChunks()->isEmpty())
                <div class="h-full flex flex-col items-center justify-center gap-3 border-2 border-dashed border-white/10 rounded-2xl bg-white/[0.02]">
                    <div class="w-12 h-12 rounded-full bg-white/5 flex items-center justify-center">
                        <svg class="w-6 h-6 text-white/30" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    </div>
                    <p class="text-white/40 text-xs font-black tracking-widest uppercase">Tiada Perlawanan Aktif</p>
                </div>
            @else
                @foreach($this->fieldChunks() as $idx => $chunk)
                    <div x-show="activeLeft === {{ $idx }}"
                         x-transition:enter="transition ease-out duration-500"
                         x-transition:enter-start="opacity-0 translate-y-3"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         x-transition:leave="transition ease-in duration-300"
                         x-transition:leave-start="opacity-100 translate-y-0"
                         x-transition:leave-end="opacity-0 -translate-y-3"
                         class="absolute inset-0 flex flex-col gap-3 overflow-y-auto pr-1"
                         style="display:none; scrollbar-width:none;">

                        @php $active = $chunk['active']; $upcoming = $chunk['upcoming']; @endphp

                        {{-- ACTIVE / LIVE MATCHES --}}
                        @if($active->isNotEmpty())
                            <div class="flex items-center gap-2 px-1">
                                <span class="w-2 h-2 rounded-full bg-red-500 animate-pulse"></span>
                                <p class="text-[10px] font-black text-red-400 tracking-[0.2em] uppercase">Sedang Berlangsung</p>
                                <div class="flex-1 h-px bg-red-500/20"></div>
                            </div>

                            @foreach($active as $match)
                                <div class="relative rounded-2xl overflow-hidden border-2 border-primary/60 bg-gradient-to-br from-primary/20 via-black/80 to-black/90 shadow-lg shadow-primary/10">
                                    <div class="absolute inset-0 rounded-2xl border-2 border-primary/40 animate-pulse pointer-events-none"></div>
                                    
                                    {{-- Card Top Bar --}}
                                    <div class="flex items-center justify-between px-3.5 py-2 bg-primary/30 border-b border-primary/30">
                                        <div class="flex items-center gap-2">
                                            <span class="text-[10px] font-black bg-red-600 text-white px-2 py-0.5 rounded uppercase tracking-wider animate-pulse">LIVE</span>
                                            <span class="text-xs font-black text-yellow-300 uppercase tracking-wide">
                                                {{ is_numeric($match->field_number) ? 'PADANG '.$match->field_number : $match->field_number }}
                                            </span>
                                        </div>
                                        <span class="text-[10px] font-bold text-white/70 uppercase tracking-widest bg-black/40 px-2 py-0.5 rounded border border-white/10">
                                            {{ optional(optional($match->group)->category)->name ?? optional($match->category)->name ?? '' }}
                                        </span>
                                    </div>

                                    {{-- Card Content (Red Corner vs Blue Corner) --}}
                                    <div class="p-3.5">
                                        @if($match->group && $match->group->game_type === 'obstacle')
                                            <div class="flex items-center justify-between gap-3">
                                                <div class="overflow-hidden">
                                                    <p class="font-black text-white text-base leading-tight truncate">{{ $match->homeTeam->team_name ?? 'BYE' }}</p>
                                                    <p class="text-[10px] text-white/50 mt-0.5 truncate uppercase font-bold">{{ $match->homeTeam->school_name ?? '' }}</p>
                                                </div>
                                                <span class="text-[10px] font-black text-emerald-400 bg-emerald-500/20 px-2.5 py-1 rounded-lg border border-emerald-500/30 shrink-0 uppercase tracking-wider">
                                                    Sedang Berlumba
                                                </span>
                                            </div>
                                        @else
                                            <div class="grid grid-cols-[1fr_auto_1fr] gap-2.5 items-center">
                                                {{-- Home Team (Red Corner) --}}
                                                <div class="text-right overflow-hidden">
                                                    <div class="flex items-center justify-end gap-1.5">
                                                        <p class="font-black text-rose-200 text-xs leading-tight truncate">{{ $match->homeTeam->team_name ?? 'BYE' }}</p>
                                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500 shrink-0 shadow-sm shadow-rose-500/50"></span>
                                                    </div>
                                                    <p class="text-[9px] text-white/50 mt-0.5 truncate font-bold uppercase">{{ $match->homeTeam->school_name ?? '' }}</p>
                                                </div>

                                                {{-- Score Display --}}
                                                <div class="flex items-center gap-1.5 bg-black/90 px-2.5 py-1 rounded-xl border border-white/20 shadow-inner">
                                                    <span class="text-lg font-black text-rose-400 tabular-nums">{{ $match->home_score ?? 0 }}</span>
                                                    <span class="text-white/30 font-black text-xs">-</span>
                                                    <span class="text-lg font-black text-sky-400 tabular-nums">{{ $match->away_score ?? 0 }}</span>
                                                </div>

                                                {{-- Away Team (Blue Corner) --}}
                                                <div class="text-left overflow-hidden">
                                                    <div class="flex items-center justify-start gap-1.5">
                                                        <span class="w-1.5 h-1.5 rounded-full bg-sky-400 shrink-0 shadow-sm shadow-sky-400/50"></span>
                                                        <p class="font-black text-sky-200 text-xs leading-tight truncate">{{ $match->awayTeam->team_name ?? 'BYE' }}</p>
                                                    </div>
                                                    <p class="text-[9px] text-white/50 mt-0.5 truncate font-bold uppercase">{{ $match->awayTeam->school_name ?? '' }}</p>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        @endif

                        {{-- UPCOMING MATCHES --}}
                        @if($upcoming->isNotEmpty())
                            <div class="flex items-center gap-2 px-1 mt-1">
                                <span class="w-1.5 h-1.5 rounded-full bg-white/40"></span>
                                <p class="text-[10px] font-black text-white/40 tracking-[0.2em] uppercase">Seterusnya</p>
                                <div class="flex-1 h-px bg-white/10"></div>
                            </div>

                            @foreach($upcoming as $match)
                                <div class="rounded-2xl overflow-hidden border border-white/10 bg-white/[0.03] backdrop-blur-sm">
                                    <div class="flex items-center justify-between px-3 py-1.5 bg-white/5 border-b border-white/5">
                                        <span class="text-[10px] font-black text-yellow-400 uppercase tracking-wider">
                                            {{ is_numeric($match->field_number) ? 'PADANG '.$match->field_number : $match->field_number }}
                                        </span>
                                        <span class="text-[9px] font-bold text-white/50 uppercase tracking-widest">
                                            {{ optional(optional($match->group)->category)->name ?? optional($match->category)->name ?? '' }}
                                        </span>
                                    </div>
                                    <div class="p-3">
                                        @if($match->group && $match->group->game_type === 'obstacle')
                                            <div class="flex items-center justify-between">
                                                <p class="font-extrabold text-white text-xs truncate">{{ $match->homeTeam->team_name ?? 'BYE' }}</p>
                                                <span class="text-[9px] font-bold text-white/40 uppercase tracking-wider">Menunggu</span>
                                            </div>
                                        @else
                                            <div class="grid grid-cols-[1fr_auto_1fr] gap-2 items-center">
                                                <div class="flex items-center justify-end gap-1.5 overflow-hidden">
                                                    <p class="font-extrabold text-rose-200/90 text-xs truncate text-right">{{ $match->homeTeam->team_name ?? 'BYE' }}</p>
                                                    <span class="w-1.5 h-1.5 rounded-full bg-rose-500/80 shrink-0"></span>
                                                </div>
                                                <span class="text-[9px] font-black text-white/40 bg-black/40 px-1.5 py-0.5 rounded border border-white/10">VS</span>
                                                <div class="flex items-center justify-start gap-1.5 overflow-hidden">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-sky-400/80 shrink-0"></span>
                                                    <p class="font-extrabold text-sky-200/90 text-xs truncate">{{ $match->awayTeam->team_name ?? 'BYE' }}</p>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        @endif
                    </div>
                @endforeach
            @endif
        </div>
    </div>

    {{-- ========================================================== --}}
    {{-- RIGHT PANEL: STANDINGS / DUAL-WING & SPLIT KNOCKOUT        --}}
    {{-- ========================================================== --}}
    @php $slides = $this->slides(); $slideCount = count($slides); @endphp
    <div class="h-full min-h-0 relative rounded-3xl overflow-hidden bg-black/40 border border-white/10 shadow-2xl backdrop-blur-md"
         x-data="{
            activeSlide: 0,
            totalSlides: {{ $slideCount }},
            init() {
                if (this.totalSlides > 1) {
                    setInterval(() => {
                        this.activeSlide = (this.activeSlide + 1) % this.totalSlides;
                    }, 12000);
                }
            }
         }">

        @if($slideCount === 0)
            <div class="h-full flex flex-col items-center justify-center gap-3">
                <div class="w-14 h-14 rounded-full bg-white/5 flex items-center justify-center border border-white/10">
                    <svg class="w-7 h-7 text-white/30" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                </div>
                <p class="text-white/40 text-xs font-black tracking-widest uppercase">Tiada Data Kejohanan</p>
            </div>
        @else
            @foreach($slides as $idx => $slide)
                @php
                    if ($slide['type'] === 'obstacle') {
                        $gradTop = 'from-emerald-900/60 via-emerald-950/30'; $borderHdr = 'border-emerald-500/30'; $accentBar = 'bg-emerald-400'; $subtitleColor = 'text-emerald-300';
                    } elseif ($slide['type'] === 'soccer') {
                        if ($slide['game'] === 'isobot') {
                            $gradTop = 'from-blue-900/60 via-blue-950/30'; $borderHdr = 'border-blue-500/30'; $accentBar = 'bg-blue-400'; $subtitleColor = 'text-blue-300';
                        } else {
                            $gradTop = 'from-purple-900/60 via-purple-950/30'; $borderHdr = 'border-purple-500/30'; $accentBar = 'bg-purple-400'; $subtitleColor = 'text-purple-300';
                        }
                    } else { // knockout and knockout_split
                        if ($slide['game'] === 'isobot') {
                            $gradTop = $slide['stage'] === 'trophy_knockout' ? 'from-blue-900/60 via-amber-950/30' : 'from-blue-900/60 via-slate-900/40';
                        } else {
                            $gradTop = $slide['stage'] === 'trophy_knockout' ? 'from-purple-900/60 via-amber-950/30' : 'from-purple-900/60 via-slate-900/40';
                        }
                        $borderHdr = $slide['stage'] === 'trophy_knockout' ? 'border-amber-500/30' : 'border-slate-400/30';
                        $accentBar = $slide['stage'] === 'trophy_knockout' ? 'bg-amber-400' : 'bg-slate-300';
                        $subtitleColor = $slide['stage'] === 'trophy_knockout' ? 'text-amber-300' : 'text-slate-300';
                    }
                @endphp
                <div x-show="activeSlide === {{ $idx }}"
                     x-transition:enter="transition ease-out duration-700"
                     x-transition:enter-start="opacity-0 scale-[1.008]"
                     x-transition:enter-end="opacity-100 scale-100"
                     x-transition:leave="transition ease-in duration-500"
                     x-transition:leave-start="opacity-100 scale-100"
                     x-transition:leave-end="opacity-0 scale-[0.992]"
                     class="absolute inset-0 flex flex-col"
                     style="display:none;">

                    {{-- Slide Top Header --}}
                    <div class="shrink-0 px-8 py-5 bg-gradient-to-r {{ $gradTop }} to-transparent border-b {{ $borderHdr }}">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-4">
                                <div class="{{ $accentBar }} w-1.5 h-10 rounded-full shrink-0 shadow-lg"></div>
                                <div>
                                    <h2 class="text-3xl font-black text-white leading-none tracking-wider uppercase drop-shadow-md">{{ $slide['title'] }}</h2>
                                    <p class="text-xs font-black {{ $subtitleColor }} tracking-widest uppercase mt-2 flex items-center gap-2 flex-wrap">
                                        @if($slide['type'] === 'obstacle')
                                            <span class="bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 px-2 py-0.5 rounded">TOP 10 TERPANTAS</span>
                                            <span class="text-white/30">•</span>
                                            <span class="text-white/90">KATEGORI {{ $slide['category'] }}</span>
                                        @elseif($slide['type'] === 'soccer')
                                            <span class="bg-white/10 text-white/90 border border-white/20 px-2 py-0.5 rounded">KEDUDUKAN KUMPULAN</span>
                                        @elseif($slide['type'] === 'knockout_split')
                                            <span class="bg-white/15 text-white border border-white/25 px-2.5 py-0.5 rounded-md font-black tracking-wider shadow-sm">
                                                KATEGORI {{ $slide['category'] }}
                                            </span>
                                            <span class="text-white/30">•</span>
                                            <span class="bg-primary/30 text-yellow-300 border border-primary/40 px-2 py-0.5 rounded font-black">
                                                {{ $slide['branchTitle'] }}
                                            </span>
                                            <span class="text-white/30">•</span>
                                            <span class="text-yellow-300 font-black">
                                                {{ $slide['stage'] === 'trophy_knockout' ? '🏆 PUSINGAN TROFI' : '🥈 PUSINGAN PIALA' }}
                                            </span>
                                        @else
                                            <span class="bg-white/15 text-white border border-white/25 px-2.5 py-0.5 rounded-md font-black tracking-wider shadow-sm">
                                                KATEGORI {{ $slide['category'] }}
                                            </span>
                                            <span class="text-white/30">•</span>
                                            <span class="text-yellow-300 font-black">
                                                {{ $slide['stage'] === 'trophy_knockout' ? '🏆 PUSINGAN TROFI' : '🥈 PUSINGAN PIALA' }}
                                            </span>
                                            <span class="text-white/30">•</span>
                                            <span class="text-white/70">CARTA KALAH MATI</span>
                                        @endif
                                    </p>
                                </div>
                            </div>

                            {{-- Slide Indicators --}}
                            <div class="flex flex-col items-end gap-2">
                                <div class="flex gap-1.5">
                                    <template x-for="i in totalSlides" :key="i">
                                        <div class="rounded-full h-1.5 transition-all duration-500"
                                             :class="activeSlide === (i-1) ? 'w-6 bg-white' : 'w-2 bg-white/20'"></div>
                                    </template>
                                </div>
                                <span class="text-[10px] text-white/40 font-black tracking-wider uppercase">
                                    <span x-text="activeSlide + 1" class="text-white"></span> / {{ $slideCount }}
                                </span>
                            </div>
                        </div>
                    </div>

                    {{-- Slide Content Area --}}
                    <div class="flex-1 min-h-0 overflow-hidden p-6">

                        {{-- ========================================== --}}
                        {{-- 1. OBSTACLE LEADERBOARD                     --}}
                        {{-- ========================================== --}}
                        @if($slide['type'] === 'obstacle')
                            <div class="h-full overflow-hidden rounded-2xl border border-white/10 bg-black/40">
                                <table class="w-full">
                                    <thead>
                                        <tr class="border-b border-white/10 bg-white/5">
                                            <th class="py-3 px-6 text-left text-[11px] font-black text-white/50 tracking-widest uppercase w-16">KED</th>
                                            <th class="py-3 px-6 text-left text-[11px] font-black text-white/50 tracking-widest uppercase">PASUKAN</th>
                                            <th class="py-3 px-6 text-left text-[11px] font-black text-white/50 tracking-widest uppercase hidden xl:table-cell">SEKOLAH</th>
                                            <th class="py-3 px-6 text-right text-[11px] font-black text-white/50 tracking-widest uppercase">MASA RASMI</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($slide['teams'] as $i => $gt)
                                            <tr class="border-b border-white/5 last:border-0 {{ $i < 3 ? 'bg-emerald-500/10' : '' }}">
                                                <td class="py-3.5 px-6">
                                                    @if($i === 0)
                                                        <span class="text-lg">🥇</span>
                                                    @elseif($i === 1)
                                                        <span class="text-lg">🥈</span>
                                                    @elseif($i === 2)
                                                        <span class="text-lg">🥉</span>
                                                    @else
                                                        <span class="text-sm font-black text-white/40 pl-1">{{ $i + 1 }}</span>
                                                    @endif
                                                </td>
                                                <td class="py-3.5 px-6">
                                                    <span class="font-extrabold text-base text-white {{ $i < 3 ? 'text-emerald-300' : '' }}">{{ $gt->team->team_name }}</span>
                                                </td>
                                                <td class="py-3.5 px-6 text-white/60 text-sm font-medium hidden xl:table-cell">{{ $gt->team->school_name }}</td>
                                                <td class="py-3.5 px-6 text-right">
                                                    @php
                                                        $tf = $gt->goals_for;
                                                        if ($tf > 0) {
                                                            $tM = floor($tf/60000); $tS = floor(($tf%60000)/1000); $tMs = $tf%1000;
                                                            $timeStr = sprintf('%02d:%02d.%03d', $tM, $tS, $tMs);
                                                        } else { $timeStr = null; }
                                                    @endphp
                                                    @if($timeStr)
                                                        <span class="font-mono font-black text-xl text-emerald-400 tabular-nums">{{ $timeStr }}</span>
                                                    @else
                                                        <span class="text-xs font-black text-white/20 uppercase tracking-widest">BELUM BERLUMBA</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                        {{-- ========================================== --}}
                        {{-- 2. SOCCER GROUP STANDINGS                   --}}
                        {{-- ========================================== --}}
                        @elseif($slide['type'] === 'soccer')
                            <div class="grid grid-cols-2 gap-6 h-full">
                                @foreach($slide['groups'] as $g)
                                    <div class="rounded-2xl border border-white/10 bg-black/40 overflow-hidden flex flex-col min-h-0 shadow-lg">
                                        <div class="flex items-center justify-between px-5 py-3.5 bg-white/5 border-b border-white/10 shrink-0">
                                            <div class="flex items-center gap-2.5">
                                                <div class="w-7 h-7 rounded-lg bg-primary text-white flex items-center justify-center font-black text-sm">
                                                    {{ $g->group_letter }}
                                                </div>
                                                <h3 class="font-black text-base text-white">Kumpulan {{ $g->group_letter }}</h3>
                                            </div>
                                            <span class="text-[10px] font-extrabold text-white/50 bg-white/10 px-2.5 py-1 rounded-md uppercase tracking-wider">{{ $g->category->name }}</span>
                                        </div>
                                        <div class="flex-1 min-h-0 overflow-hidden">
                                            <table class="w-full">
                                                <thead>
                                                    <tr class="border-b border-white/10 bg-white/[0.02]">
                                                        <th class="py-2.5 px-4 text-left text-[10px] font-black text-white/40 tracking-widest">PASUKAN</th>
                                                        <th class="py-2.5 px-2 text-center text-[10px] font-black text-white/40 tracking-widest">P</th>
                                                        <th class="py-2.5 px-2 text-center text-[10px] font-black text-white/40 tracking-widest">M</th>
                                                        <th class="py-2.5 px-2 text-center text-[10px] font-black text-white/40 tracking-widest">S</th>
                                                        <th class="py-2.5 px-2 text-center text-[10px] font-black text-white/40 tracking-widest">K</th>
                                                        <th class="py-2.5 px-2 text-center text-[10px] font-black text-yellow-400/80 tracking-widest">GOL</th>
                                                        <th class="py-2.5 px-2 text-center text-[10px] font-black text-white/40 tracking-widest">+/-</th>
                                                        <th class="py-2.5 px-4 text-center text-[10px] font-black text-primary tracking-widest">MATA</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($g->getStandings() as $i => $gt)
                                                        <tr class="border-b border-white/5 last:border-0 {{ $i < 2 ? 'bg-primary/10' : '' }}">
                                                            <td class="py-3 px-4">
                                                                <div class="flex items-center gap-2">
                                                                    <div class="w-1 h-4 rounded-full {{ $i < 2 ? 'bg-primary' : 'bg-white/10' }} shrink-0"></div>
                                                                    <span class="font-extrabold text-sm {{ $i < 2 ? 'text-white' : 'text-white/60' }} truncate">{{ $gt->team->team_name }}</span>
                                                                </div>
                                                            </td>
                                                            <td class="py-3 px-2 text-center text-xs text-white/60 font-bold">{{ $gt->played }}</td>
                                                            <td class="py-3 px-2 text-center text-xs text-emerald-400 font-bold">{{ $gt->won }}</td>
                                                            <td class="py-3 px-2 text-center text-xs text-white/40 font-bold">{{ $gt->drawn }}</td>
                                                            <td class="py-3 px-2 text-center text-xs text-red-400 font-bold">{{ $gt->lost }}</td>
                                                            <td class="py-3 px-2 text-center text-xs text-yellow-300 font-black tabular-nums">{{ $gt->goals_for }}</td>
                                                            <td class="py-3 px-2 text-center text-xs font-black {{ $gt->goal_difference > 0 ? 'text-emerald-400' : ($gt->goal_difference < 0 ? 'text-red-400' : 'text-white/40') }}">
                                                                {{ $gt->goal_difference > 0 ? '+'.$gt->goal_difference : $gt->goal_difference }}
                                                            </td>
                                                            <td class="py-3 px-4 text-center font-black text-base {{ $i < 2 ? 'text-primary' : 'text-white/50' }}">{{ $gt->points }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                        {{-- ========================================================================= --}}
                        {{-- 3. SPLIT BRACKET VIEW (BLOK KIRI / BLOK KANAN FOR P32 TOURNAMENTS)         --}}
                        {{-- ========================================================================= --}}
                        @elseif($slide['type'] === 'knockout_split')
                            @php 
                                $isTrophy = $slide['stage'] === 'trophy_knockout';
                                $feederRounds = $slide['feederRounds'];
                                $isLeft = $slide['branch'] === 'left';
                            @endphp

                            <div class="h-full flex items-stretch gap-4 min-h-0 overflow-x-auto pb-1" style="scrollbar-width:none;">
                                @foreach($feederRounds as $roundName => $matches)
                                    @php 
                                        $half = ceil($matches->count() / 2);
                                        $branchMatches = $isLeft ? $matches->take($half) : $matches->slice($half);
                                        $isSemi = in_array($roundName, ['Separuh Akhir', 'Semi Final']);
                                    @endphp
                                    <div class="flex-1 min-w-[220px] flex flex-col min-h-0 bg-white/[0.02] border border-white/10 rounded-2xl p-3 backdrop-blur-sm {{ $isSemi ? 'border-amber-500/30 bg-amber-500/5' : '' }}">
                                        
                                        {{-- Column Header --}}
                                        <div class="flex items-center justify-between pb-2.5 mb-2 border-b border-white/10 shrink-0">
                                            <div class="flex items-center gap-2">
                                                <div class="w-1.5 h-3.5 rounded-full {{ $isSemi ? 'bg-amber-400' : ($isTrophy ? 'bg-primary' : 'bg-slate-400') }}"></div>
                                                <h3 class="text-xs font-black tracking-wider uppercase {{ $isSemi ? 'text-yellow-300 font-black' : 'text-white' }}">
                                                    {{ $roundName }}
                                                </h3>
                                            </div>
                                            @if($isSemi)
                                                <span class="text-[9px] font-black px-2 py-0.5 rounded bg-yellow-400/20 text-yellow-300 border border-yellow-400/30 uppercase">
                                                    KE FINAL 🏆
                                                </span>
                                            @else
                                                <span class="text-[9px] font-black px-1.5 py-0.5 rounded bg-white/10 text-white/60">
                                                    {{ count($branchMatches) }} Match
                                                </span>
                                            @endif
                                        </div>

                                        {{-- Matches List (Red vs Blue Corner) --}}
                                        <div class="flex-1 flex flex-col justify-around gap-2.5 min-h-0">
                                            @foreach($branchMatches as $match)
                                                @php
                                                    $done = $match->status === 'completed' || $match->winner_team_id;
                                                    $homeW = $match->winner_team_id && $match->winner_team_id == $match->home_team_id;
                                                    $awayW = $match->winner_team_id && $match->winner_team_id == $match->away_team_id;
                                                @endphp
                                                <div class="rounded-xl overflow-hidden border {{ $isSemi ? 'border-amber-500/40 bg-black/70 shadow-lg shadow-amber-500/10' : ($done ? 'border-white/20 bg-black/60' : 'border-white/10 bg-black/40') }}">
                                                    {{-- Card Top Meta --}}
                                                    <div class="flex items-center justify-between px-3 py-1 bg-white/5 border-b border-white/5">
                                                        <span class="text-[9px] font-black {{ $match->field_number ? 'text-yellow-300' : 'text-white/40' }} uppercase tracking-wider">
                                                            {{ $match->field_number ? (is_numeric($match->field_number) ? 'PADANG '.$match->field_number : $match->field_number) : 'PADANG TBD' }}
                                                        </span>
                                                        @if($done)
                                                            <span class="text-[8px] font-black text-emerald-400 bg-emerald-500/20 px-1.5 py-0.2 rounded">SELESAI</span>
                                                        @elseif($match->status === 'in_progress')
                                                            <span class="text-[8px] font-black text-red-400 bg-red-500/20 px-1.5 py-0.2 rounded animate-pulse">LIVE</span>
                                                        @else
                                                            <span class="text-[8px] font-extrabold text-white/30">M#{{ $match->bracket_position ?? $match->id }}</span>
                                                        @endif
                                                    </div>

                                                    {{-- Teams List (Home: Red, Away: Blue) --}}
                                                    <div class="divide-y divide-white/5">
                                                        {{-- Home Team (Red Pill) --}}
                                                        <div class="flex items-center justify-between px-3 py-2 {{ $homeW ? 'bg-emerald-500/20' : 'bg-rose-500/[0.03]' }}">
                                                            <div class="flex items-center gap-1.5 flex-1 min-w-0 pr-1.5">
                                                                <span class="w-1 h-3 rounded-full {{ $homeW ? 'bg-emerald-400' : 'bg-rose-500' }} shrink-0"></span>
                                                                <span class="font-extrabold text-xs truncate {{ $homeW ? 'text-emerald-300 font-black' : ($match->homeTeam ? 'text-white' : 'text-white/30 italic') }}">
                                                                    @if($homeW) <span class="text-emerald-400 font-black mr-0.5">✓</span> @endif
                                                                    {{ $match->homeTeam ? $match->homeTeam->team_name : 'Menunggu' }}
                                                                </span>
                                                            </div>
                                                            <span class="font-black text-xs tabular-nums px-2 py-0.5 rounded bg-black/60 border border-white/10 {{ $homeW ? 'text-emerald-400 border-emerald-500/40' : ($match->home_score !== null ? 'text-rose-300' : 'text-white/20') }}">
                                                                {{ $match->home_score !== null ? $match->home_score : '-' }}
                                                            </span>
                                                        </div>

                                                        {{-- Away Team (Blue Pill) --}}
                                                        <div class="flex items-center justify-between px-3 py-2 {{ $awayW ? 'bg-emerald-500/20' : 'bg-sky-500/[0.03]' }}">
                                                            <div class="flex items-center gap-1.5 flex-1 min-w-0 pr-1.5">
                                                                <span class="w-1 h-3 rounded-full {{ $awayW ? 'bg-emerald-400' : 'bg-sky-400' }} shrink-0"></span>
                                                                <span class="font-extrabold text-xs truncate {{ $awayW ? 'text-emerald-300 font-black' : ($match->awayTeam ? 'text-white' : 'text-white/30 italic') }}">
                                                                    @if($awayW) <span class="text-emerald-400 font-black mr-0.5">✓</span> @endif
                                                                    {{ $match->awayTeam ? $match->awayTeam->team_name : 'Menunggu' }}
                                                                </span>
                                                            </div>
                                                            <span class="font-black text-xs tabular-nums px-2 py-0.5 rounded bg-black/60 border border-white/10 {{ $awayW ? 'text-emerald-400 border-emerald-500/40' : ($match->away_score !== null ? 'text-sky-300' : 'text-white/20') }}">
                                                                {{ $match->away_score !== null ? $match->away_score : '-' }}
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                        {{-- ========================================================================= --}}
                        {{-- 4. UNIFIED DUAL-WING BRACKET + TOP 5 OFFICIAL RANKINGS ON THE RIGHT       --}}
                        {{-- ========================================================================= --}}
                        @elseif($slide['type'] === 'knockout')
                            @php 
                                $isTrophy = $slide['stage'] === 'trophy_knockout';
                                $feederRounds = $slide['feederRounds'];
                                $finalMatch = $slide['finalMatch'];
                                $thirdMatch = $slide['thirdMatch'];
                                $rankings = $slide['rankings'];
                            @endphp

                            <div class="h-full flex items-stretch gap-3 min-h-0 overflow-x-auto pb-1" style="scrollbar-width:none;">
                                
                                {{-- ------------------------------------------------------------- --}}
                                {{-- A. LEFT WING (BLOK KIRI: Earliest Round ➡️ Separuh Akhir)      --}}
                                {{-- ------------------------------------------------------------- --}}
                                @foreach($feederRounds as $roundName => $matches)
                                    @php 
                                        $half = ceil($matches->count() / 2);
                                        $leftMatches = $matches->take($half);
                                    @endphp
                                    <div class="flex-1 min-w-[170px] flex flex-col min-h-0 bg-white/[0.02] border border-white/10 rounded-2xl p-2.5 backdrop-blur-sm">
                                        {{-- Header --}}
                                        <div class="flex items-center justify-between pb-2 mb-1.5 border-b border-white/10 shrink-0">
                                            <div class="flex items-center gap-1.5">
                                                <div class="w-1.5 h-3.5 rounded-full {{ $isTrophy ? 'bg-amber-400' : 'bg-slate-300' }}"></div>
                                                <h3 class="text-[11px] font-black tracking-wider uppercase {{ $isTrophy ? 'text-amber-200' : 'text-slate-200' }}">
                                                    {{ $roundName }}
                                                </h3>
                                            </div>
                                            <span class="text-[9px] font-black px-1.5 py-0.5 rounded bg-white/10 text-white/70">KIRI</span>
                                        </div>

                                        {{-- Matches List (Red vs Blue Corner) --}}
                                        <div class="flex-1 flex flex-col justify-around gap-2 min-h-0">
                                            @foreach($leftMatches as $match)
                                                @php
                                                    $done = $match->status === 'completed' || $match->winner_team_id;
                                                    $homeW = $match->winner_team_id && $match->winner_team_id == $match->home_team_id;
                                                    $awayW = $match->winner_team_id && $match->winner_team_id == $match->away_team_id;
                                                @endphp
                                                <div class="rounded-xl overflow-hidden border {{ $done ? 'border-white/20 bg-black/60' : 'border-white/10 bg-black/40' }} shadow-md">
                                                    {{-- Card Top Meta --}}
                                                    <div class="flex items-center justify-between px-2.5 py-1 bg-white/5 border-b border-white/5">
                                                        <span class="text-[9px] font-black {{ $match->field_number ? 'text-yellow-300' : 'text-white/40' }} uppercase tracking-wider">
                                                            {{ $match->field_number ? (is_numeric($match->field_number) ? 'P.'.$match->field_number : $match->field_number) : 'PADANG TBD' }}
                                                        </span>
                                                        @if($done)
                                                            <span class="text-[8px] font-black text-emerald-400 bg-emerald-500/20 px-1 py-0.2 rounded">SELESAI</span>
                                                        @elseif($match->status === 'in_progress')
                                                            <span class="text-[8px] font-black text-red-400 bg-red-500/20 px-1 py-0.2 rounded animate-pulse">LIVE</span>
                                                        @else
                                                            <span class="text-[8px] font-extrabold text-white/30">M#{{ $match->bracket_position ?? $match->id }}</span>
                                                        @endif
                                                    </div>

                                                    {{-- Teams List (Home: Red, Away: Blue) --}}
                                                    <div class="divide-y divide-white/5">
                                                        <div class="flex items-center justify-between px-2.5 py-1.5 {{ $homeW ? 'bg-emerald-500/20' : 'bg-rose-500/[0.03]' }}">
                                                            <div class="flex items-center gap-1.5 flex-1 min-w-0 pr-1">
                                                                <span class="w-1 h-3 rounded-full {{ $homeW ? 'bg-emerald-400' : 'bg-rose-500' }} shrink-0"></span>
                                                                <span class="font-extrabold text-[11px] truncate {{ $homeW ? 'text-emerald-300 font-black' : ($match->homeTeam ? 'text-white' : 'text-white/30 italic') }}">
                                                                    @if($homeW) <span class="text-emerald-400 font-black mr-0.5">✓</span> @endif
                                                                    {{ $match->homeTeam ? $match->homeTeam->team_name : 'Menunggu' }}
                                                                </span>
                                                            </div>
                                                            <span class="font-black text-xs tabular-nums px-1.5 py-0.5 rounded bg-black/60 border border-white/10 {{ $homeW ? 'text-emerald-400 border-emerald-500/40' : ($match->home_score !== null ? 'text-rose-300' : 'text-white/20') }}">
                                                                {{ $match->home_score !== null ? $match->home_score : '-' }}
                                                            </span>
                                                        </div>
                                                        <div class="flex items-center justify-between px-2.5 py-1.5 {{ $awayW ? 'bg-emerald-500/20' : 'bg-sky-500/[0.03]' }}">
                                                            <div class="flex items-center gap-1.5 flex-1 min-w-0 pr-1">
                                                                <span class="w-1 h-3 rounded-full {{ $awayW ? 'bg-emerald-400' : 'bg-sky-400' }} shrink-0"></span>
                                                                <span class="font-extrabold text-[11px] truncate {{ $awayW ? 'text-emerald-300 font-black' : ($match->awayTeam ? 'text-white' : 'text-white/30 italic') }}">
                                                                    @if($awayW) <span class="text-emerald-400 font-black mr-0.5">✓</span> @endif
                                                                    {{ $match->awayTeam ? $match->awayTeam->team_name : 'Menunggu' }}
                                                                </span>
                                                            </div>
                                                            <span class="font-black text-xs tabular-nums px-1.5 py-0.5 rounded bg-black/60 border border-white/10 {{ $awayW ? 'text-emerald-400 border-emerald-500/40' : ($match->away_score !== null ? 'text-sky-300' : 'text-white/20') }}">
                                                                {{ $match->away_score !== null ? $match->away_score : '-' }}
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach

                                {{-- ------------------------------------------------------------- --}}
                                {{-- B. CENTER STAGE (PENTAS AKHIR & TEMPAT KE-3)                   --}}
                                {{-- ------------------------------------------------------------- --}}
                                <div class="flex-1 min-w-[210px] flex flex-col justify-center gap-3.5 min-h-0 bg-gradient-to-b from-amber-500/10 via-black/40 to-black/60 border-2 border-amber-500/40 rounded-3xl p-3 shadow-2xl shadow-amber-500/10">
                                    
                                    {{-- 🏆 FINAL MATCH --}}
                                    <div class="flex flex-col">
                                        <div class="flex items-center justify-center gap-2 pb-2 mb-1.5 border-b border-amber-500/30">
                                            <span class="text-xl animate-bounce">🏆</span>
                                            <h3 class="text-xs font-black text-yellow-300 tracking-widest uppercase drop-shadow">
                                                Pentas Akhir
                                            </h3>
                                        </div>

                                        @if($finalMatch)
                                            @php
                                                $fDone = $finalMatch->status === 'completed' || $finalMatch->winner_team_id;
                                                $fHomeW = $finalMatch->winner_team_id && $finalMatch->winner_team_id == $finalMatch->home_team_id;
                                                $fAwayW = $finalMatch->winner_team_id && $finalMatch->winner_team_id == $finalMatch->away_team_id;
                                            @endphp
                                            <div class="rounded-2xl overflow-hidden border-2 border-amber-500/50 bg-black/80 shadow-xl shadow-amber-500/15">
                                                <div class="flex items-center justify-between px-3 py-1 bg-amber-500/20 border-b border-amber-500/20">
                                                    <span class="text-[9px] font-black text-yellow-300 uppercase tracking-wider">
                                                        {{ $finalMatch->field_number ? (is_numeric($finalMatch->field_number) ? 'PADANG '.$finalMatch->field_number : $finalMatch->field_number) : 'PADANG AKHIR' }}
                                                    </span>
                                                    @if($fDone)
                                                        <span class="text-[8px] font-black text-emerald-400 bg-emerald-500/20 px-1.5 py-0.5 rounded">JUARA DITENTUKAN</span>
                                                    @elseif($finalMatch->status === 'in_progress')
                                                        <span class="text-[8px] font-black text-red-400 bg-red-500/20 px-1.5 py-0.5 rounded animate-pulse">LIVE FINAL</span>
                                                    @else
                                                        <span class="text-[8px] font-black text-yellow-400/80">PENENTUAN JUARA</span>
                                                    @endif
                                                </div>
                                                <div class="divide-y divide-white/10">
                                                    <div class="flex items-center justify-between px-2.5 py-2 {{ $fHomeW ? 'bg-emerald-500/20' : 'bg-rose-500/[0.04]' }}">
                                                        <div class="flex items-center gap-1.5 flex-1 min-w-0 pr-1">
                                                            <span class="w-1.5 h-3.5 rounded-full {{ $fHomeW ? 'bg-emerald-400' : 'bg-rose-500' }} shrink-0"></span>
                                                            <span class="font-black text-[11px] truncate {{ $fHomeW ? 'text-emerald-300 text-xs' : ($finalMatch->homeTeam ? 'text-white' : 'text-white/30 italic') }}">
                                                                @if($fHomeW) 👑 @endif
                                                                {{ $finalMatch->homeTeam ? $finalMatch->homeTeam->team_name : 'Pemenang SF 1' }}
                                                            </span>
                                                        </div>
                                                        <span class="font-black text-sm tabular-nums px-2 py-0.5 rounded-lg bg-black border border-white/20 {{ $fHomeW ? 'text-emerald-400 border-emerald-400 font-black' : ($finalMatch->home_score !== null ? 'text-rose-300' : 'text-white/30') }}">
                                                            {{ $finalMatch->home_score !== null ? $finalMatch->home_score : '-' }}
                                                        </span>
                                                    </div>
                                                    <div class="flex items-center justify-between px-2.5 py-2 {{ $fAwayW ? 'bg-emerald-500/20' : 'bg-sky-500/[0.04]' }}">
                                                        <div class="flex items-center gap-1.5 flex-1 min-w-0 pr-1">
                                                            <span class="w-1.5 h-3.5 rounded-full {{ $fAwayW ? 'bg-emerald-400' : 'bg-sky-400' }} shrink-0"></span>
                                                            <span class="font-black text-[11px] truncate {{ $fAwayW ? 'text-emerald-300 text-xs' : ($finalMatch->awayTeam ? 'text-white' : 'text-white/30 italic') }}">
                                                                @if($fAwayW) 👑 @endif
                                                                {{ $finalMatch->awayTeam ? $finalMatch->awayTeam->team_name : 'Pemenang SF 2' }}
                                                            </span>
                                                        </div>
                                                        <span class="font-black text-sm tabular-nums px-2 py-0.5 rounded-lg bg-black border border-white/20 {{ $fAwayW ? 'text-emerald-400 border-emerald-400 font-black' : ($finalMatch->away_score !== null ? 'text-sky-300' : 'text-white/30') }}">
                                                            {{ $finalMatch->away_score !== null ? $finalMatch->away_score : '-' }}
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                    </div>

                                    {{-- 🥉 3RD PLACE MATCH --}}
                                    @if($thirdMatch)
                                        @php
                                            $tDone = $thirdMatch->status === 'completed' || $thirdMatch->winner_team_id;
                                            $tHomeW = $thirdMatch->winner_team_id && $thirdMatch->winner_team_id == $thirdMatch->home_team_id;
                                            $tAwayW = $thirdMatch->winner_team_id && $thirdMatch->winner_team_id == $thirdMatch->away_team_id;
                                        @endphp
                                        <div class="flex flex-col pt-1.5 border-t border-white/10">
                                            <div class="flex items-center justify-center gap-1 pb-1 mb-1">
                                                <span class="text-xs">🥉</span>
                                                <h4 class="text-[9px] font-black text-slate-300 tracking-wider uppercase">
                                                    Tempat Ke-3 & 4
                                                </h4>
                                            </div>
                                            <div class="rounded-xl overflow-hidden border border-white/10 bg-black/60 shadow">
                                                <div class="divide-y divide-white/5">
                                                    <div class="flex items-center justify-between px-2 py-1 {{ $tHomeW ? 'bg-emerald-500/20' : 'bg-rose-500/[0.03]' }}">
                                                        <div class="flex items-center gap-1 flex-1 min-w-0 pr-1">
                                                            <span class="w-1 h-2.5 rounded-full {{ $tHomeW ? 'bg-emerald-400' : 'bg-rose-500' }} shrink-0"></span>
                                                            <span class="font-bold text-[10px] truncate {{ $tHomeW ? 'text-emerald-300 font-black' : ($thirdMatch->homeTeam ? 'text-white' : 'text-white/30 italic') }}">
                                                                @if($tHomeW) ✓ @endif
                                                                {{ $thirdMatch->homeTeam ? $thirdMatch->homeTeam->team_name : 'Kalah SF 1' }}
                                                            </span>
                                                        </div>
                                                        <span class="font-black text-[10px] tabular-nums px-1.5 py-0.5 rounded bg-black/60 border border-white/10 {{ $tHomeW ? 'text-emerald-400' : ($thirdMatch->home_score !== null ? 'text-rose-300' : 'text-white/40') }}">
                                                            {{ $thirdMatch->home_score !== null ? $thirdMatch->home_score : '-' }}
                                                        </span>
                                                    </div>
                                                    <div class="flex items-center justify-between px-2 py-1 {{ $tAwayW ? 'bg-emerald-500/20' : 'bg-sky-500/[0.03]' }}">
                                                        <div class="flex items-center gap-1 flex-1 min-w-0 pr-1">
                                                            <span class="w-1 h-2.5 rounded-full {{ $tAwayW ? 'bg-emerald-400' : 'bg-sky-400' }} shrink-0"></span>
                                                            <span class="font-bold text-[10px] truncate {{ $tAwayW ? 'text-emerald-300 font-black' : ($thirdMatch->awayTeam ? 'text-white' : 'text-white/30 italic') }}">
                                                                @if($tAwayW) ✓ @endif
                                                                {{ $thirdMatch->awayTeam ? $thirdMatch->awayTeam->team_name : 'Kalah SF 2' }}
                                                            </span>
                                                        </div>
                                                        <span class="font-black text-[10px] tabular-nums px-1.5 py-0.5 rounded bg-black/60 border border-white/10 {{ $tAwayW ? 'text-emerald-400' : ($thirdMatch->away_score !== null ? 'text-sky-300' : 'text-white/40') }}">
                                                            {{ $thirdMatch->away_score !== null ? $thirdMatch->away_score : '-' }}
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endif

                                </div>

                                {{-- ------------------------------------------------------------- --}}
                                {{-- C. RIGHT WING (BLOK KANAN: Separuh Akhir ⬅️ Earliest Round)    --}}
                                {{-- ------------------------------------------------------------- --}}
                                @foreach($feederRounds->reverse() as $roundName => $matches)
                                    @php 
                                        $half = ceil($matches->count() / 2);
                                        $rightMatches = $matches->slice($half);
                                    @endphp
                                    <div class="flex-1 min-w-[170px] flex flex-col min-h-0 bg-white/[0.02] border border-white/10 rounded-2xl p-2.5 backdrop-blur-sm">
                                        {{-- Header --}}
                                        <div class="flex items-center justify-between pb-2 mb-1.5 border-b border-white/10 shrink-0">
                                            <span class="text-[9px] font-black px-1.5 py-0.5 rounded bg-white/10 text-white/70">KANAN</span>
                                            <div class="flex items-center gap-1.5">
                                                <h3 class="text-[11px] font-black tracking-wider uppercase {{ $isTrophy ? 'text-amber-200' : 'text-slate-200' }}">
                                                    {{ $roundName }}
                                                </h3>
                                                <div class="w-1.5 h-3.5 rounded-full {{ $isTrophy ? 'bg-amber-400' : 'bg-slate-300' }}"></div>
                                            </div>
                                        </div>

                                        {{-- Matches List (Red vs Blue Corner) --}}
                                        <div class="flex-1 flex flex-col justify-around gap-2 min-h-0">
                                            @foreach($rightMatches as $match)
                                                @php
                                                    $done = $match->status === 'completed' || $match->winner_team_id;
                                                    $homeW = $match->winner_team_id && $match->winner_team_id == $match->home_team_id;
                                                    $awayW = $match->winner_team_id && $match->winner_team_id == $match->away_team_id;
                                                @endphp
                                                <div class="rounded-xl overflow-hidden border {{ $done ? 'border-white/20 bg-black/60' : 'border-white/10 bg-black/40' }} shadow-md">
                                                    {{-- Card Top Meta --}}
                                                    <div class="flex items-center justify-between px-2.5 py-1 bg-white/5 border-b border-white/5">
                                                        @if($done)
                                                            <span class="text-[8px] font-black text-emerald-400 bg-emerald-500/20 px-1 py-0.2 rounded">SELESAI</span>
                                                        @elseif($match->status === 'in_progress')
                                                            <span class="text-[8px] font-black text-red-400 bg-red-500/20 px-1 py-0.2 rounded animate-pulse">LIVE</span>
                                                        @else
                                                            <span class="text-[8px] font-extrabold text-white/30">M#{{ $match->bracket_position ?? $match->id }}</span>
                                                        @endif
                                                        <span class="text-[9px] font-black {{ $match->field_number ? 'text-yellow-300' : 'text-white/40' }} uppercase tracking-wider">
                                                            {{ $match->field_number ? (is_numeric($match->field_number) ? 'P.'.$match->field_number : $match->field_number) : 'PADANG TBD' }}
                                                        </span>
                                                    </div>

                                                    {{-- Teams List (Home: Red, Away: Blue) --}}
                                                    <div class="divide-y divide-white/5">
                                                        <div class="flex items-center justify-between px-2.5 py-1.5 {{ $homeW ? 'bg-emerald-500/20' : 'bg-rose-500/[0.03]' }}">
                                                            <div class="flex items-center gap-1.5 flex-1 min-w-0 pr-1">
                                                                <span class="w-1 h-3 rounded-full {{ $homeW ? 'bg-emerald-400' : 'bg-rose-500' }} shrink-0"></span>
                                                                <span class="font-extrabold text-[11px] truncate {{ $homeW ? 'text-emerald-300 font-black' : ($match->homeTeam ? 'text-white' : 'text-white/30 italic') }}">
                                                                    @if($homeW) <span class="text-emerald-400 font-black mr-0.5">✓</span> @endif
                                                                    {{ $match->homeTeam ? $match->homeTeam->team_name : 'Menunggu' }}
                                                                </span>
                                                            </div>
                                                            <span class="font-black text-xs tabular-nums px-1.5 py-0.5 rounded bg-black/60 border border-white/10 {{ $homeW ? 'text-emerald-400 border-emerald-500/40' : ($match->home_score !== null ? 'text-rose-300' : 'text-white/20') }}">
                                                                {{ $match->home_score !== null ? $match->home_score : '-' }}
                                                            </span>
                                                        </div>
                                                        <div class="flex items-center justify-between px-2.5 py-1.5 {{ $awayW ? 'bg-emerald-500/20' : 'bg-sky-500/[0.03]' }}">
                                                            <div class="flex items-center gap-1.5 flex-1 min-w-0 pr-1">
                                                                <span class="w-1 h-3 rounded-full {{ $awayW ? 'bg-emerald-400' : 'bg-sky-400' }} shrink-0"></span>
                                                                <span class="font-extrabold text-[11px] truncate {{ $awayW ? 'text-emerald-300 font-black' : ($match->awayTeam ? 'text-white' : 'text-white/30 italic') }}">
                                                                    @if($awayW) <span class="text-emerald-400 font-black mr-0.5">✓</span> @endif
                                                                    {{ $match->awayTeam ? $match->awayTeam->team_name : 'Menunggu' }}
                                                                </span>
                                                            </div>
                                                            <span class="font-black text-xs tabular-nums px-1.5 py-0.5 rounded bg-black/60 border border-white/10 {{ $awayW ? 'text-emerald-400 border-emerald-500/40' : ($match->away_score !== null ? 'text-sky-300' : 'text-white/20') }}">
                                                                {{ $match->away_score !== null ? $match->away_score : '-' }}
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach

                                {{-- ------------------------------------------------------------- --}}
                                {{-- D. OFFICIAL TOP 5 RANKINGS (KEDUDUKAN 1 HINGGA 5)             --}}
                                {{-- ------------------------------------------------------------- --}}
                                <div class="flex-1 min-w-[210px] flex flex-col min-h-0 bg-gradient-to-b from-amber-500/10 via-black/50 to-black/70 border-2 border-amber-500/40 rounded-3xl p-3 shadow-2xl backdrop-blur-md">
                                    
                                    {{-- Header --}}
                                    <div class="flex items-center justify-between pb-2 mb-2 border-b border-amber-500/30 shrink-0">
                                        <div class="flex items-center gap-1.5">
                                            <span class="text-base">🏆</span>
                                            <h3 class="text-xs font-black tracking-wider uppercase text-yellow-300 drop-shadow">
                                                Kedudukan Rasmi
                                            </h3>
                                        </div>
                                        @if($rankings['isFinished'])
                                            <span class="text-[8px] font-black px-2 py-0.5 rounded bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 uppercase animate-pulse">
                                                TAMAT
                                            </span>
                                        @else
                                            <span class="text-[8px] font-black px-1.5 py-0.5 rounded bg-white/10 text-white/50 uppercase">
                                                TOP 5
                                            </span>
                                        @endif
                                    </div>

                                    {{-- Rankings List (1st to 5th) --}}
                                    <div class="flex-1 flex flex-col justify-between gap-1.5 min-h-0">
                                        
                                        {{-- 🥇 1st Place (JUARA) --}}
                                        <div class="rounded-xl overflow-hidden border border-yellow-500/50 bg-gradient-to-r from-yellow-500/20 via-black/80 to-black/90 p-2 shadow-md">
                                            <div class="flex items-center gap-2 overflow-hidden">
                                                <span class="text-lg shrink-0">🥇</span>
                                                <div class="overflow-hidden">
                                                    <span class="text-[8px] font-black text-yellow-400 uppercase tracking-widest block leading-tight">JUARA</span>
                                                    <p class="font-black text-xs text-white truncate leading-tight mt-0.5">
                                                        {{ $rankings['first']->team_name ?? 'Menunggu Final' }}
                                                    </p>
                                                    @if($rankings['first'])
                                                        <p class="text-[8px] text-white/40 truncate font-bold uppercase">{{ $rankings['first']->school_name }}</p>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>

                                        {{-- 🥈 2nd Place (NAIB JUARA) --}}
                                        <div class="rounded-xl overflow-hidden border border-slate-300/40 bg-gradient-to-r from-slate-400/15 via-black/80 to-black/90 p-2 shadow-sm">
                                            <div class="flex items-center gap-2 overflow-hidden">
                                                <span class="text-lg shrink-0">🥈</span>
                                                <div class="overflow-hidden">
                                                    <span class="text-[8px] font-black text-slate-300 uppercase tracking-widest block leading-tight">NAIB JUARA</span>
                                                    <p class="font-black text-xs text-white truncate leading-tight mt-0.5">
                                                        {{ $rankings['second']->team_name ?? 'Menunggu Final' }}
                                                    </p>
                                                    @if($rankings['second'])
                                                        <p class="text-[8px] text-white/40 truncate font-bold uppercase">{{ $rankings['second']->school_name }}</p>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>

                                        {{-- 🥉 3rd Place (TEMPAT KE-3) --}}
                                        <div class="rounded-xl overflow-hidden border border-amber-600/40 bg-gradient-to-r from-amber-700/15 via-black/80 to-black/90 p-2 shadow-sm">
                                            <div class="flex items-center gap-2 overflow-hidden">
                                                <span class="text-lg shrink-0">🥉</span>
                                                <div class="overflow-hidden">
                                                    <span class="text-[8px] font-black text-amber-400 uppercase tracking-widest block leading-tight">TEMPAT KE-3</span>
                                                    <p class="font-black text-xs text-white truncate leading-tight mt-0.5">
                                                        {{ $rankings['third']->team_name ?? 'Menunggu Penentuan' }}
                                                    </p>
                                                    @if($rankings['third'])
                                                        <p class="text-[8px] text-white/40 truncate font-bold uppercase">{{ $rankings['third']->school_name }}</p>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>

                                        {{-- 🏅 4th Place (TEMPAT KE-4) --}}
                                        <div class="rounded-xl overflow-hidden border border-white/10 bg-black/60 p-2">
                                            <div class="flex items-center gap-2 overflow-hidden">
                                                <span class="text-sm shrink-0 opacity-80">🏅</span>
                                                <div class="overflow-hidden">
                                                    <span class="text-[8px] font-black text-white/50 uppercase tracking-widest block leading-tight">TEMPAT KE-4</span>
                                                    <p class="font-extrabold text-xs text-white/90 truncate leading-tight mt-0.5">
                                                        {{ $rankings['fourth']->team_name ?? 'Menunggu Penentuan' }}
                                                    </p>
                                                    @if($rankings['fourth'])
                                                        <p class="text-[8px] text-white/40 truncate font-bold uppercase">{{ $rankings['fourth']->school_name }}</p>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>

                                        {{-- 🎖️ 5th Place (TEMPAT KE-5 - TERBAIK SUKU AKHIR) --}}
                                        <div class="rounded-xl overflow-hidden border border-emerald-500/30 bg-gradient-to-r from-emerald-500/10 via-black/60 to-black/80 p-2">
                                            <div class="flex items-center gap-2 overflow-hidden">
                                                <span class="text-sm shrink-0">🎖️</span>
                                                <div class="overflow-hidden">
                                                    <div class="flex items-center gap-1.5 flex-wrap">
                                                        <span class="text-[8px] font-black text-emerald-400 uppercase tracking-widest leading-tight">TEMPAT KE-5</span>
                                                        @if($rankings['fifth_goals'] !== null)
                                                            <span class="text-[7px] font-black text-emerald-300 bg-emerald-500/20 px-1 py-0.2 rounded border border-emerald-500/30">
                                                                {{ $rankings['fifth_goals'] }} JUMLAH GOL
                                                            </span>
                                                        @endif
                                                    </div>
                                                    <p class="font-extrabold text-xs text-white truncate leading-tight mt-0.5">
                                                        {{ $rankings['fifth']->team_name ?? 'Menunggu Suku Akhir' }}
                                                    </p>
                                                    @if($rankings['fifth'])
                                                        <p class="text-[8px] text-white/40 truncate font-bold uppercase">{{ $rankings['fifth']->school_name }}</p>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>

                                    </div>
                                </div>

                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        @endif
    </div>

    {{-- ========================================================== --}}
    {{-- ========================================================== --}}
    {{-- CALLING OVERLAY (PANGGILAN PASUKAN) DENGAN PENGGERA AUDIO  --}}
    {{-- ========================================================== --}}
    @php $callingMatches = $this->calledMatches(); @endphp
    @if(count($callingMatches) > 0)
        <div x-data="{
                playAlarm() {
                    try {
                        const AudioCtx = window.AudioContext || window.webkitAudioContext;
                        if (!AudioCtx) return;
                        if (!window._mircAudioCtx) {
                            window._mircAudioCtx = new AudioCtx();
                        }
                        const ctx = window._mircAudioCtx;
                        if (ctx.state === 'suspended') {
                            ctx.resume();
                        }

                        const now = ctx.currentTime;

                        // Master lowpass filter and gain for crisp, pleasant alert presence
                        const filter = ctx.createBiquadFilter();
                        filter.type = 'lowpass';
                        filter.frequency.setValueAtTime(3500, now);
                        filter.connect(ctx.destination);

                        const masterGain = ctx.createGain();
                        masterGain.gain.setValueAtTime(0.85, now);
                        masterGain.connect(filter);

                        // Really really short alert: snappy, high-attention double chime (total duration ~0.22s)
                        const notes = [
                            { freq: 880,  start: 0,    dur: 0.07 }, // Note 1: 880Hz (70ms)
                            { freq: 1320, start: 0.09, dur: 0.12 }  // Note 2: 1320Hz (120ms)
                        ];

                        notes.forEach(n => {
                            const t = now + n.start;
                            const osc = ctx.createOscillator();
                            const gain = ctx.createGain();

                            osc.type = 'triangle';
                            osc.frequency.setValueAtTime(n.freq, t);

                            gain.gain.setValueAtTime(0.0001, t);
                            gain.gain.exponentialRampToValueAtTime(0.85, t + 0.008);
                            gain.gain.setValueAtTime(0.85, t + n.dur - 0.02);
                            gain.gain.exponentialRampToValueAtTime(0.0001, t + n.dur);

                            osc.connect(gain);
                            gain.connect(masterGain);

                            osc.start(t);
                            osc.stop(t + n.dur + 0.01);
                        });

                    } catch (e) {
                        console.warn('Live TV Audio alert error:', e);
                    }
                },
                init() {
                    // Play single alarm chime when overlay appears
                    this.playAlarm();
                }
            }"
            x-init="init()"
            @click="if (window._mircAudioCtx && window._mircAudioCtx.state === 'suspended') window._mircAudioCtx.resume()"
            class="fixed inset-0 z-50 bg-black/90 backdrop-blur-2xl flex items-center justify-center p-10 animate-fade-in">
            <div class="w-full max-w-5xl flex flex-col items-center gap-8">
                <div class="flex items-center gap-5">
                    <svg class="w-12 h-12 text-yellow-400 animate-bounce" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                    <div class="text-center">
                        <p class="text-xs font-black text-yellow-400 tracking-[0.3em] uppercase mb-1.5">Panggilan Pasukan</p>
                        <h1 class="text-5xl font-black text-white tracking-widest uppercase drop-shadow-lg">SILA LAPOR DIRI</h1>
                    </div>
                    <svg class="w-12 h-12 text-yellow-400 animate-bounce" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                </div>
                <div class="grid {{ count($callingMatches) > 1 ? 'grid-cols-2' : 'grid-cols-1 max-w-xl' }} gap-6 w-full">
                    @foreach($callingMatches as $match)
                        <div class="rounded-3xl border-2 border-red-500/50 bg-gradient-to-br from-red-950/90 via-black/90 to-black p-8 shadow-2xl shadow-red-500/20">
                            <div class="text-center mb-6">
                                <div class="inline-flex items-center gap-3 bg-red-500/20 border border-red-500/40 rounded-2xl px-6 py-2.5 shadow-inner">
                                    <div class="w-2 h-2 rounded-full bg-red-400 animate-ping"></div>
                                    <span class="text-2xl font-black text-yellow-300 tracking-widest uppercase">
                                        {{ is_numeric($match->field_number) ? 'PADANG '.$match->field_number : strtoupper($match->field_number ?? '?') }}
                                    </span>
                                </div>
                                <p class="text-sm font-black text-white/50 uppercase tracking-widest mt-2.5">
                                    {{ optional(optional($match->group)->category)->name ?? '' }}
                                    @if($match->round_name) &nbsp;·&nbsp; {{ $match->round_name }} @endif
                                </p>
                            </div>
                            @if($match->group && $match->group->game_type === 'obstacle')
                                <div class="text-center">
                                    <h3 class="text-3xl font-black text-white">{{ $match->homeTeam->team_name ?? 'BYE' }}</h3>
                                    <p class="text-base text-white/50 font-bold uppercase mt-1.5">{{ $match->homeTeam->school_name ?? '' }}</p>
                                </div>
                            @else
                                <div class="grid grid-cols-[1fr_auto_1fr] gap-4 items-center">
                                    {{-- Red Corner --}}
                                    <div class="text-right">
                                        <span class="text-[10px] font-black text-rose-400 uppercase tracking-widest block mb-1">Sudut Merah</span>
                                        <h3 class="text-2xl font-black text-white leading-tight">{{ $match->homeTeam->team_name ?? 'BYE' }}</h3>
                                        <p class="text-xs text-white/50 font-bold uppercase mt-1">{{ $match->homeTeam->school_name ?? '' }}</p>
                                    </div>
                                    <div class="w-14 h-14 rounded-full bg-white/5 border border-white/10 flex items-center justify-center shadow-inner">
                                        <span class="text-base font-black text-white/30 italic">VS</span>
                                    </div>
                                    {{-- Blue Corner --}}
                                    <div class="text-left">
                                        <span class="text-[10px] font-black text-sky-400 uppercase tracking-widest block mb-1">Sudut Biru</span>
                                        <h3 class="text-2xl font-black text-white leading-tight">{{ $match->awayTeam->team_name ?? 'BYE' }}</h3>
                                        <p class="text-xs text-white/50 font-bold uppercase mt-1">{{ $match->awayTeam->school_name ?? '' }}</p>
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
                
                {{-- Bottom Status Notice --}}
                <div class="text-center">
                    <p class="text-xs font-black text-yellow-400/80 tracking-[0.25em] uppercase animate-pulse">
                        🚨 Sila segera lapor diri ke padang yang ditetapkan 🚨
                    </p>
                </div>
            </div>
        </div>
    @endif
</div>
