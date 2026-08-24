<?php

use App\Models\Team;
use App\Models\Group;
use App\Models\Category;
use App\Models\TournamentMatch;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;

new class extends Component {
    
    #[Url(as: 'tab')]
    public string $activeTab = 'search';

    #[Url(as: 'q')]
    public string $search = '';

    public ?int $viewTeamId = null;
    
    public string $selectedFilterId = '';
    public ?int $selectedKnockoutCategoryId = null;

    public function mount()
    {
        $categories = Category::all();
        if ($categories->isNotEmpty()) {
            $this->selectedFilterId = (string)$categories->first()->id;
            $this->selectedKnockoutCategoryId = $categories->first()->id;
        }
    }

    public function setTab($tab)
    {
        $this->activeTab = $tab;
    }

    public function viewTeam($teamId)
    {
        $this->viewTeamId = $teamId;
    }

    public function clearViewTeam()
    {
        $this->viewTeamId = null;
    }

    #[Computed]
    public function searchResults()
    {
        if (strlen(trim($this->search)) < 3) {
            return collect();
        }
        
        return Team::with(['category', 'groupTeams.group'])
            ->where(function ($q) {
                $q->where('team_name', 'like', '%' . $this->search . '%')
                  ->orWhere('school_name', 'like', '%' . $this->search . '%');
            })
            ->orderBy('team_name')
            ->get();
    }

    #[Computed]
    public function myTeamDetails()
    {
        if (!$this->viewTeamId) return null;
        
        $team = Team::with(['category', 'groupTeams.group'])->find($this->viewTeamId);
        if (!$team) return null;
        
        $matches = TournamentMatch::with(['homeTeam', 'awayTeam', 'group'])
            ->where('home_team_id', $this->viewTeamId)
            ->orWhere('away_team_id', $this->viewTeamId)
            ->orderBy('id')
            ->get();
            
        return [
            'team' => $team,
            'matches' => $matches
        ];
    }
    
    #[Computed]
    public function categories()
    {
        return Category::all();
    }

    #[Computed]
    public function standingsFilters()
    {
        $filters = [];
        $categories = Category::all();
        foreach ($categories as $cat) {
            $name = strtolower($cat->name);
            if (str_contains($name, 'u12 & ppki') || str_contains($name, 'u15 & u20')) {
                $filters[] = ['id' => $cat->id . '_sky', 'label' => 'Sky Soccer - ' . $cat->name, 'category_id' => $cat->id, 'type' => 'sky'];
                $filters[] = ['id' => $cat->id . '_obs', 'label' => 'Obstacle - ' . $cat->name, 'category_id' => $cat->id, 'type' => 'obs'];
            } else {
                $filters[] = ['id' => (string)$cat->id, 'label' => 'Isobot Soccer - ' . $cat->name, 'category_id' => $cat->id, 'type' => 'isobot'];
            }
        }
        return collect($filters);
    }
    
    #[Computed]
    public function publicGroups()
    {
        if (!$this->selectedFilterId) return collect();
        $filter = $this->standingsFilters->firstWhere('id', $this->selectedFilterId);
        if (!$filter) return collect();
        
        $catId = $filter['category_id'];
        
        if ($filter['type'] === 'obs') {
            $groupTeams = \App\Models\GroupTeam::with(['team', 'group'])
                ->whereHas('group', function($q) use ($catId) {
                    $q->where('category_id', $catId)->where('game_type', 'obstacle');
                })
                ->orderByRaw('CASE WHEN goals_for > 0 THEN 0 ELSE 1 END')
                ->orderBy('goals_for', 'asc')
                ->get();
                
            $fakeGroup = new Group(['group_name' => 'Kedudukan Keseluruhan (Ranking)', 'game_type' => 'obstacle']);
            $fakeGroup->standings = $groupTeams;
            return collect([$fakeGroup]);
        }
        
        return Group::with('groupTeams.team')
            ->where('category_id', $catId)
            ->where('game_type', '!=', 'obstacle')
            ->get()
            ->map(function($g) {
                $g->standings = $g->getStandings();
                return $g;
            });
    }

    #[Computed]
    public function knockoutBrackets()
    {
        if (!$this->selectedKnockoutCategoryId) return collect();
        return TournamentMatch::with(['homeTeam', 'awayTeam'])
            ->where('category_id', $this->selectedKnockoutCategoryId)
            ->whereIn('stage', ['trophy_knockout', 'cup_knockout'])
            ->get()
            ->groupBy(['stage', 'round_name']);
    }
};
?>

