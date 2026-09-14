<?php

use App\Models\Team;
use App\Models\Group;
use App\Models\Category;
use App\Models\GroupTeam;
use App\Models\TournamentMatch;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;

new class extends Component {
    
    #[Url(as: 'game')]
    public string $selectedGame = ''; // '', 'isobot', 'sky_soccer', 'obstacle'

    #[Url(as: 'q')]
    public string $search = '';

    public array $activeCategoryTabs = []; // [sectionKey => 'trophy' | 'cup' | 'groups']

    public function setCategoryTab(string $sectionKey, string $tab)
    {
        $this->activeCategoryTabs[$sectionKey] = $tab;
    }

    public function getTeamTotalGoals(int $teamId): int
    {
        static $goalCache = [];
        if (isset($goalCache[$teamId])) {
            return $goalCache[$teamId];
        }

        $home = TournamentMatch::where('home_team_id', $teamId)
            ->where('status', 'completed')
            ->whereNotNull('home_score')
            ->sum('home_score');

        $away = TournamentMatch::where('away_team_id', $teamId)
            ->where('status', 'completed')
            ->whereNotNull('away_score')
            ->sum('away_score');

        $matchGoals = (int)($home + $away);
        $groupGoals = (int)(GroupTeam::where('team_id', $teamId)->value('goals_for') ?? 0);

        return $goalCache[$teamId] = max($matchGoals, $groupGoals);
    }

    #[Computed]
    public function resultsByGame()
    {
        $data = [];

        // All distinct tournament events: (game_type, category_id)
        $events = [
            // Isobot Soccer
            ['game_type' => 'isobot', 'category_id' => 1, 'game_name' => 'ISOBOT SOCCER', 'icon' => '🤖'],
            ['game_type' => 'isobot', 'category_id' => 2, 'game_name' => 'ISOBOT SOCCER', 'icon' => '🤖'],
            ['game_type' => 'isobot', 'category_id' => 3, 'game_name' => 'ISOBOT SOCCER', 'icon' => '🤖'],
            ['game_type' => 'isobot', 'category_id' => 4, 'game_name' => 'ISOBOT SOCCER', 'icon' => '🤖'],

            // Drone Sky Soccer
            ['game_type' => 'sky_soccer', 'category_id' => 5, 'game_name' => 'DRONE SKY SOCCER', 'icon' => '⚽'],
            ['game_type' => 'sky_soccer', 'category_id' => 6, 'game_name' => 'DRONE SKY SOCCER', 'icon' => '⚽'],

            // Drone Obstacle
            ['game_type' => 'obstacle', 'category_id' => 5, 'game_name' => 'DRONE OBSTACLE', 'icon' => '🛸'],
            ['game_type' => 'obstacle', 'category_id' => 6, 'game_name' => 'DRONE OBSTACLE', 'icon' => '🛸'],
        ];

        foreach ($events as $event) {
            $gt = $event['game_type'];
            $catId = $event['category_id'];

            // Filter by selectedGame if active
            if ($this->selectedGame && $this->selectedGame !== $gt) {
                continue;
            }

            try {
                $cat = Category::find($catId);
            } catch (\Throwable $e) {
                $cat = null;
            }
            if (!$cat) continue;

            $sectionKey = "{$gt}_{$catId}";

            if ($gt === 'obstacle') {
                // Drone Obstacle: Top 10 Fastest Times
                try {
                    $standings = GroupTeam::whereHas('group', function($q) use ($catId, $gt) {
                        $q->where('category_id', $catId)->where('game_type', $gt);
                    })
                    ->with('team')
                    ->where('goals_for', '>', 0)
                    ->orderBy('goals_for', 'asc')
                    ->limit(10)
                    ->get()
                    ->map(function($gtItem) {
                        $tf = $gtItem->goals_for;
                        $tM = floor($tf / 60000);
                        $tS = floor(($tf % 60000) / 1000);
                        $tMs = $tf % 1000;
                        $gtItem->time_formatted = sprintf('%02d:%02d.%03d', $tM, $tS, $tMs);
                        return $gtItem;
                    });
                } catch (\Throwable $e) {
                    $standings = collect();
                }

                $data[$sectionKey] = [
                    'key'         => $sectionKey,
                    'category'    => $cat,
                    'type'        => 'obstacle',
                    'game_type'   => 'obstacle',
                    'game_name'   => $event['game_name'],
                    'icon'        => $event['icon'],
                    'standings'   => $standings,
                ];
            } else {
                // Soccer Categories (Isobot / Sky Soccer)
                try {
                    $matches = TournamentMatch::with(['homeTeam', 'awayTeam'])
                        ->where('category_id', $catId)
                        ->where(function($q) use ($gt) {
                            $q->whereHas('homeTeam', fn($t) => $t->where('game_type', $gt))
                              ->orWhereHas('awayTeam', fn($t) => $t->where('game_type', $gt));
                        })
                        ->whereIn('stage', ['trophy_knockout', 'cup_knockout'])
                        ->get();
                } catch (\Throwable $e) {
                    $matches = collect();
                }

                $trophyRankings = $this->calculateStageRankings($matches, 'trophy_knockout', $catId);
                $hasCup = $matches->where('stage', 'cup_knockout')->isNotEmpty();
                $cupRankings = $hasCup ? $this->calculateStageRankings($matches, 'cup_knockout', $catId) : null;

                $hasKnockoutData = !empty($trophyRankings['first']);

                // Fetch full group standings
                try {
                    $groups = Group::where('category_id', $catId)
                        ->where('game_type', $gt)
                        ->orderBy('group_letter')
                        ->get();

                    $groupStandings = $groups->map(function($g) {
                        return [
                            'group'     => $g,
                            'standings' => $g->getStandings(),
                        ];
                    });
                } catch (\Throwable $e) {
                    $groupStandings = collect();
                }

                $data[$sectionKey] = [
                    'key'             => $sectionKey,
                    'category'        => $cat,
                    'type'            => 'soccer',
                    'game_type'       => $gt,
                    'game_name'       => $event['game_name'],
                    'icon'            => $event['icon'],
                    'has_knockouts'   => $hasKnockoutData,
                    'trophy'          => $trophyRankings,
                    'has_cup'         => $hasCup,
                    'cup'             => $cupRankings,
                    'groups'          => $groupStandings,
                ];
            }
        }

        return $data;
    }

    private function calculateStageRankings($allMatches, string $stage, int $categoryId): array
    {
        $matches = $allMatches->where('stage', $stage);
        if ($matches->isEmpty()) {
            return [
                'first' => null, 'second' => null, 'third' => null,
                'fourth' => null, 'fifth' => null, 'fifth_goals' => null
            ];
        }

        $finalMatch = $matches->firstWhere('round_name', 'Akhir');
        $thirdMatch = $matches->firstWhere('round_name', 'Penentuan Tempat Ke-3');
        $fifthMatch = $matches->firstWhere('round_name', 'Penentuan Tempat Ke-5');

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

        // 5th Place
        $fifthId = null;
        $fifthGoals = null;
        if ($fifthMatch && $fifthMatch->status === 'completed' && $fifthMatch->winner_team_id) {
            $fifthId = $fifthMatch->winner_team_id;
        } else {
            // Highest cumulative goals among QF losers
            $qfMatches = $matches->filter(fn($m) => in_array($m->round_name, ['Suku Akhir', 'Quarter Final']));
            $qfLosers = [];
            foreach ($qfMatches as $m) {
                if ($m->status === 'completed' && $m->winner_team_id) {
                    $loserId = ($m->winner_team_id == $m->home_team_id) ? $m->away_team_id : $m->home_team_id;
                    $loserMatches = $matches->filter(fn($match) => $match->status === 'completed' && ($match->home_team_id == $loserId || $match->away_team_id == $loserId));
                    $totalGoals = 0;
                    $totalConceded = 0;
                    foreach ($loserMatches as $lm) {
                        if ($lm->home_team_id == $loserId) {
                            $totalGoals += (int)($lm->home_score ?? 0);
                            $totalConceded += (int)($lm->away_score ?? 0);
                        } else {
                            $totalGoals += (int)($lm->away_score ?? 0);
                            $totalConceded += (int)($lm->home_score ?? 0);
                        }
                    }
                    $qfLosers[] = [
                        'team_id'         => $loserId,
                        'total_goals'     => $totalGoals,
                        'total_goal_diff' => $totalGoals - $totalConceded,
                    ];
                }
            }
            usort($qfLosers, function($a, $b) {
                if ($b['total_goals'] !== $a['total_goals']) {
                    return $b['total_goals'] <=> $a['total_goals'];
                }
                return $b['total_goal_diff'] <=> $a['total_goal_diff'];
            });
            $fifthId = !empty($qfLosers) ? $qfLosers[0]['team_id'] : null;
            $fifthGoals = !empty($qfLosers) ? $qfLosers[0]['total_goals'] : null;
        }

        $teamIds = array_filter([$firstId, $secondId, $thirdId, $fourthId, $fifthId]);
        $teamsMap = !empty($teamIds) ? Team::whereIn('id', $teamIds)->get()->keyBy('id') : collect();

        return [
            'first'       => $firstId ? ($teamsMap[$firstId] ?? null) : null,
            'second'      => $secondId ? ($teamsMap[$secondId] ?? null) : null,
            'third'       => $thirdId ? ($teamsMap[$thirdId] ?? null) : null,
            'fourth'      => $fourthId ? ($teamsMap[$fourthId] ?? null) : null,
            'fifth'       => $fifthId ? ($teamsMap[$fifthId] ?? null) : null,
            'fifth_goals' => $fifthGoals,
        ];
    }

    #[Computed]
    public function searchResults()
    {
        $q = trim($this->search);
        if (strlen($q) < 2) {
            return collect();
        }

        return Team::with(['category', 'groupTeams.group'])
            ->where(function($builder) use ($q) {
                $builder->where('team_name', 'like', "%{$q}%")
                        ->orWhere('school_name', 'like', "%{$q}%");
            })
            ->limit(12)
            ->get()
            ->map(function($team) {
                $honors = [];
                $catId = $team->category_id;

                // Check Finals
                $wonFinal = TournamentMatch::where('category_id', $catId)
                    ->where('round_name', 'Akhir')
                    ->where('winner_team_id', $team->id)
                    ->first();
                if ($wonFinal) {
                    $honors[] = $wonFinal->stage === 'trophy_knockout' ? '🏆 JUARA (Pusingan Trofi)' : '🥈 JUARA (Pusingan Piala)';
                } else {
                    $lostFinal = TournamentMatch::where('category_id', $catId)
                        ->where('round_name', 'Akhir')
                        ->where('status', 'completed')
                        ->where(fn($m) => $m->where('home_team_id', $team->id)->orWhere('away_team_id', $team->id))
                        ->first();
                    if ($lostFinal) {
                        $honors[] = $lostFinal->stage === 'trophy_knockout' ? '🥈 NAIB JUARA (Pusingan Trofi)' : '🥈 NAIB JUARA (Pusingan Piala)';
                    }
                }

                // Check 3rd place
                $won3rd = TournamentMatch::where('category_id', $catId)
                    ->where('round_name', 'Penentuan Tempat Ke-3')
                    ->where('winner_team_id', $team->id)
                    ->first();
                if ($won3rd) {
                    $honors[] = '🥉 TEMPAT KE-3 (' . ($won3rd->stage === 'trophy_knockout' ? 'Trofi' : 'Piala') . ')';
                }

                // Check Obstacle time
                $gt = $team->groupTeams->first();
                if ($gt && $gt->group && $gt->group->game_type === 'obstacle' && $gt->goals_for > 0) {
                    $tf = $gt->goals_for;
                    $tM = floor($tf / 60000);
                    $tS = floor(($tf % 60000) / 1000);
                    $tMs = $tf % 1000;
                    $honors[] = '⏱️ Masa Rasmi: ' . sprintf('%02d:%02d.%03d', $tM, $tS, $tMs);
                }

                $team->honors = $honors;
                $team->total_goals = $this->getTeamTotalGoals($team->id);
                return $team;
            });
    }
};
?>

