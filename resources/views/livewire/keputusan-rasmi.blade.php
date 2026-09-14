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

    public array $activeCategoryTabs = []; // [categoryId => 'trophy' | 'cup']

    public function setCategoryTab(int $categoryId, string $tab)
    {
        $this->activeCategoryTabs[$categoryId] = $tab;
    }

    #[Computed]
    public function categories()
    {
        $query = Category::with(['groups', 'teams'])->orderBy('sort_order')->orderBy('id');

        $allCategories = $query->get();

        if ($this->selectedGame) {
            $allCategories = $allCategories->filter(function($cat) {
                $gameTypes = $cat->groups->pluck('game_type')->merge($cat->teams->pluck('game_type'))->unique();
                return $gameTypes->contains($this->selectedGame);
            });
        }

        return $allCategories;
    }

    #[Computed]
    public function resultsByGame()
    {
        $data = [];

        foreach ($this->categories as $cat) {
            $groups = $cat->groups;
            $primaryGameType = $groups->first()?->game_type ?? $cat->teams->first()?->game_type ?? 'isobot';

            if ($primaryGameType === 'obstacle') {
                // Drone Obstacle: Top 10 Fastest Times
                $standings = GroupTeam::whereHas('group', function($q) use ($cat) {
                    $q->where('category_id', $cat->id)->where('game_type', 'obstacle');
                })
                ->with('team')
                ->where('goals_for', '>', 0)
                ->orderBy('goals_for', 'asc')
                ->limit(10)
                ->get()
                ->map(function($gt) {
                    $tf = $gt->goals_for;
                    $tM = floor($tf / 60000);
                    $tS = floor(($tf % 60000) / 1000);
                    $tMs = $tf % 1000;
                    $gt->time_formatted = sprintf('%02d:%02d.%03d', $tM, $tS, $tMs);
                    return $gt;
                });

                $data[$cat->id] = [
                    'category'  => $cat,
                    'type'      => 'obstacle',
                    'game_type' => 'obstacle',
                    'standings' => $standings,
                ];
            } else {
                // Soccer Categories (Isobot / Sky Soccer)
                $matches = TournamentMatch::with(['homeTeam', 'awayTeam'])
                    ->where('category_id', $cat->id)
                    ->whereIn('stage', ['trophy_knockout', 'cup_knockout'])
                    ->get();

                $trophyRankings = $this->calculateStageRankings($matches, 'trophy_knockout', $cat->id);
                $hasCup = $matches->where('stage', 'cup_knockout')->isNotEmpty();
                $cupRankings = $hasCup ? $this->calculateStageRankings($matches, 'cup_knockout', $cat->id) : null;

                // Fallback to group stage if knockout never occurred
                $hasKnockoutData = !empty($trophyRankings['first']);
                $groupStandingsFallback = null;
                if (!$hasKnockoutData) {
                    $groupStandingsFallback = $groups->map(function($g) {
                        return [
                            'group'     => $g,
                            'standings' => $g->getStandings()->take(3)
                        ];
                    });
                }

                $data[$cat->id] = [
                    'category'        => $cat,
                    'type'            => 'soccer',
                    'game_type'       => $primaryGameType,
                    'has_knockouts'   => $hasKnockoutData,
                    'trophy'          => $trophyRankings,
                    'has_cup'         => $hasCup,
                    'cup'             => $cupRankings,
                    'group_fallbacks' => $groupStandingsFallback,
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
    <div class="flex flex-col md:flex-row items-center justify-between gap-4">
        {{-- Filter Buttons --}}
        <div class="flex items-center gap-2 overflow-x-auto w-full md:w-auto pb-1" style="scrollbar-width:none;">
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

        {{-- Team/School Search Input --}}
        <div class="w-full md:w-80 relative">
            <svg class="w-4 h-4 text-base-content/40 absolute left-3.5 top-1/2 -translate-y-1/2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <input type="text"
                   wire:model.live.debounce.300ms="search"
                   placeholder="Cari keputusan sekolah / pasukan..."
                   class="w-full pl-10 pr-4 py-2.5 bg-white border border-base-200 rounded-xl text-xs font-medium focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all shadow-sm">
        </div>
    </div>

    {{-- ================================================================ --}}
    {{-- 2B. SEARCH RESULTS (IF ACTIVE SEARCH)                            --}}
    {{-- ================================================================ --}}
    @if(strlen(trim($search)) >= 2)
        @php $searchTeams = $this->searchResults; @endphp
        <div class="bg-white rounded-3xl p-6 border border-base-200 shadow-md space-y-4">
            <div class="flex items-center justify-between border-b border-base-200 pb-3">
                <h3 class="font-black text-sm text-base-content flex items-center gap-2">
                    <span>🔍 Hasil Carian Pasukan:</span>
                    <span class="text-primary font-bold">"{{ $search }}"</span>
                </h3>
                <span class="text-xs font-bold text-base-content/50">{{ $searchTeams->count() }} pasukan dijumpai</span>
            </div>

            @if($searchTeams->isEmpty())
                <div class="text-center py-8 text-base-content/40 text-xs">
                    Tiada rekod pasukan atau sekolah dijumpai untuk carian ini.
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($searchTeams as $team)
                        <div class="rounded-2xl border border-base-200 p-4 bg-base-50/50 hover:bg-white transition-all shadow-sm space-y-2">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <h4 class="font-extrabold text-sm text-base-content">{{ $team->team_name }}</h4>
                                    <p class="text-xs text-base-content/60 font-medium">{{ $team->school_name }}</p>
                                </div>
                                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-base-200 text-base-content/70 whitespace-nowrap">
                                    {{ $team->category->name }}
                                </span>
                            </div>

                            @if(!empty($team->honors))
                                <div class="space-y-1 pt-1">
                                    @foreach($team->honors as $honor)
                                        <div class="inline-block bg-amber-100 text-amber-900 border border-amber-300 text-[11px] font-black px-2.5 py-1 rounded-lg">
                                            {{ $honor }}
                                        </div>
                                    @endforeach
                                </div>
                            @endif

                            <div class="pt-2 border-t border-base-200/60 text-[11px] text-base-content/60 space-y-0.5">
                                @if($team->player_1)<p><span class="font-bold text-primary">1.</span> {{ $team->player_1 }}</p>@endif
                                @if($team->player_2)<p><span class="font-bold text-primary">2.</span> {{ $team->player_2 }}</p>@endif
                                @if($team->player_3)<p><span class="font-bold text-primary">3.</span> {{ $team->player_3 }}</p>@endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @endif

    {{-- ================================================================ --}}
    {{-- 3. CATEGORY BY CATEGORY WINNERS SHOWCASE                         --}}
    {{-- ================================================================ --}}
    @php $allResults = $this->resultsByGame; @endphp

    <div class="space-y-8">
        @forelse($allResults as $catId => $data)
            @php
                $cat = $data['category'];
                $isObstacle = $data['type'] === 'obstacle';
                $activeTab = $this->activeCategoryTabs[$catId] ?? 'trophy';
            @endphp

            <div class="bg-white rounded-3xl border border-base-200 shadow-md overflow-hidden">
                {{-- Category Header --}}
                <div class="bg-gradient-to-r {{ $isObstacle ? 'from-emerald-900 via-emerald-800 to-teal-900' : ($data['game_type'] === 'sky_soccer' ? 'from-purple-950 via-indigo-900 to-slate-900' : 'from-blue-950 via-indigo-950 to-slate-900') }} px-6 py-5 text-white flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-2xl bg-white/10 flex items-center justify-center font-black text-xl shadow-inner border border-white/15">
                            {{ $isObstacle ? '🛸' : ($data['game_type'] === 'sky_soccer' ? '⚽' : '🤖') }}
                        </div>
                        <div>
                            <span class="text-[10px] font-black tracking-widest uppercase text-white/60">
                                {{ $isObstacle ? 'DRONE OBSTACLE' : ($data['game_type'] === 'sky_soccer' ? 'DRONE SKY SOCCER' : 'ISOBOT SOCCER') }}
                            </span>
                            <h2 class="text-xl font-black text-white leading-tight">
                                Kategori {{ $cat->name }}
                            </h2>
                        </div>
                    </div>

                    {{-- Toggle between Trophy and Cup if Cup exists --}}
                    @if(!$isObstacle && !empty($data['has_cup']))
                        <div class="flex bg-black/40 p-1 rounded-xl border border-white/10 w-fit">
                            <button wire:click="setCategoryTab({{ $catId }}, 'trophy')"
                                    class="px-3 py-1.5 rounded-lg text-xs font-bold transition-colors {{ $activeTab === 'trophy' ? 'bg-amber-400 text-slate-950' : 'text-white/70 hover:text-white' }}">
                                🏆 Pusingan Trofi
                            </button>
                            <button wire:click="setCategoryTab({{ $catId }}, 'cup')"
                                    class="px-3 py-1.5 rounded-lg text-xs font-bold transition-colors {{ $activeTab === 'cup' ? 'bg-slate-300 text-slate-950' : 'text-white/70 hover:text-white' }}">
                                🥈 Pusingan Piala
                            </button>
                        </div>
                    @endif
                </div>

                {{-- Content Body --}}
                <div class="p-6">
                    @if($isObstacle)
                        {{-- ================= DRONE OBSTACLE LEADERBOARD ================= --}}
                        @if($data['standings']->isEmpty())
                            <p class="text-xs text-base-content/40 italic text-center py-4">Tiada rekod masa rasmi.</p>
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
                                        @foreach($data['standings'] as $idx => $gt)
                                            <tr class="{{ $idx === 0 ? 'bg-yellow-50/60 font-bold' : ($idx === 1 ? 'bg-slate-50 font-bold' : ($idx === 2 ? 'bg-amber-50/40 font-bold' : '')) }}">
                                                <td class="py-3.5 px-4 text-center">
                                                    @if($idx === 0) <span class="text-base">🥇</span>
                                                    @elseif($idx === 1) <span class="text-base">🥈</span>
                                                    @elseif($idx === 2) <span class="text-base">🥉</span>
                                                    @else <span class="font-black text-base-content/40">{{ $idx + 1 }}</span>
                                                    @endif
                                                </td>
                                                <td class="py-3.5 px-4">
                                                    <span class="font-black text-sm text-base-content">{{ $gt->team->team_name }}</span>
                                                    <span class="block sm:hidden text-[10px] text-base-content/50">{{ $gt->team->school_name }}</span>
                                                </td>
                                                <td class="py-3.5 px-4 text-base-content/70 hidden sm:table-cell">{{ $gt->team->school_name }}</td>
                                                <td class="py-3.5 px-4 text-right">
                                                    <span class="font-mono font-black text-sm text-emerald-700 bg-emerald-50 border border-emerald-200 px-2 py-0.5 rounded-lg">
                                                        {{ $gt->time_formatted }}
                                                    </span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif

                    @else
                        {{-- ================= SOCCER PODIUM & RANKINGS ================= --}}
                        @php
                            $currentRankings = ($activeTab === 'cup' && !empty($data['cup'])) ? $data['cup'] : $data['trophy'];
                        @endphp

                        @if(!empty($data['has_knockouts']))
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                                {{-- 🥇 1st Place (JUARA) --}}
                                <div class="order-1 md:order-2 rounded-2xl border-2 border-amber-400 bg-gradient-to-b from-amber-50/80 via-white to-amber-50/20 p-5 shadow-lg relative overflow-hidden">
                                    <div class="absolute -right-4 -top-4 w-20 h-20 bg-amber-400/10 rounded-full blur-xl pointer-events-none"></div>
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
                                    @if($currentRankings['first'])
                                        <div class="mt-3 pt-3 border-t border-amber-200/60 text-[11px] text-base-content/60 space-y-0.5">
                                            <p class="font-bold text-amber-800 text-[10px] uppercase tracking-wider">Senarai Pemain:</p>
                                            @if($currentRankings['first']->player_1)<p>• {{ $currentRankings['first']->player_1 }}</p>@endif
                                            @if($currentRankings['first']->player_2)<p>• {{ $currentRankings['first']->player_2 }}</p>@endif
                                            @if($currentRankings['first']->player_3)<p>• {{ $currentRankings['first']->player_3 }}</p>@endif
                                        </div>
                                    @endif
                                </div>

                                {{-- 🥈 2nd Place (NAIB JUARA) --}}
                                <div class="order-2 md:order-1 rounded-2xl border-2 border-slate-300 bg-gradient-to-b from-slate-50 via-white to-slate-50/30 p-5 shadow-md">
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
                                    @if($currentRankings['second'])
                                        <div class="mt-3 pt-3 border-t border-slate-200 text-[11px] text-base-content/60 space-y-0.5">
                                            <p class="font-bold text-slate-700 text-[10px] uppercase tracking-wider">Senarai Pemain:</p>
                                            @if($currentRankings['second']->player_1)<p>• {{ $currentRankings['second']->player_1 }}</p>@endif
                                            @if($currentRankings['second']->player_2)<p>• {{ $currentRankings['second']->player_2 }}</p>@endif
                                            @if($currentRankings['second']->player_3)<p>• {{ $currentRankings['second']->player_3 }}</p>@endif
                                        </div>
                                    @endif
                                </div>

                                {{-- 🥉 3rd Place (TEMPAT KE-3) --}}
                                <div class="order-3 md:order-3 rounded-2xl border-2 border-amber-600/30 bg-gradient-to-b from-amber-50/40 via-white to-transparent p-5 shadow-md">
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
                                    @if($currentRankings['third'])
                                        <div class="mt-3 pt-3 border-t border-amber-200/50 text-[11px] text-base-content/60 space-y-0.5">
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
                                <div class="rounded-xl border border-base-200 p-3 bg-base-50/60 flex items-center gap-3">
                                    <span class="text-lg">🏅</span>
                                    <div class="overflow-hidden">
                                        <span class="text-[9px] font-black uppercase tracking-wider text-base-content/50 block">TEMPAT KE-4</span>
                                        <p class="font-extrabold text-xs text-base-content truncate">{{ $currentRankings['fourth']->team_name ?? '—' }}</p>
                                        <p class="text-[10px] text-base-content/50 truncate">{{ $currentRankings['fourth']->school_name ?? '' }}</p>
                                    </div>
                                </div>

                                {{-- 5th Place --}}
                                <div class="rounded-xl border border-emerald-200 p-3 bg-emerald-50/50 flex items-center gap-3">
                                    <span class="text-lg">🎖️</span>
                                    <div class="overflow-hidden">
                                        <div class="flex items-center gap-1.5">
                                            <span class="text-[9px] font-black uppercase tracking-wider text-emerald-800">TEMPAT KE-5</span>
                                            @if(!empty($currentRankings['fifth_goals']))
                                                <span class="text-[8px] bg-emerald-200/80 text-emerald-900 font-bold px-1 rounded">{{ $currentRankings['fifth_goals'] }} Gol</span>
                                            @endif
                                        </div>
                                        <p class="font-extrabold text-xs text-emerald-950 truncate">{{ $currentRankings['fifth']->team_name ?? '—' }}</p>
                                        <p class="text-[10px] text-emerald-700 truncate">{{ $currentRankings['fifth']->school_name ?? '' }}</p>
                                    </div>
                                </div>
                            </div>

                        @else
                            {{-- Fallback: Group Standings when no knockout --}}
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                @foreach($data['group_fallbacks'] as $gData)
                                    <div class="border border-base-200 rounded-2xl p-4 bg-base-50/40">
                                        <h4 class="font-black text-xs text-base-content/70 uppercase mb-2">Kumpulan {{ $gData['group']->group_letter }} (Top 3)</h4>
                                        <div class="space-y-2">
                                            @foreach($gData['standings'] as $pos => $gt)
                                                <div class="flex items-center justify-between text-xs">
                                                    <span class="font-bold text-base-content">{{ $pos + 1 }}. {{ $gt->team->team_name }}</span>
                                                    <span class="font-black text-primary">{{ $gt->points }} Mata</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
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