<div class="min-h-screen bg-base-200 pb-24">
    <!-- Top Nav / Header -->
    <div class="bg-primary text-primary-content sticky top-0 z-40 shadow-md">
        <div class="max-w-3xl mx-auto px-4 py-4 flex items-center justify-between">
            <div>
                <h1 class="text-xl font-black italic tracking-wider uppercase flex items-center gap-2">
                    <svg class="w-6 h-6 text-yellow-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                    Hab Peserta
                </h1>
                <p class="text-[10px] font-bold text-primary-content/70 tracking-widest uppercase">Portal Rasmi Kejohanan</p>
            </div>
            
            <a href="/" class="btn btn-sm btn-ghost btn-circle">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" /></svg>
            </a>
        </div>
    </div>

    <!-- Main Content Area -->
    <div class="max-w-3xl mx-auto p-4 space-y-6">
        
        {{-- ============================================== --}}
        {{-- TAB 1: CARIAN & JADUAL PERIBADI                --}}
        {{-- ============================================== --}}
        @if($activeTab === 'search')
            
            @if(!$viewTeamId)
                <div class="text-center space-y-3 mt-6 mb-8 animate-fade-in">
                    <h2 class="text-3xl font-black text-base-content leading-tight">Semak <span class="text-primary">Status</span> & <br>Jadual <span class="text-secondary">Pasukan</span> Anda.</h2>
                    <p class="text-sm text-base-content/60">Taip nama pasukan atau nama sekolah untuk melihat maklumat terperinci pendaftaran dan jadual penuh.</p>
                </div>

                <div class="bg-white p-2 rounded-2xl shadow-lg border border-base-200 flex items-center gap-2 sticky top-20 z-30 animate-slide-up">
                    <div class="pl-3 text-base-content/40">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                    </div>
                    <input wire:model.live.debounce.500ms="search" type="text" placeholder="Contoh: SK Gombak..." class="flex-1 bg-transparent py-3 text-base font-medium focus:outline-none w-full" autofocus>
                    @if(strlen($search) > 0)
                        <button wire:click="$set('search', '')" class="p-2 text-base-content/40 hover:text-base-content rounded-xl transition-colors">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    @endif
                </div>

                <div class="mt-8 space-y-4">
                    <div wire:loading class="w-full text-center py-8">
                        <span class="loading loading-spinner text-primary"></span>
                        <p class="text-sm text-base-content/50 mt-2 font-medium">Mencari rekod...</p>
                    </div>

                    <div wire:loading.remove>
                        @if(strlen(trim($search)) >= 3)
                            @if($this->searchResults->isEmpty())
                                <div class="text-center py-12 bg-white rounded-3xl border border-base-200 border-dashed">
                                    <p class="text-lg font-bold text-base-content/40">Tiada pasukan dijumpai.</p>
                                </div>
                            @else
                                <p class="text-xs font-bold text-base-content/50 uppercase tracking-widest px-2 mb-3">Hasil Carian ({{ $this->searchResults->count() }})</p>
                                <div class="grid grid-cols-1 gap-3">
                                    @foreach($this->searchResults as $team)
                                        <button wire:click="viewTeam({{ $team->id }})" class="w-full text-left bg-white border-2 border-base-200 hover:border-primary p-4 rounded-2xl shadow-sm transition-all flex items-center justify-between group">
                                            <div>
                                                <h3 class="font-bold text-base-content text-lg group-hover:text-primary transition-colors">{{ $team->team_name }}</h3>
                                                <p class="text-xs text-base-content/60 font-medium">🏫 {{ $team->school_name }}</p>
                                            </div>
                                            <div class="shrink-0 flex flex-col items-end gap-2">
                                                @if($team->status === 'checked_in')
                                                    <span class="bg-emerald-100 text-emerald-700 text-[10px] font-black px-2 py-1 rounded-md uppercase tracking-wider">Hadir</span>
                                                @else
                                                    <span class="bg-base-200 text-base-content/50 text-[10px] font-black px-2 py-1 rounded-md uppercase tracking-wider">Berdaftar</span>
                                                @endif
                                                <svg class="w-5 h-5 text-base-content/20 group-hover:text-primary group-hover:translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                            </div>
                                        </button>
                                    @endforeach
                                </div>
                            @endif
                        @endif
                    </div>
                </div>
            @else
                {{-- Personalized Dashboard View --}}
                @php $details = $this->myTeamDetails; @endphp
                @if($details)
                    <div class="animate-fade-in">
                        <button wire:click="clearViewTeam" class="inline-flex items-center gap-2 text-sm font-bold text-base-content/60 hover:text-primary mb-4 transition-colors">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                            Kembali ke carian
                        </button>

                        <!-- ID Card -->
                        <div class="bg-gradient-to-br from-primary to-primary-focus p-6 rounded-3xl shadow-xl text-white relative overflow-hidden">
                            <div class="absolute -right-4 -bottom-4 opacity-10">
                                <svg class="w-48 h-48" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2L2 22h20L12 2z"/></svg>
                            </div>
                            <div class="relative z-10">
                                <div class="inline-block bg-white/20 px-3 py-1 rounded-full text-xs font-bold tracking-widest uppercase mb-3 backdrop-blur-sm border border-white/20">
                                    {{ $details['team']->category->name ?? 'Kategori Umum' }}
                                </div>
                                <h2 class="text-3xl font-black leading-tight">{{ $details['team']->team_name }}</h2>
                                <p class="text-white/80 font-medium mt-1">🏫 {{ $details['team']->school_name }}</p>
                                
                                <div class="mt-6 flex flex-wrap gap-3">
                                    @if($details['team']->status === 'checked_in')
                                        <div class="bg-emerald-400 text-emerald-950 text-xs font-black px-3 py-1.5 rounded-lg flex items-center gap-1.5 shadow-lg shadow-emerald-500/20">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                            DISAHKAN HADIR
                                        </div>
                                    @else
                                        <div class="bg-white/20 text-white border border-white/30 text-xs font-black px-3 py-1.5 rounded-lg flex items-center gap-1.5 backdrop-blur-md">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            BELUM CHECK-IN
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Schedule List -->
                        <div class="mt-8">
                            <h3 class="text-sm font-black text-base-content/60 uppercase tracking-widest mb-4 flex items-center gap-2">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                Jadual Perlawanan Pasukan
                            </h3>
                            
                            @if($details['matches']->isEmpty())
                                <div class="bg-white border-2 border-base-200 border-dashed rounded-3xl p-8 text-center">
                                    <p class="text-base-content/40 font-bold">Jadual perlawanan belum dikeluarkan.</p>
                                </div>
                            @else
                                <div class="space-y-4">
                                    @foreach($details['matches'] as $match)
                                        @php
                                            $isHome = $match->home_team_id === $this->viewTeamId;
                                            $opponent = $isHome ? $match->awayTeam : $match->homeTeam;
                                            $myScore = $isHome ? $match->home_score : $match->away_score;
                                            $oppScore = $isHome ? $match->away_score : $match->home_score;
                                            $isWin = $match->winner_team_id === $this->viewTeamId;
                                            $isLoss = $match->winner_team_id && $match->winner_team_id !== $this->viewTeamId;
                                        @endphp
                                        
                                        <div class="bg-white border-2 {{ $match->status === 'in_progress' ? 'border-primary shadow-lg shadow-primary/10' : 'border-base-200' }} rounded-2xl p-5 relative overflow-hidden">
                                            @if($match->status === 'in_progress')
                                                <div class="absolute top-0 right-0 bg-primary text-white text-[9px] font-black uppercase tracking-widest px-3 py-1 rounded-bl-xl animate-pulse">
                                                    SEDANG BERLANGSUNG
                                                </div>
                                            @endif
                                            
                                            <div class="flex justify-between items-start mb-3">
                                                <div class="inline-block bg-base-200 text-base-content/60 text-[10px] font-bold px-2 py-0.5 rounded uppercase tracking-wider">
                                                    {{ $match->stage === 'group' ? ($match->group->group_name ?? 'Kumpulan') : $match->round_name }}
                                                </div>
                                                
                                                @if($match->field_number)
                                                    <div class="text-xs font-bold text-secondary">
                                                        Padang {{ $match->field_number }}
                                                    </div>
                                                @endif
                                            </div>

                                            <div class="flex items-center gap-4">
                                                <!-- Opponent Info -->
                                                <div class="flex-1">
                                                    <p class="text-[10px] font-bold text-base-content/40 uppercase">Lawan</p>
                                                    <h4 class="font-bold text-base-content text-lg leading-tight">{{ $opponent->team_name ?? 'TBD' }}</h4>
                                                </div>
                                                
                                                <!-- Score/Status -->
                                                <div class="shrink-0 text-center">
                                                    @if($match->status === 'completed')
                                                        @if($match->group && $match->group->game_type === 'obstacle')
                                                            <!-- Obstacle Time Result -->
                                                            <div class="bg-base-200 px-3 py-1 rounded-lg">
                                                                <span class="text-xs font-bold text-base-content/50 block">Masa Direkod</span>
                                                                <span class="text-lg font-black text-emerald-600">{{ $match->formatted_obstacle_time }}</span>
                                                            </div>
                                                        @else
                                                            <div class="bg-base-100 border border-base-200 px-4 py-2 rounded-xl flex items-center justify-center gap-3">
                                                                <span class="text-xl font-black {{ $isWin ? 'text-emerald-600' : ($isLoss ? 'text-red-500' : 'text-base-content') }}">{{ $myScore ?? 0 }}</span>
                                                                <span class="text-xs font-bold text-base-content/30">-</span>
                                                                <span class="text-xl font-black {{ $isLoss ? 'text-emerald-600' : ($isWin ? 'text-red-500' : 'text-base-content') }}">{{ $oppScore ?? 0 }}</span>
                                                            </div>
                                                            @if($isWin) <p class="text-[10px] font-bold text-emerald-600 uppercase mt-1">MENANG</p>
                                                            @elseif($isLoss) <p class="text-[10px] font-bold text-red-500 uppercase mt-1">KALAH</p>
                                                            @else <p class="text-[10px] font-bold text-base-content/50 uppercase mt-1">SERI</p>
                                                            @endif
                                                        @endif
                                                    @else
                                                        <span class="text-xs font-bold text-base-content/40 uppercase bg-base-200 px-3 py-1.5 rounded-lg block">Belum Mula</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                @endif
            @endif
        @endif

        {{-- ============================================== --}}
        {{-- TAB 2: KEDUDUKAN LIGA (STANDINGS)              --}}
        {{-- ============================================== --}}
        @if($activeTab === 'standings')
            <div class="animate-fade-in space-y-6">
                <div>
                    <h2 class="text-2xl font-black text-base-content">Kedudukan Awam</h2>
                    <p class="text-sm text-base-content/60">Pilih kategori untuk melihat carta kedudukan terkini peringkat kumpulan/masa.</p>
                </div>

                <!-- Category Selector -->
                <div class="overflow-x-auto pb-2 -mx-4 px-4 hide-scrollbar">
                    <div class="flex gap-2 w-max">
                        @foreach($this->standingsFilters as $filter)
                            <button wire:click="$set('selectedFilterId', '{{ $filter['id'] }}')" 
                                class="px-4 py-2 rounded-xl text-sm font-bold border-2 transition-all whitespace-nowrap {{ $selectedFilterId === $filter['id'] ? 'bg-primary border-primary text-white shadow-md shadow-primary/20' : 'bg-white border-base-200 text-base-content/70 hover:border-primary/50' }}">
                                {{ $filter['label'] }}
                            </button>
                        @endforeach
                    </div>
                </div>

                <!-- Groups List -->
                @if($this->publicGroups->isEmpty())
                    <div class="bg-white border-2 border-base-200 border-dashed rounded-3xl p-10 text-center">
                        <svg class="w-12 h-12 text-base-content/20 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                        <p class="text-base-content/50 font-bold">Tiada data liga untuk kategori ini.</p>
                    </div>
                @else
                    <div class="space-y-6">
                        @foreach($this->publicGroups as $group)
                            <div class="bg-white rounded-3xl border border-base-200 shadow-sm overflow-hidden">
                                <div class="bg-base-100 px-5 py-3 border-b border-base-200 flex justify-between items-center">
                                    <h3 class="font-black text-base-content">{{ $group->group_name }}</h3>
                                    <span class="text-[10px] font-bold uppercase text-base-content/40 tracking-wider">
                                        {{ $group->game_type === 'obstacle' ? 'Senarai Masa Terbaik' : 'Carta Liga' }}
                                    </span>
                                </div>
                                
                                <div class="divide-y divide-base-100">
                                    @foreach($group->standings as $index => $gt)
                                        <div class="p-3 flex items-center gap-3 hover:bg-base-50 transition-colors">
                                            <div class="w-6 text-center font-black text-sm {{ $index < 2 && $group->game_type !== 'obstacle' ? 'text-primary' : 'text-base-content/30' }}">
                                                {{ $index + 1 }}
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <h4 class="font-bold text-sm text-base-content truncate">{{ $gt->team->team_name }}</h4>
                                                <p class="text-[10px] text-base-content/50 truncate">{{ $gt->team->school_name }}</p>
                                            </div>
                                            
                                            <div class="shrink-0 text-right">
                                                @if($group->game_type === 'obstacle')
                                                    @if($gt->goals_for > 0)
                                                        @php
                                                            $ms = $gt->goals_for;
                                                            $m = floor($ms / 60000);
                                                            $s = floor(($ms % 60000) / 1000);
                                                            $ms_remain = $ms % 1000;
                                                        @endphp
                                                        <span class="font-black text-emerald-600 text-sm bg-emerald-50 px-2 py-1 rounded-md">{{ sprintf('%02d:%02d.%03d', $m, $s, $ms_remain) }}</span>
                                                    @else
                                                        <span class="font-bold text-base-content/30 text-xs">-</span>
                                                    @endif
                                                @else
                                                    <div class="grid grid-cols-4 gap-1 min-w-[155px] text-center items-center">
                                                        <div class="text-center">
                                                            <span class="block text-[8px] font-bold text-base-content/40 uppercase whitespace-nowrap">Main</span>
                                                            <span class="text-xs font-bold text-base-content/80">{{ $gt->played }}</span>
                                                        </div>
                                                        <div class="text-center">
                                                            <span class="block text-[8px] font-bold text-base-content/40 uppercase whitespace-nowrap">Gol</span>
                                                            <span class="text-xs font-black text-amber-600">{{ $gt->goals_for }}</span>
                                                        </div>
                                                        <div class="text-center">
                                                            <span class="block text-[8px] font-bold text-base-content/40 uppercase whitespace-nowrap">Menang</span>
                                                            <span class="text-xs font-bold text-emerald-600">{{ $gt->won }}</span>
                                                        </div>
                                                        <div class="text-center bg-base-100 rounded py-0.5 px-1">
                                                            <span class="block text-[8px] font-bold text-base-content/40 uppercase whitespace-nowrap">Mata</span>
                                                            <span class="text-sm font-black text-primary">{{ $gt->points }}</span>
                                                        </div>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        @endif

        {{-- ============================================== --}}
        {{-- TAB 3: KALAH MATI (KNOCKOUT)                   --}}
        {{-- ============================================== --}}
        @if($activeTab === 'knockout')
            <div class="animate-fade-in space-y-6">
                <div>
                    <h2 class="text-2xl font-black text-base-content">Carta Kalah Mati</h2>
                    <p class="text-sm text-base-content/60">Carta pusingan akhir (Knockout Bracket).</p>
                </div>

                <!-- Category Selector -->
                <div class="overflow-x-auto pb-2 -mx-4 px-4 hide-scrollbar">
                    <div class="flex gap-2 w-max">
                        @foreach($this->standingsFilters->where('type', '!=', 'obs') as $filter)
                            <button wire:click="$set('selectedKnockoutCategoryId', {{ $filter['category_id'] }})" 
                                class="px-4 py-2 rounded-xl text-sm font-bold border-2 transition-all whitespace-nowrap {{ $selectedKnockoutCategoryId === $filter['category_id'] ? 'bg-primary border-primary text-white shadow-md shadow-primary/20' : 'bg-white border-base-200 text-base-content/70 hover:border-primary/50' }}">
                                {{ $filter['label'] }}
                            </button>
                        @endforeach
                    </div>
                </div>

                @if($this->knockoutBrackets->isEmpty())
                    <div class="bg-white border-2 border-base-200 border-dashed rounded-3xl p-10 text-center">
                        <svg class="w-12 h-12 text-base-content/20 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                        <p class="text-base-content/50 font-bold">Carta kalah mati belum dijana untuk kategori ini.</p>
                    </div>
                @else
                    @foreach($this->knockoutBrackets as $stage => $rounds)
                        <div class="bg-white rounded-3xl border-2 border-base-200 shadow-sm overflow-hidden mb-6">
                            <div class="bg-gradient-to-r {{ $stage === 'trophy_knockout' ? 'from-amber-400 to-yellow-500' : 'from-slate-300 to-slate-400' }} px-5 py-4">
                                <h3 class="font-black text-white text-lg">
                                    {{ $stage === 'trophy_knockout' ? '🏆 PUSINGAN TROFI' : '🥈 PUSINGAN PIALA' }}
                                </h3>
                            </div>
                            
                            <div class="p-4 space-y-8">
                                @foreach($rounds as $roundName => $matches)
                                    <div>
                                        <h4 class="text-sm font-black text-base-content/50 uppercase tracking-widest mb-3 border-b border-base-200 pb-2">{{ $roundName }}</h4>
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            @foreach($matches as $match)
                                                <div class="bg-base-50 rounded-xl border border-base-200 p-3 relative flex flex-col justify-center">
                                                    @if($match->winner_team_id)
                                                        <div class="absolute -right-2 -top-2 bg-emerald-500 text-white rounded-full p-1 shadow-sm">
                                                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                                        </div>
                                                    @endif
                                                    
                                                    <!-- Home -->
                                                    <div class="flex justify-between items-center mb-2 {{ $match->winner_team_id === $match->home_team_id ? 'text-primary font-bold' : '' }}">
                                                        <span class="text-sm truncate pr-2 {{ !$match->home_team_id ? 'text-base-content/30 italic' : '' }}">{{ $match->homeTeam->team_name ?? 'Menunggu...' }}</span>
                                                        <span class="font-black">{{ $match->home_score ?? '-' }}</span>
                                                    </div>
                                                    <!-- Away -->
                                                    <div class="flex justify-between items-center {{ $match->winner_team_id === $match->away_team_id ? 'text-primary font-bold' : '' }}">
                                                        <span class="text-sm truncate pr-2 {{ !$match->away_team_id ? 'text-base-content/30 italic' : '' }}">{{ $match->awayTeam->team_name ?? 'Menunggu...' }}</span>
                                                        <span class="font-black">{{ $match->away_score ?? '-' }}</span>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>
        @endif

    </div>

    <!-- Bottom Mobile Navigation Bar -->
    <div class="fixed bottom-0 w-full bg-white border-t border-base-200 pb-safe z-50 shadow-[0_-4px_20px_-10px_rgba(0,0,0,0.1)]">
        <div class="flex justify-around items-center max-w-3xl mx-auto h-16">
            <button wire:click="setTab('search')" class="flex-1 flex flex-col items-center justify-center gap-1 h-full transition-colors {{ $activeTab === 'search' ? 'text-primary' : 'text-base-content/40 hover:text-base-content' }}">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                <span class="text-[10px] font-bold tracking-wider uppercase">Jadual</span>
            </button>
            
            <button wire:click="setTab('standings')" class="flex-1 flex flex-col items-center justify-center gap-1 h-full transition-colors {{ $activeTab === 'standings' ? 'text-primary' : 'text-base-content/40 hover:text-base-content' }}">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
                <span class="text-[10px] font-bold tracking-wider uppercase">Kedudukan</span>
            </button>

            <button wire:click="setTab('knockout')" class="flex-1 flex flex-col items-center justify-center gap-1 h-full transition-colors {{ $activeTab === 'knockout' ? 'text-primary' : 'text-base-content/40 hover:text-base-content' }}">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" /></svg>
                <span class="text-[10px] font-bold tracking-wider uppercase">Kalah Mati</span>
            </button>
        </div>
    </div>
</div>
