<?php

use Livewire\Component;
use Livewire\Attributes\Computed;
use App\Models\Category;
use App\Models\Group;
use App\Models\TournamentMatch;
use App\Models\Team;

new class extends Component {
    public ?string $gameType = null;
    public string $activeGameTab = 'all'; // 'all', 'isobot', 'sky_soccer', 'obstacle'
    public ?int $viewUnfinishedCategoryId = null;
    public ?string $viewUnfinishedGameType = null;

    public function mount(?string $gameType = null)
    {
        $this->gameType = $gameType;
        if ($gameType) {
            $this->activeGameTab = $gameType;
        }
    }

    public function setTab(string $tab)
    {
        $this->activeGameTab = $tab;
        $this->viewUnfinishedCategoryId = null;
    }

    public function showUnfinishedMatches(int $categoryId, string $gameType)
    {
        $this->viewUnfinishedCategoryId = $categoryId;
        $this->viewUnfinishedGameType = $gameType;
    }

    public function closeUnfinishedModal()
    {
        $this->viewUnfinishedCategoryId = null;
        $this->viewUnfinishedGameType = null;
    }

    #[Computed]
    public function categoryStatuses()
    {
        $query = Category::with([
            'teams',
            'groups.groupTeams.team',
            'groups.matches.homeTeam',
            'groups.matches.awayTeam',
            'matches.homeTeam',
            'matches.awayTeam'
        ])->orderBy('sort_order');

        $categories = $query->get();
        $results = [];

        $games = match($this->activeGameTab) {
            'all' => ['isobot', 'sky_soccer', 'obstacle'],
            default => [$this->activeGameTab]
        };

        foreach ($categories as $category) {
            foreach ($games as $game) {
                // Total registered teams for this category & game
                $teams = $category->teams->where('game_type', $game);
                $totalTeams = $teams->count();
                if ($totalTeams === 0 && $this->activeGameTab === 'all') {
                    continue;
                }

                $checkedInTeams = $teams->where('status', 'checked_in')->count();
                $attendancePct = $totalTeams > 0 ? round(($checkedInTeams / $totalTeams) * 100) : 0;

                // Groups for this category & game
                $groups = $category->groups->where('game_type', $game);
                $totalGroups = $groups->count();
                
                // Group matches
                $groupMatches = $category->matches->where('stage', 'group')->filter(function($m) use ($game) {
                    return $m->group && $m->group->game_type === $game;
                });
                
                $totalGroupMatches = $groupMatches->count();
                $completedGroupMatches = $groupMatches->where('status', 'completed')->count();
                $inProgressGroupMatches = $groupMatches->where('status', 'in_progress')->count();
                $pendingGroupMatches = $totalGroupMatches - $completedGroupMatches;

                $groupProgressPct = $totalGroupMatches > 0 ? round(($completedGroupMatches / $totalGroupMatches) * 100) : 0;

                // Knockout stage (only applicable for soccer games if format != round_robin_only)
                $isObstacle = ($game === 'obstacle');
                $isRoundRobinOnly = $category->isRoundRobinOnly() || $isObstacle;

                $knockoutMatches = $category->matches->whereIn('stage', ['trophy_knockout', 'cup_knockout']);
                $hasKnockoutGenerated = $knockoutMatches->isNotEmpty();
                $totalKnockoutMatches = $knockoutMatches->count();
                $completedKnockoutMatches = $knockoutMatches->where('status', 'completed')->count();
                $inProgressKnockoutMatches = $knockoutMatches->where('status', 'in_progress')->count();

                // Unfinished group matches list
                $unfinishedList = $groupMatches->where('status', '!=', 'completed')->values();

                // Overall status and readiness evaluation
                $statusType = 'pending';
                $statusText = '';
                $statusBadgeClass = '';

                if ($totalTeams === 0) {
                    $statusType = 'empty';
                    $statusText = 'Tiada Pasukan';
                    $statusBadgeClass = 'badge-ghost';
                } elseif ($totalGroups === 0) {
                    if ($checkedInTeams === 0) {
                        $statusType = 'attendance_pending';
                        $statusText = 'Menunggu Semak Masuk';
                        $statusBadgeClass = 'bg-amber-100 text-amber-800 border-amber-300';
                    } else {
                        $statusType = 'groups_needed';
                        $statusText = 'Sedia Dijana Kumpulan';
                        $statusBadgeClass = 'bg-blue-100 text-blue-800 border-blue-300';
                    }
                } elseif ($totalGroupMatches === 0) {
                    $statusType = 'fixtures_needed';
                    $statusText = 'Perlu Jana Jadual Perlawanan';
                    $statusBadgeClass = 'bg-amber-100 text-amber-800 border-amber-300';
                } elseif ($pendingGroupMatches > 0) {
                    $statusType = 'matches_in_progress';
                    $statusText = $pendingGroupMatches . ' ' . __('Perlawanan Berbaki');
                    $statusBadgeClass = 'bg-orange-100 text-orange-800 border-orange-300';
                } else {
                    // All group matches completed
                    if ($isRoundRobinOnly) {
                        $statusType = 'completed';
                        $statusText = 'Peringkat Liga Selesai';
                        $statusBadgeClass = 'bg-emerald-100 text-emerald-800 border-emerald-300';
                    } elseif (!$hasKnockoutGenerated) {
                        $statusType = 'ready_for_knockout';
                        $statusText = 'Sedia Jana Kalah Mati';
                        $statusBadgeClass = 'bg-emerald-500 text-white font-black shadow-md shadow-emerald-500/30 animate-pulse';
                    } else {
                        $pendingKnockout = $totalKnockoutMatches - $completedKnockoutMatches;
                        if ($pendingKnockout > 0) {
                            $statusType = 'knockout_active';
                            $statusText = 'Kalah Mati Aktif (' . $completedKnockoutMatches . '/' . $totalKnockoutMatches . ')';
                            $statusBadgeClass = 'bg-indigo-100 text-indigo-800 border-indigo-300';
                        } else {
                            $statusType = 'completed';
                            $statusText = 'Kejohanan Selesai';
                            $statusBadgeClass = 'bg-emerald-600 text-white';
                        }
                    }
                }

                $results[] = [
                    'category_id'                => $category->id,
                    'category_name'              => $category->name,
                    'category_slug'              => $category->slug,
                    'game_type'                  => $game,
                    'game_label'                 => match($game) {
                        'isobot' => 'Isobot Soccer',
                        'sky_soccer' => 'Drone Sky Soccer',
                        'obstacle' => 'Drone Obstacle',
                        default => $game
                    },
                    'is_obstacle'                => $isObstacle,
                    'is_round_robin_only'        => $isRoundRobinOnly,
                    'total_teams'                => $totalTeams,
                    'checked_in_teams'           => $checkedInTeams,
                    'attendance_pct'             => $attendancePct,
                    'total_groups'               => $totalGroups,
                    'total_group_matches'        => $totalGroupMatches,
                    'completed_group_matches'    => $completedGroupMatches,
                    'in_progress_group_matches'  => $inProgressGroupMatches,
                    'pending_group_matches'      => $pendingGroupMatches,
                    'group_progress_pct'         => $groupProgressPct,
                    'has_knockout_generated'     => $hasKnockoutGenerated,
                    'total_knockout_matches'     => $totalKnockoutMatches,
                    'completed_knockout_matches' => $completedKnockoutMatches,
                    'in_progress_knockout'       => $inProgressKnockoutMatches,
                    'status_type'                => $statusType,
                    'status_text'                => $statusText,
                    'status_badge_class'         => $statusBadgeClass,
                    'unfinished_matches'         => $unfinishedList,
                ];
            }
        }

        return $results;
    }

    #[Computed]
    public function fieldLiveMatrix()
    {
        $fields = TournamentMatch::whereNotNull('field_number')
            ->select('field_number')
            ->distinct()
            ->orderByRaw('LENGTH(field_number)')
            ->orderBy('field_number')
            ->pluck('field_number');

        $fieldData = [];

        foreach ($fields as $field) {
            $activeMatch = TournamentMatch::with(['homeTeam', 'awayTeam', 'group.category', 'category'])
                ->where('field_number', $field)
                ->where('status', 'in_progress')
                ->first();

            $upcomingMatch = TournamentMatch::with(['homeTeam', 'awayTeam', 'group.category', 'category'])
                ->where('field_number', $field)
                ->where('status', 'scheduled')
                ->orderBy('scheduled_time')
                ->orderBy('id')
                ->first();

            $completedCount = TournamentMatch::where('field_number', $field)
                ->where('status', 'completed')
                ->count();

            $totalFieldMatches = TournamentMatch::where('field_number', $field)->count();

            $fieldData[] = [
                'field_number'    => $field,
                'active_match'    => $activeMatch,
                'upcoming_match'  => $upcomingMatch,
                'completed_count' => $completedCount,
                'total_matches'   => $totalFieldMatches,
            ];
        }

        return $fieldData;
    }

    #[Computed]
    public function globalStats()
    {
        $totalMatches = TournamentMatch::count();
        $inProgress = TournamentMatch::where('status', 'in_progress')->count();
        $completed = TournamentMatch::where('status', 'completed')->count();
        $scheduled = TournamentMatch::where('status', 'scheduled')->count();
        
        $statuses = $this->categoryStatuses();
        $readyForKnockoutCount = collect($statuses)->where('status_type', 'ready_for_knockout')->count();
        $groupsPendingMatchesCount = collect($statuses)->where('status_type', 'matches_in_progress')->count();

        return [
            'total_matches'            => $totalMatches,
            'in_progress'              => $inProgress,
            'completed'                => $completed,
            'scheduled'                => $scheduled,
            'progress_pct'             => $totalMatches > 0 ? round(($completed / $totalMatches) * 100) : 0,
            'ready_for_knockout_count' => $readyForKnockoutCount,
            'groups_pending_count'     => $groupsPendingMatchesCount,
        ];
    }

    #[Computed]
    public function modalUnfinishedData()
    {
        if (!$this->viewUnfinishedCategoryId || !$this->viewUnfinishedGameType) {
            return null;
        }

        $category = Category::find($this->viewUnfinishedCategoryId);
        if (!$category) return null;

        $matches = TournamentMatch::with(['homeTeam', 'awayTeam', 'group'])
            ->where('category_id', $this->viewUnfinishedCategoryId)
            ->where('stage', 'group')
            ->where('status', '!=', 'completed')
            ->whereHas('group', fn($q) => $q->where('game_type', $this->viewUnfinishedGameType))
            ->orderBy('group_id')
            ->orderBy('id')
            ->get();

        return [
            'category' => $category,
            'game_type' => $this->viewUnfinishedGameType,
            'matches'  => $matches,
        ];
    }
};
?>