<div class="space-y-10 animate-fade-in pb-16">

    {{-- ================================================================ --}}
    {{-- 1. CELEBRATORY HERO BANNER                                       --}}
    {{-- ================================================================ --}}
    <div class="relative overflow-hidden bg-gradient-to-br from-primary via-indigo-950 to-slate-900 rounded-3xl p-8 md:p-12 text-white shadow-2xl border border-white/10">
        {{-- Background Glow --}}
        <div class="absolute -top-24 -right-24 w-96 h-96 bg-amber-400/15 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -bottom-24 -left-24 w-96 h-96 bg-primary/20 rounded-full blur-3xl pointer-events-none"></div>

        <div class="relative z-10 text-center max-w-3xl mx-auto space-y-4">
            <div class="inline-flex items-center gap-2 bg-amber-400/20 text-yellow-300 border border-amber-400/40 px-4 py-1.5 rounded-full text-xs font-black uppercase tracking-widest shadow-inner">
                <span class="animate-bounce">🏆</span>
                <span>Keputusan Rasmi Kejohanan Selesai</span>
            </div>

            <h1 class="text-3xl md:text-5xl font-black tracking-tight text-white drop-shadow-md">
                Tahniah &amp; Syabas!
            </h1>

            <p class="text-white/80 text-sm md:text-base leading-relaxed">
                Kejohanan <strong class="text-yellow-300">Microbit Innovation Robotic Competition (MIRC)</strong> telah selesai dengan jayanya. Setinggi-tinggi penghargaan diucapkan kepada semua peserta, guru pembimbing, pihak sekolah dan ibu bapa yang telah menjayakan kejohanan ini.
            </p>

            <div class="pt-2 flex items-center justify-center gap-2 text-xs font-semibold text-white/50">
                <span>⚡ Dianjurkan oleh Elvira Systems Sdn Bhd</span>
                <span>•</span>
                <span>📅 Arkib Rasmi Keputusan</span>
            </div>
        </div>
    </div>

    {{-- ================================================================ --}}
    {{-- 2. GAME FILTER TABS & QUICK SEARCH                               --}}
    {{-- ================================================================ --}}
    <div class="flex flex-col md:flex-row gap-4 items-stretch md:items-center justify-between">
        {{-- Game Type Pills --}}
        <div class="flex gap-2 overflow-x-auto pb-2 md:pb-0 scrollbar-none">
            <button wire:click="$set('selectedGame', '')"
                    class="px-4 py-2.5 rounded-xl text-xs font-black transition-all whitespace-nowrap {{ $selectedGame === '' ? 'bg-primary text-white shadow-md shadow-primary/30' : 'bg-white text-base-content/70 hover:bg-base-100 border border-base-200' }}">
                Semua Permainan
            </button>
            <button wire:click="$set('selectedGame', 'isobot')"
                    class="px-4 py-2.5 rounded-xl text-xs font-black transition-all whitespace-nowrap {{ $selectedGame === 'isobot' ? 'bg-blue-600 text-white shadow-md shadow-blue-600/30' : 'bg-white text-base-content/70 hover:bg-base-100 border border-base-200' }}">
                🤖 Isobot Soccer
            </button>
            <button wire:click="$set('selectedGame', 'sky_soccer')"
                    class="px-4 py-2.5 rounded-xl text-xs font-black transition-all whitespace-nowrap {{ $selectedGame === 'sky_soccer' ? 'bg-purple-600 text-white shadow-md shadow-purple-600/30' : 'bg-white text-base-content/70 hover:bg-base-100 border border-base-200' }}">
                ⚽ Drone Sky Soccer
            </button>
            <button wire:click="$set('selectedGame', 'obstacle')"
                    class="px-4 py-2.5 rounded-xl text-xs font-black transition-all whitespace-nowrap {{ $selectedGame === 'obstacle' ? 'bg-emerald-600 text-white shadow-md shadow-emerald-600/30' : 'bg-white text-base-content/70 hover:bg-base-100 border border-base-200' }}">
                🛸 Drone Obstacle
            </button>
        </div>

        {{-- Quick Search Input --}}
        <div class="relative flex-1 max-w-md">
            <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-base-content/40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <input wire:model.live.debounce.250ms="search"
                   type="text"
                   placeholder="Cari nama pasukan atau sekolah..."
                   class="w-full pl-10 pr-4 py-2.5 bg-white border border-base-300 rounded-xl text-xs font-semibold focus:outline-none focus:border-primary focus:ring-4 focus:ring-primary/10 transition-all shadow-sm" />
            @if($search)
                <button wire:click="$set('search', '')" class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-base-content/40 hover:text-base-content font-bold">✕</button>
            @endif
        </div>
    </div>

    {{-- Search Results Overlay --}}
    @if(strlen(trim($search)) >= 2)
        <div class="bg-white rounded-3xl p-6 border border-primary/20 shadow-xl space-y-4 animate-slide-up">
            <div class="flex items-center justify-between border-b border-base-200 pb-3">
                <div class="flex items-center gap-2">
                    <span class="text-base">🔍</span>
                    <h3 class="font-black text-sm text-base-content">Hasil Carian Pasukan: "{{ $search }}"</h3>
                </div>
                <span class="text-xs font-bold text-primary">{{ $this->searchResults->count() }} rekod dijumpai</span>
            </div>

            @if($this->searchResults->isEmpty())
                <p class="text-xs text-base-content/50 py-4 text-center">Tiada pasukan atau sekolah ditemui untuk "{{ $search }}".</p>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                    @foreach($this->searchResults as $res)
                        <div class="border border-base-200 rounded-2xl p-4 bg-base-50/50 hover:border-primary/40 transition-all space-y-2">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <h4 class="font-black text-sm text-base-content leading-tight">{{ $res->team_name }}</h4>
                                    <p class="text-[11px] text-base-content/60 mt-0.5">{{ $res->school_name }}</p>
                                </div>
                                <span class="px-2 py-0.5 rounded-md text-[10px] font-extrabold uppercase bg-primary/10 text-primary shrink-0">
                                    {{ $res->game_type_label }}
                                </span>
                            </div>

                            <div class="flex items-center gap-2 text-[10px] text-base-content/50 font-semibold">
                                <span>Kategori: {{ $res->category->name }}</span>
                                @if($res->groupTeams->isNotEmpty())
                                    <span>&bull;</span>
                                    <span>Kumpulan {{ $res->groupTeams->first()->group->group_letter }}</span>
                                @endif
                            </div>

                            {{-- Total Goals Badge --}}
                            @if($res->game_type !== 'obstacle')
                                <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-800 text-[11px] font-black">
                                    <span>⚽</span>
                                    <span>Jumlah Jaringan Kejohanan: {{ $res->total_goals }} Gol</span>
                                </div>
                            @endif

                            @if(!empty($res->honors))
                                <div class="pt-2 border-t border-base-200 space-y-1">
                                    @foreach($res->honors as $honor)
                                        <div class="text-xs font-black text-amber-700 bg-amber-50 px-2 py-1 rounded-lg border border-amber-200/60 inline-block">
                                            {{ $honor }}
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @endif

    {{-- ================================================================ --}}
    {{-- 3. CATEGORY & GAME WINNERS / STANDINGS SHOWCASE                  --}}
    {{-- ================================================================ --}}
    @php $allResults = $this->resultsByGame; @endphp

    <div class="space-y-8">
        @forelse($allResults as $sectionKey => $data)
            @php
                $cat = $data['category'];
                $gt = $data['game_type'];
                $isObstacle = $gt === 'obstacle';
                $activeTab = $this->activeCategoryTabs[$sectionKey] ?? (!empty($data['has_knockouts']) ? 'trophy' : 'groups');
            @endphp

            <div class="bg-white rounded-3xl border border-base-200 shadow-md overflow-hidden">
                {{-- Category Header --}}
                <div class="bg-gradient-to-r {{ $isObstacle ? 'from-emerald-900 via-emerald-800 to-teal-900' : ($gt === 'sky_soccer' ? 'from-purple-950 via-indigo-900 to-slate-900' : 'from-blue-950 via-indigo-950 to-slate-900') }} px-6 py-5 text-white flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-2xl bg-white/10 flex items-center justify-center font-black text-xl shadow-inner border border-white/15">
                            {{ $data['icon'] }}
                        </div>
                        <div>
                            <span class="text-[10px] font-black tracking-widest uppercase text-white/60">
                                {{ $data['game_name'] }}
                            </span>
                            <h2 class="text-xl font-black text-white leading-tight">
                                Kategori {{ $cat->name }}
                            </h2>
                        </div>
                    </div>

                    {{-- Navigation Tabs if Soccer --}}
                    @if(!$isObstacle)
                        <div class="flex bg-black/40 p-1 rounded-xl border border-white/10 flex-wrap gap-1">
                            @if(!empty($data['has_knockouts']))
                                <button wire:click="setCategoryTab('{{ $sectionKey }}', 'trophy')"
                                        class="px-3 py-1.5 rounded-lg text-xs font-bold transition-colors {{ $activeTab === 'trophy' ? 'bg-amber-400 text-slate-950 shadow-sm' : 'text-white/70 hover:text-white' }}">
                                    🏆 Keputusan Akhir &amp; Pemenang
                                </button>
                                @if(!empty($data['has_cup']))
                                    <button wire:click="setCategoryTab('{{ $sectionKey }}', 'cup')"
                                            class="px-3 py-1.5 rounded-lg text-xs font-bold transition-colors {{ $activeTab === 'cup' ? 'bg-slate-300 text-slate-950 shadow-sm' : 'text-white/70 hover:text-white' }}">
                                        🥈 Pusingan Piala
                                    </button>
                                @endif
                            @endif
                            <button wire:click="setCategoryTab('{{ $sectionKey }}', 'groups')"
                                    class="px-3 py-1.5 rounded-lg text-xs font-bold transition-colors {{ $activeTab === 'groups' ? 'bg-white text-slate-950 shadow-sm' : 'text-white/70 hover:text-white' }}">
                                📊 Kedudukan Kumpulan ({{ $data['groups']->count() }})
                            </button>
                        </div>
                    @endif
                </div>

                {{-- Content Body --}}
                <div class="p-6">
                    @if($isObstacle)
                        {{-- ================= DRONE OBSTACLE LEADERBOARD ================= --}}
                        @if($data['standings']->isEmpty())
                            <p class="text-xs text-base-content/40 italic text-center py-6">Tiada rekod masa rasmi.</p>
                        @else
                            <div class="overflow-x-auto">
                                <table class="w-full text-left">
                                    <thead>
                                        <tr class="border-b border-base-200 text-[11px] font-black text-base-content/40 uppercase tracking-widest">
                                            <th class="py-3 px-4 w-14 text-center">Ked</th>
                                            <th class="py-3 px-4">Nama Pasukan</th>
                                            <th class="py-3 px-4 hidden sm:table-cell">Sekolah</th>
                                            <th class="py-3 px-4 text-right">Masa Rasmi</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-base-100 text-xs">
                                        @foreach($data['standings'] as $idx => $gtItem)
                                            <tr class="{{ $idx === 0 ? 'bg-yellow-50/60 font-bold' : ($idx === 1 ? 'bg-slate-50 font-bold' : ($idx === 2 ? 'bg-amber-50/40 font-bold' : '')) }}">
                                                <td class="py-3.5 px-4 text-center">
                                                    @if($idx === 0) <span class="text-base">🥇</span>
                                                    @elseif($idx === 1) <span class="text-base">🥈</span>
                                                    @elseif($idx === 2) <span class="text-base">🥉</span>
                                                    @else <span class="font-black text-base-content/40">{{ $idx + 1 }}</span>
                                                    @endif
                                                </td>
                                                <td class="py-3.5 px-4">
                                                    <span class="font-black text-sm text-base-content">{{ $gtItem->team->team_name }}</span>
                                                    <span class="block sm:hidden text-[10px] text-base-content/50">{{ $gtItem->team->school_name }}</span>
                                                </td>
                                                <td class="py-3.5 px-4 text-base-content/70 hidden sm:table-cell">{{ $gtItem->team->school_name }}</td>
                                                <td class="py-3.5 px-4 text-right">
                                                    <span class="font-mono font-black text-sm text-emerald-700 bg-emerald-50 border border-emerald-200 px-2 py-0.5 rounded-lg">
                                                        {{ $gtItem->time_formatted }}
                                                    </span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif

                    @else
                        {{-- ================= SOCCER PODIUM OR GROUP STANDINGS ================= --}}

                        @if($activeTab === 'groups' || empty($data['has_knockouts']))
                            {{-- FULL GROUP STANDINGS --}}
                            <div class="space-y-6">
                                <div class="flex items-center justify-between border-b border-base-200 pb-2">
                                    <h3 class="font-black text-sm text-base-content uppercase tracking-wider flex items-center gap-2">
                                        <span>📊 Kedudukan Penuh Peringkat Kumpulan</span>
                                    </h3>
                                    <span class="text-xs text-base-content/50 font-medium">{{ $data['groups']->count() }} Kumpulan</span>
                                </div>

                                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                    @foreach($data['groups'] as $gData)
                                        @php $grp = $gData['group']; @endphp
                                        <div class="border border-base-200 rounded-2xl overflow-hidden shadow-sm bg-base-50/30 flex flex-col">
                                            <div class="bg-gradient-to-r from-base-200/60 to-base-100 px-4 py-2.5 border-b border-base-200 flex items-center justify-between">
                                                <span class="font-black text-xs text-primary uppercase tracking-wider">
                                                    Kumpulan {{ $grp->group_letter }}
                                                </span>
                                                <span class="text-[10px] text-base-content/50 font-bold">
                                                    {{ $gData['standings']->count() }} Pasukan
                                                </span>
                                            </div>

                                            <div class="overflow-x-auto flex-1">
                                                <table class="w-full text-left text-xs">
                                                    <thead>
                                                        <tr class="border-b border-base-200 text-[10px] font-black text-base-content/40 uppercase">
                                                            <th class="py-2 px-3 w-8 text-center">#</th>
                                                            <th class="py-2 px-3">Pasukan</th>
                                                            <th class="py-2 px-1.5 text-center" title="Perlawanan">P</th>
                                                            <th class="py-2 px-1.5 text-center" title="Menang">M</th>
                                                            <th class="py-2 px-1.5 text-center" title="Seri">S</th>
                                                            <th class="py-2 px-1.5 text-center" title="Kalah">K</th>
                                                            <th class="py-2 px-1.5 text-center" title="Jaringan">J</th>
                                                            <th class="py-2 px-1.5 text-center" title="Bolos">B</th>
                                                            <th class="py-2 px-1.5 text-center" title="Perbezaan Gol">PG</th>
                                                            <th class="py-2 px-2 text-center font-black text-primary" title="Mata">MT</th>
                                                            <th class="py-2 px-3 text-right font-black text-emerald-700" title="Jumlah Gol Keseluruhan dari permulaan hingga akhir">⚽ Jumlah Gol</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="divide-y divide-base-200">
                                                        @foreach($gData['standings'] as $pos => $gtRow)
                                                            @php $totGoals = $this->getTeamTotalGoals($gtRow->team_id); @endphp
                                                            <tr class="{{ $pos === 0 ? 'bg-emerald-50/50 font-bold' : ($pos === 1 ? 'bg-blue-50/30' : '') }}">
                                                                <td class="py-2.5 px-3 text-center font-black text-base-content/60">
                                                                    {{ $pos + 1 }}
                                                                </td>
                                                                <td class="py-2.5 px-3">
                                                                    <span class="font-extrabold text-base-content block leading-tight">{{ $gtRow->team->team_name ?? '—' }}</span>
                                                                    <span class="text-[10px] text-base-content/50 block truncate max-w-[150px]">{{ $gtRow->team->school_name ?? '' }}</span>
                                                                </td>
                                                                <td class="py-2.5 px-1.5 text-center text-base-content/60">{{ $gtRow->played }}</td>
                                                                <td class="py-2.5 px-1.5 text-center text-emerald-700 font-bold">{{ $gtRow->won }}</td>
                                                                <td class="py-2.5 px-1.5 text-center text-base-content/60">{{ $gtRow->drawn }}</td>
                                                                <td class="py-2.5 px-1.5 text-center text-red-600">{{ $gtRow->lost }}</td>
                                                                <td class="py-2.5 px-1.5 text-center text-base-content/70">{{ $gtRow->goals_for }}</td>
                                                                <td class="py-2.5 px-1.5 text-center text-base-content/70">{{ $gtRow->goals_against }}</td>
                                                                <td class="py-2.5 px-1.5 text-center font-bold {{ $gtRow->goal_difference > 0 ? 'text-emerald-700' : ($gtRow->goal_difference < 0 ? 'text-red-600' : 'text-base-content/50') }}">
                                                                    {{ $gtRow->goal_difference > 0 ? '+'.$gtRow->goal_difference : $gtRow->goal_difference }}
                                                                </td>
                                                                <td class="py-2.5 px-2 text-center font-black text-primary text-sm">
                                                                    {{ $gtRow->points }}
                                                                </td>
                                                                <td class="py-2.5 px-3 text-right font-black text-emerald-700">
                                                                    <span class="inline-flex items-center gap-1 bg-emerald-100/70 text-emerald-900 px-2 py-0.5 rounded-md font-mono text-[11px]">
                                                                        {{ $totGoals }}
                                                                    </span>
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                        @else
                            {{-- KNOCKOUT PODIUM & WINNERS SHOWCASE --}}
                            @php
                                $currentRankings = ($activeTab === 'cup' && !empty($data['cup'])) ? $data['cup'] : $data['trophy'];
                            @endphp

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                                {{-- 🥇 1st Place (JUARA) --}}
                                <div class="order-1 md:order-2 rounded-2xl border-2 border-amber-400 bg-gradient-to-b from-amber-50/80 via-white to-amber-50/20 p-5 shadow-lg relative overflow-hidden flex flex-col justify-between">
                                    <div class="absolute -right-4 -top-4 w-20 h-20 bg-amber-400/10 rounded-full blur-xl pointer-events-none"></div>
                                    <div>
                                        <div class="flex items-center gap-2 mb-2">
                                            <span class="text-2xl">🥇</span>
                                            <span class="bg-amber-400 text-amber-950 text-[10px] font-black px-2.5 py-0.5 rounded-full uppercase tracking-wider shadow-sm">
                                                JUARA
                                            </span>
                                        </div>
                                        <h3 class="text-lg font-black text-base-content leading-snug">
                                            {{ $currentRankings['first']->team_name ?? '—' }}
                                        </h3>
                                        <p class="text-xs text-base-content/60 font-semibold mt-0.5">
                                            {{ $currentRankings['first']->school_name ?? '' }}
                                        </p>

                                        {{-- Total Goals Score Pill --}}
                                        @if($currentRankings['first'])
                                            @php $goals1 = $this->getTeamTotalGoals($currentRankings['first']->id); @endphp
                                            <div class="mt-2.5 inline-flex items-center gap-1.5 px-3 py-1 rounded-xl bg-amber-400/25 border border-amber-400/50 text-amber-950 text-xs font-black">
                                                <span>⚽</span>
                                                <span>Jumlah Gol Kejohanan: <strong>{{ $goals1 }} Gol</strong></span>
                                            </div>
                                        @endif
                                    </div>

                                    @if($currentRankings['first'])
                                        <div class="mt-4 pt-3 border-t border-amber-200/60 text-[11px] text-base-content/60 space-y-0.5">
                                            <p class="font-bold text-amber-800 text-[10px] uppercase tracking-wider">Senarai Pemain:</p>
                                            @if($currentRankings['first']->player_1)<p>• {{ $currentRankings['first']->player_1 }}</p>@endif
                                            @if($currentRankings['first']->player_2)<p>• {{ $currentRankings['first']->player_2 }}</p>@endif
                                            @if($currentRankings['first']->player_3)<p>• {{ $currentRankings['first']->player_3 }}</p>@endif
                                        </div>
                                    @endif
                                </div>

                                {{-- 🥈 2nd Place (NAIB JUARA) --}}
                                <div class="order-2 md:order-1 rounded-2xl border-2 border-slate-300 bg-gradient-to-b from-slate-50 via-white to-slate-50/30 p-5 shadow-md flex flex-col justify-between">
                                    <div>
                                        <div class="flex items-center gap-2 mb-2">
                                            <span class="text-2xl">🥈</span>
                                            <span class="bg-slate-300 text-slate-800 text-[10px] font-black px-2.5 py-0.5 rounded-full uppercase tracking-wider">
                                                NAIB JUARA
                                            </span>
                                        </div>
                                        <h3 class="text-base font-black text-base-content leading-snug">
                                            {{ $currentRankings['second']->team_name ?? '—' }}
                                        </h3>
                                        <p class="text-xs text-base-content/60 font-semibold mt-0.5">
                                            {{ $currentRankings['second']->school_name ?? '' }}
                                        </p>

                                        {{-- Total Goals Score Pill --}}
                                        @if($currentRankings['second'])
                                            @php $goals2 = $this->getTeamTotalGoals($currentRankings['second']->id); @endphp
                                            <div class="mt-2.5 inline-flex items-center gap-1.5 px-3 py-1 rounded-xl bg-slate-200/80 border border-slate-300 text-slate-800 text-xs font-black">
                                                <span>⚽</span>
                                                <span>Jumlah Gol Kejohanan: <strong>{{ $goals2 }} Gol</strong></span>
                                            </div>
                                        @endif
                                    </div>

                                    @if($currentRankings['second'])
                                        <div class="mt-4 pt-3 border-t border-slate-200 text-[11px] text-base-content/60 space-y-0.5">
                                            <p class="font-bold text-slate-700 text-[10px] uppercase tracking-wider">Senarai Pemain:</p>
                                            @if($currentRankings['second']->player_1)<p>• {{ $currentRankings['second']->player_1 }}</p>@endif
                                            @if($currentRankings['second']->player_2)<p>• {{ $currentRankings['second']->player_2 }}</p>@endif
                                            @if($currentRankings['second']->player_3)<p>• {{ $currentRankings['second']->player_3 }}</p>@endif
                                        </div>
                                    @endif
                                </div>

                                {{-- 🥉 3rd Place (TEMPAT KE-3) --}}
                                <div class="order-3 md:order-3 rounded-2xl border-2 border-amber-600/30 bg-gradient-to-b from-amber-50/40 via-white to-transparent p-5 shadow-md flex flex-col justify-between">
                                    <div>
                                        <div class="flex items-center gap-2 mb-2">
                                            <span class="text-2xl">🥉</span>
                                            <span class="bg-amber-600/20 text-amber-900 text-[10px] font-black px-2.5 py-0.5 rounded-full uppercase tracking-wider">
                                                TEMPAT KE-3
                                            </span>
                                        </div>
                                        <h3 class="text-base font-black text-base-content leading-snug">
                                            {{ $currentRankings['third']->team_name ?? '—' }}
                                        </h3>
                                        <p class="text-xs text-base-content/60 font-semibold mt-0.5">
                                            {{ $currentRankings['third']->school_name ?? '' }}
                                        </p>

                                        {{-- Total Goals Score Pill --}}
                                        @if($currentRankings['third'])
                                            @php $goals3 = $this->getTeamTotalGoals($currentRankings['third']->id); @endphp
                                            <div class="mt-2.5 inline-flex items-center gap-1.5 px-3 py-1 rounded-xl bg-amber-600/15 border border-amber-600/30 text-amber-900 text-xs font-black">
                                                <span>⚽</span>
                                                <span>Jumlah Gol Kejohanan: <strong>{{ $goals3 }} Gol</strong></span>
                                            </div>
                                        @endif
                                    </div>

                                    @if($currentRankings['third'])
                                        <div class="mt-4 pt-3 border-t border-amber-200/50 text-[11px] text-base-content/60 space-y-0.5">
                                            <p class="font-bold text-amber-800 text-[10px] uppercase tracking-wider">Senarai Pemain:</p>
                                            @if($currentRankings['third']->player_1)<p>• {{ $currentRankings['third']->player_1 }}</p>@endif
                                            @if($currentRankings['third']->player_2)<p>• {{ $currentRankings['third']->player_2 }}</p>@endif
                                            @if($currentRankings['third']->player_3)<p>• {{ $currentRankings['third']->player_3 }}</p>@endif
                                        </div>
                                    @endif
                                </div>
                            </div>

                            {{-- 4th and 5th Place Row --}}
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-2">
                                {{-- 4th Place --}}
                                <div class="rounded-xl border border-base-200 p-3.5 bg-base-50/60 flex items-center justify-between gap-3">
                                    <div class="flex items-center gap-3 overflow-hidden">
                                        <span class="text-xl">🏅</span>
                                        <div class="overflow-hidden">
                                            <span class="text-[9px] font-black uppercase tracking-wider text-base-content/50 block">TEMPAT KE-4</span>
                                            <p class="font-extrabold text-xs text-base-content truncate">{{ $currentRankings['fourth']->team_name ?? '—' }}</p>
                                            <p class="text-[10px] text-base-content/50 truncate">{{ $currentRankings['fourth']->school_name ?? '' }}</p>
                                        </div>
                                    </div>
                                    @if($currentRankings['fourth'])
                                        <span class="shrink-0 text-xs font-mono font-black text-slate-700 bg-white border border-base-200 px-2 py-1 rounded-lg">
                                            ⚽ {{ $this->getTeamTotalGoals($currentRankings['fourth']->id) }} Gol
                                        </span>
                                    @endif
                                </div>

                                {{-- 5th Place --}}
                                <div class="rounded-xl border border-emerald-200 p-3.5 bg-emerald-50/50 flex items-center justify-between gap-3">
                                    <div class="flex items-center gap-3 overflow-hidden">
                                        <span class="text-xl">🎖️</span>
                                        <div class="overflow-hidden">
                                            <div class="flex items-center gap-1.5">
                                                <span class="text-[9px] font-black uppercase tracking-wider text-emerald-800">TEMPAT KE-5</span>
                                                @if(!empty($currentRankings['fifth_goals']))
                                                    <span class="text-[8px] bg-emerald-200/80 text-emerald-900 font-bold px-1 rounded">{{ $currentRankings['fifth_goals'] }} Gol QF</span>
                                                @endif
                                            </div>
                                            <p class="font-extrabold text-xs text-emerald-950 truncate">{{ $currentRankings['fifth']->team_name ?? '—' }}</p>
                                            <p class="text-[10px] text-emerald-700 truncate">{{ $currentRankings['fifth']->school_name ?? '' }}</p>
                                        </div>
                                    </div>
                                    @if($currentRankings['fifth'])
                                        <span class="shrink-0 text-xs font-mono font-black text-emerald-800 bg-white border border-emerald-200 px-2 py-1 rounded-lg">
                                            ⚽ {{ $this->getTeamTotalGoals($currentRankings['fifth']->id) }} Gol
                                        </span>
                                    @endif
                                </div>
                            </div>
                        @endif

                    @endif
                </div>
            </div>

        @empty
            <div class="bg-white rounded-3xl p-12 text-center border border-base-200 text-base-content/40">
                Tiada kategori dijumpai bagi pilihan permainan ini.
            </div>
        @endforelse
    </div>

    {{-- ================================================================ --}}
    {{-- 4. FOOTER NOTE & ADMIN PORTAL ACCESS                             --}}
    {{-- ================================================================ --}}
    <div class="bg-white rounded-2xl border border-base-200 p-6 flex flex-col sm:flex-row items-center justify-between gap-4 shadow-sm">
        <div>
            <h4 class="font-black text-sm text-base-content">Portal Pentadbiran Kejohanan</h4>
            <p class="text-xs text-base-content/60 mt-0.5">Urusetia &amp; Pentadbir sistem boleh log masuk untuk menyemak arkib penuh data pertandingan.</p>
        </div>
        <a href="{{ route('admin.login') }}" class="btn btn-primary btn-sm rounded-xl px-5 text-xs font-bold shrink-0">
            Log Masuk Admin 🔒
        </a>
    </div>

</div>