<div wire:poll.8s class="space-y-6">

    {{-- ========================================================= --}}
    {{-- TOP OPERATIONAL PULSE BAR                                --}}
    {{-- ========================================================= --}}
    @php $gStats = $this->globalStats(); @endphp
    <div class="bg-white rounded-2xl border border-base-200 shadow-sm p-4 md:p-5">
        <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4">
            
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center text-primary shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h2 class="font-extrabold text-base md:text-lg text-base-content">{{ __('Pusat Kawalan & Status Operasi Kejohanan') }}</h2>
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-black bg-emerald-100 text-emerald-800 uppercase tracking-widest border border-emerald-200">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-ping"></span>
                            {{ __('Live Auto-Sync') }}
                        </span>
                    </div>
                    <p class="text-xs text-base-content/50 mt-0.5">{{ __('Pantau kemajuan liga kumpulan, padang aktif, dan kesediaan janaan carta kalah mati.') }}</p>
                </div>
            </div>

            {{-- Metric Pills --}}
            <div class="flex items-center gap-2 flex-wrap shrink-0">
                @if($gStats['in_progress'] > 0)
                    <div class="flex items-center gap-2 bg-red-50 border border-red-200 text-red-700 px-3 py-1.5 rounded-xl text-xs font-bold shadow-2xs animate-pulse">
                        <span class="w-2 h-2 rounded-full bg-red-500"></span>
                        <span>{{ $gStats['in_progress'] }} {{ __('Perlawanan Live') }}</span>
                    </div>
                @endif

                @if($gStats['ready_for_knockout_count'] > 0)
                    <a href="{{ route('admin.knockout.index') }}" 
                       class="flex items-center gap-1.5 bg-emerald-500 hover:bg-emerald-600 text-white px-3.5 py-1.5 rounded-xl text-xs font-black shadow-md shadow-emerald-500/20 transition-all hover:scale-105">
                        <span>⚡</span>
                        <span>{{ $gStats['ready_for_knockout_count'] }} {{ __('Kategori Sedia Kalah Mati') }}</span>
                        <span>→</span>
                    </a>
                @endif

                <div class="flex items-center gap-2 bg-base-100 border border-base-200 px-3 py-1.5 rounded-xl text-xs font-semibold text-base-content/70">
                    <span>{{ __('Perlawanan:') }}</span>
                    <span class="font-bold text-primary">{{ $gStats['completed'] }}/{{ $gStats['total_matches'] }}</span>
                    <span class="text-[10px] bg-base-200 px-1.5 py-0.5 rounded font-bold text-base-content/60">{{ $gStats['progress_pct'] }}%</span>
                </div>
            </div>
        </div>

        {{-- Game Tabs Filter --}}
        <div class="flex gap-2 overflow-x-auto mt-4 pt-4 border-t border-base-200">
            <button wire:click="setTab('all')" 
                    class="px-4 py-2 rounded-xl text-xs font-bold transition-all shrink-0 {{ $activeGameTab === 'all' ? 'bg-primary text-white shadow-sm' : 'bg-base-100 border border-base-200 text-base-content/60 hover:bg-base-200' }}">
                🌐 {{ __('Semua Permainan') }}
            </button>
            <button wire:click="setTab('isobot')" 
                    class="px-4 py-2 rounded-xl text-xs font-bold transition-all shrink-0 {{ $activeGameTab === 'isobot' ? 'bg-blue-600 text-white shadow-sm' : 'bg-base-100 border border-base-200 text-base-content/60 hover:bg-base-200' }}">
                🤖 {{ __('Isobot Soccer') }}
            </button>
            <button wire:click="setTab('sky_soccer')" 
                    class="px-4 py-2 rounded-xl text-xs font-bold transition-all shrink-0 {{ $activeGameTab === 'sky_soccer' ? 'bg-violet-600 text-white shadow-sm' : 'bg-base-100 border border-base-200 text-base-content/60 hover:bg-base-200' }}">
                🚁 {{ __('Drone Sky Soccer') }}
            </button>
            <button wire:click="setTab('obstacle')" 
                    class="px-4 py-2 rounded-xl text-xs font-bold transition-all shrink-0 {{ $activeGameTab === 'obstacle' ? 'bg-amber-500 text-white shadow-sm' : 'bg-base-100 border border-base-200 text-base-content/60 hover:bg-base-200' }}">
                🏁 {{ __('Drone Obstacle') }}
            </button>
        </div>
    </div>

    {{-- ========================================================= --}}
    {{-- CATEGORY READINESS PIPELINE CARDS                        --}}
    {{-- ========================================================= --}}
    @php $categories = $this->categoryStatuses(); @endphp
    @if(empty($categories))
        <div class="bg-base-100 border border-base-200 rounded-2xl p-8 text-center text-base-content/40">
            <p class="font-bold text-sm">{{ __('Tiada data kategori dijumpai untuk permainan ini.') }}</p>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
            @foreach($categories as $item)
            <div class="bg-white rounded-2xl border {{ $item['status_type'] === 'ready_for_knockout' ? 'border-emerald-400 ring-2 ring-emerald-400/30' : 'border-base-200' }} shadow-sm overflow-hidden flex flex-col transition-all hover:shadow-md">
                
                {{-- Card Header --}}
                <div class="p-5 border-b border-base-200 bg-base-50/50 flex items-start justify-between gap-3">
                    <div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <h3 class="font-extrabold text-base text-base-content">{{ $item['category_name'] }}</h3>
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full {{ $item['game_type'] === 'isobot' ? 'bg-blue-100 text-blue-700' : ($item['game_type'] === 'sky_soccer' ? 'bg-violet-100 text-violet-700' : 'bg-amber-100 text-amber-700') }}">
                                {{ $item['game_label'] }}
                            </span>
                        </div>
                        <p class="text-xs text-base-content/50 mt-0.5 font-medium">
                            {{ $item['total_teams'] }} {{ __('Pasukan Berdaftar') }}
                        </p>
                    </div>

                    <span class="text-[10px] font-bold px-2.5 py-1 rounded-xl border text-center shrink-0 {{ $item['status_badge_class'] }}">
                        {{ __($item['status_text']) }}
                    </span>
                </div>

                {{-- Card Pipeline Steps --}}
                <div class="p-5 space-y-4 flex-1 flex flex-col justify-between">

                    {{-- Step 1: Attendance Progress --}}
                    <div class="space-y-1.5">
                        <div class="flex justify-between items-center text-xs">
                            <span class="font-bold text-base-content/70 flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full {{ $item['attendance_pct'] == 100 ? 'bg-emerald-500' : 'bg-amber-400' }}"></span>
                                {{ __('1. Kehadiran') }}
                            </span>
                            <span class="font-bold {{ $item['attendance_pct'] == 100 ? 'text-emerald-600' : 'text-base-content/70' }}">
                                {{ $item['checked_in_teams'] }}/{{ $item['total_teams'] }} ({{ $item['attendance_pct'] }}%)
                            </span>
                        </div>
                        <div class="w-full bg-base-200 rounded-full h-2 overflow-hidden">
                            <div class="h-2 rounded-full {{ $item['attendance_pct'] == 100 ? 'bg-emerald-500' : 'bg-amber-400' }} transition-all duration-500" style="width: {{ $item['attendance_pct'] }}%"></div>
                        </div>
                    </div>

                    {{-- Step 2: Group Matches / Runs Progress --}}
                    <div class="space-y-1.5">
                        <div class="flex justify-between items-center text-xs">
                            <span class="font-bold text-base-content/70 flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full {{ $item['group_progress_pct'] == 100 && $item['total_group_matches'] > 0 ? 'bg-emerald-500' : ($item['total_groups'] > 0 ? 'bg-amber-400' : 'bg-base-300') }}"></span>
                                @if($item['is_obstacle']) {{ __('2. Giliran Laluan') }} @else {{ __('2. Perlawanan Kumpulan') }} @endif
                            </span>
                            <span class="font-bold {{ $item['group_progress_pct'] == 100 && $item['total_group_matches'] > 0 ? 'text-emerald-600' : 'text-base-content/70' }}">
                                @if($item['total_groups'] == 0)
                                    <span class="text-base-content/40">{{ __('Belum Dijana') }}</span>
                                @else
                                    {{ $item['completed_group_matches'] }}/{{ $item['total_group_matches'] }} ({{ $item['group_progress_pct'] }}%)
                                @endif
                            </span>
                        </div>
                        <div class="w-full bg-base-200 rounded-full h-2 overflow-hidden">
                            <div class="h-2 rounded-full {{ $item['group_progress_pct'] == 100 ? 'bg-emerald-500' : 'bg-primary' }} transition-all duration-500" style="width: {{ $item['group_progress_pct'] }}%"></div>
                        </div>
                        @if($item['pending_group_matches'] > 0 && $item['total_groups'] > 0)
                            <div class="flex justify-between items-center text-[11px] pt-1">
                                <span class="text-amber-600 font-semibold">⏳ {{ $item['pending_group_matches'] }} {{ __('perlawanan belum selesai') }}</span>
                                <button wire:click="showUnfinishedMatches({{ $item['category_id'] }}, '{{ $item['game_type'] }}')" 
                                        class="text-primary hover:underline font-bold text-[11px]">
                                    {{ __('Lihat Baki') }} 🔍
                                </button>
                            </div>
                        @endif
                    </div>

                    {{-- Step 3: Knockout Readiness / Standings Status --}}
                    <div class="pt-3 border-t border-base-200">
                        @if($item['is_obstacle'] || $item['is_round_robin_only'])
                            <div class="bg-base-50 border border-base-200 rounded-xl p-3 flex items-center justify-between">
                                <div>
                                    <p class="text-[10px] font-bold uppercase tracking-wider text-base-content/40">{{ __('Format Pertandingan') }}</p>
                                    <p class="text-xs font-bold text-base-content mt-0.5">
                                        @if($item['is_obstacle']) 🏁 {{ __('Pengiraan Masa Terpantas') }} @else 🏆 {{ __('Kedudukan Liga Kumpulan') }} @endif
                                    </p>
                                </div>
                                <a href="{{ route('admin.groups.show', ['game' => $item['game_type'], 'category' => $item['category_slug']]) }}" 
                                   class="btn btn-xs btn-ghost text-primary font-bold">
                                    {{ __('Lihat Skor') }} →
                                </a>
                            </div>
                        @elseif($item['status_type'] === 'ready_for_knockout')
                            {{-- HIGHLIGHT: READY TO GENERATE KNOCKOUT! --}}
                            <div class="bg-emerald-50 border-2 border-emerald-300 rounded-xl p-3.5 space-y-2">
                                <div class="flex items-center gap-2">
                                    <span class="text-lg">🎉</span>
                                    <div>
                                        <p class="text-xs font-black text-emerald-800">{{ __('Semua Kumpulan Selesai!') }}</p>
                                        <p class="text-[10px] text-emerald-700">{{ __('Sedia untuk menjana pusingan kalah mati.') }}</p>
                                    </div>
                                </div>
                                <a href="{{ route('admin.knockout.index') }}" 
                                   class="w-full bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-extrabold py-2 px-3 rounded-lg shadow transition-all flex items-center justify-center gap-1.5">
                                    <span>⚡</span>
                                    <span>{{ __('Jana Bracket Kalah Mati Sekarang') }}</span>
                                    <span>→</span>
                                </a>
                            </div>
                        @elseif($item['has_knockout_generated'])
                            <div class="bg-indigo-50 border border-indigo-200 rounded-xl p-3 flex items-center justify-between">
                                <div>
                                    <p class="text-[10px] font-bold uppercase tracking-wider text-indigo-500">{{ __('Carta Kalah Mati') }}</p>
                                    <p class="text-xs font-bold text-indigo-900 mt-0.5">
                                        {{ $item['completed_knockout_matches'] }}/{{ $item['total_knockout_matches'] }} {{ __('Perlawanan Selesai') }}
                                    </p>
                                </div>
                                <a href="{{ route('admin.knockout.show', ['category' => $item['category_id']]) }}" 
                                   class="btn btn-xs btn-primary font-bold">
                                    {{ __('Lihat Carta') }} 📊
                                </a>
                            </div>
                        @else
                            <div class="bg-base-100 border border-base-200 rounded-xl p-3 text-xs text-base-content/50 flex items-center justify-between">
                                <span>🔒 {{ __('Kalah Mati: Menunggu kumpulan selesai') }}</span>
                                @if($item['total_groups'] == 0)
                                    <a href="{{ route('admin.groups.index') }}" class="text-primary font-bold text-xs hover:underline">
                                        {{ __('Jana Kump') }} →
                                    </a>
                                @endif
                            </div>
                        @endif
                    </div>

                </div>
            </div>
            @endforeach
        </div>
    @endif

    {{-- ========================================================= --}}
    {{-- LIVE FIELD / PITCH ACTIVITY MATRIX                       --}}
    {{-- ========================================================= --}}
    @php $fields = $this->fieldLiveMatrix(); @endphp
    @if(!empty($fields))
        <div class="bg-white rounded-2xl border border-base-200 shadow-sm p-6 space-y-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg bg-secondary/10 flex items-center justify-center text-secondary">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    </div>
                    <div>
                        <h3 class="font-extrabold text-base text-base-content">{{ __('Status Padang & Giliran Semasa') }}</h3>
                        <p class="text-xs text-base-content/50">{{ __('Pantau aktiviti semasa pengadil di setiap padang perlawanan.') }}</p>
                    </div>
                </div>
                <a href="{{ route('admin.matches.index') }}" class="btn btn-sm btn-ghost text-primary text-xs font-bold">
                    {{ __('Buka Papan Pengadil') }} →
                </a>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                @foreach($fields as $f)
                    <div class="border rounded-2xl p-4 transition-all {{ $f['active_match'] ? 'bg-gradient-to-br from-red-50/50 via-white to-white border-red-200 shadow-sm' : 'bg-base-50/50 border-base-200' }}">
                        <div class="flex justify-between items-center mb-3">
                            <div class="flex items-center gap-2">
                                <span class="font-extrabold text-sm text-base-content">
                                    {{ is_numeric($f['field_number']) ? __('Padang') . ' ' . $f['field_number'] : $f['field_number'] }}
                                </span>
                            </div>
                            @if($f['active_match'])
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-black bg-red-500 text-white uppercase tracking-wider animate-pulse">
                                    ● LIVE
                                </span>
                            @else
                                <span class="text-[10px] font-semibold text-base-content/40 bg-base-200 px-2 py-0.5 rounded-full">
                                    {{ $f['completed_count'] }}/{{ $f['total_matches'] }} {{ __('Selesai') }}
                                </span>
                            @endif
                        </div>

                        {{-- Active Match --}}
                        @if($f['active_match'])
                            @php $m = $f['active_match']; @endphp
                            <div class="bg-white rounded-xl p-3 border border-red-100 shadow-2xs space-y-2 mb-2">
                                <span class="text-[9px] font-bold text-primary bg-primary/10 px-2 py-0.5 rounded-full block w-fit">
                                    {{ optional(optional($m->group)->category)->name ?? optional($m->category)->name ?? '' }}
                                </span>
                                @if($m->group && $m->group->game_type === 'obstacle')
                                    <p class="font-bold text-xs text-base-content truncate">{{ $m->homeTeam->team_name ?? 'BYE' }}</p>
                                    <p class="text-[10px] text-base-content/50 truncate">🏫 {{ $m->homeTeam->school_name ?? '' }}</p>
                                @else
                                    <div class="grid grid-cols-[1fr_auto_1fr] items-center gap-1 text-center">
                                        <p class="font-bold text-xs text-base-content truncate text-right">{{ $m->homeTeam->team_name ?? 'BYE' }}</p>
                                        <span class="text-xs font-black text-primary px-1.5 py-0.5 bg-primary/10 rounded">{{ $m->home_score ?? 0 }} - {{ $m->away_score ?? 0 }}</span>
                                        <p class="font-bold text-xs text-base-content truncate text-left">{{ $m->awayTeam->team_name ?? 'BYE' }}</p>
                                    </div>
                                @endif
                            </div>
                        @else
                            <div class="p-3 text-center text-xs text-base-content/30 border border-dashed border-base-200 rounded-xl mb-2">
                                {{ __('Tiada Perlawanan Sedang Berlangsung') }}
                            </div>
                        @endif

                        {{-- Next Scheduled --}}
                        @if($f['upcoming_match'])
                            @php $up = $f['upcoming_match']; @endphp
                            <div class="text-[11px] text-base-content/60 bg-base-100 rounded-lg p-2 border border-base-200 flex items-center justify-between">
                                <span class="text-[10px] text-base-content/40 font-bold uppercase">{{ __('Seterusnya:') }}</span>
                                <span class="font-semibold truncate ml-2 text-right">
                                    @if($up->group && $up->group->game_type === 'obstacle')
                                        {{ $up->homeTeam->team_name ?? 'BYE' }}
                                    @else
                                        {{ $up->homeTeam->team_name ?? 'BYE' }} vs {{ $up->awayTeam->team_name ?? 'BYE' }}
                                    @endif
                                </span>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- ========================================================= --}}
    {{-- MODAL: UNFINISHED MATCHES RESOLUTION                     --}}
    {{-- ========================================================= --}}
    @if($viewUnfinishedCategoryId)
        @php $uData = $this->modalUnfinishedData(); @endphp
        @if($uData)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4 animate-fade-in">
            <div class="bg-white rounded-3xl shadow-2xl w-full max-w-2xl max-h-[85vh] flex flex-col overflow-hidden animate-scale-in border border-base-200">
                <div class="p-6 border-b border-base-200 bg-base-50 flex justify-between items-center">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-xl">🔍</span>
                            <h3 class="font-black text-lg text-base-content">
                                {{ __('Perlawanan Belum Selesai') }} — {{ $uData['category']->name }}
                            </h3>
                        </div>
                        <p class="text-xs text-base-content/50 mt-0.5">
                            {{ __('Selesaikan perlawanan di bawah untuk membolehkan carta kalah mati dijana.') }}
                        </p>
                    </div>
                    <button wire:click="closeUnfinishedModal" class="w-8 h-8 rounded-full bg-base-200 text-base-content/50 hover:text-base-content flex items-center justify-center font-bold text-sm">✕</button>
                </div>

                <div class="p-6 overflow-y-auto space-y-3 flex-1">
                    @forelse($uData['matches'] as $um)
                        <div class="bg-white rounded-xl border border-base-200 p-4 shadow-2xs flex flex-col sm:flex-row sm:items-center justify-between gap-3 hover:border-primary/50 transition-colors">
                            <div class="space-y-1">
                                <div class="flex items-center gap-2">
                                    <span class="text-[10px] font-bold bg-primary/10 text-primary px-2 py-0.5 rounded-full">
                                        {{ $um->group->group_name ?? 'Kumpulan' }}
                                    </span>
                                    <span class="text-[10px] font-semibold text-base-content/50">
                                        {{ is_numeric($um->field_number) ? __('Padang') . ' ' . $um->field_number : ($um->field_number ?: __('Belum Set Padang')) }}
                                    </span>
                                    @if($um->status === 'in_progress')
                                        <span class="text-[10px] font-bold bg-red-100 text-red-700 px-2 py-0.5 rounded-full">● LIVE</span>
                                    @else
                                        <span class="text-[10px] font-medium bg-base-200 text-base-content/60 px-2 py-0.5 rounded-full">{{ __('Dijadualkan') }}</span>
                                    @endif
                                </div>
                                <div class="font-bold text-sm text-base-content">
                                    @if($um->group && $um->group->game_type === 'obstacle')
                                        {{ $um->homeTeam->team_name ?? 'BYE' }} <span class="text-xs text-base-content/50 font-normal">({{ $um->homeTeam->school_name ?? '' }})</span>
                                    @else
                                        <span>{{ $um->homeTeam->team_name ?? 'BYE' }}</span>
                                        <span class="text-base-content/40 font-normal mx-1">vs</span>
                                        <span>{{ $um->awayTeam->team_name ?? 'BYE' }}</span>
                                    @endif
                                </div>
                            </div>

                            <a href="{{ route('admin.matches.index') }}" 
                               class="btn btn-sm btn-primary rounded-xl text-xs font-bold shrink-0 gap-1">
                                ✏️ {{ __('Kemas Kini Skor') }}
                            </a>
                        </div>
                    @empty
                        <div class="text-center py-8 text-emerald-600 font-bold">
                            ✓ {{ __('Semua perlawanan kumpulan telah selesai!') }}
                        </div>
                    @endforelse
                </div>

                <div class="p-4 border-t border-base-200 bg-base-50 flex justify-end">
                    <button wire:click="closeUnfinishedModal" class="btn btn-sm btn-ghost rounded-xl px-5">
                        {{ __('Tutup') }}
                    </button>
                </div>
            </div>
        </div>
        @endif
    @endif

</div>
