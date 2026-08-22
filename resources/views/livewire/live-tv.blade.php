<?php

use App\Models\TournamentMatch;
use App\Models\Category;
use App\Models\Group;
use App\Models\GroupTeam;
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
                'active' => collect(),
                'upcoming' => collect()
            ];

            foreach ($fieldChunk as $field) {
                // Get active match for this field
                $activeMatch = TournamentMatch::with(['homeTeam', 'awayTeam', 'group.category'])
                    ->where('field_number', $field)
                    ->where('status', 'in_progress')
                    ->first();
                    
                if ($activeMatch) {
                    $chunkData['active']->push($activeMatch);
                }

                // Get next scheduled match for this field
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

        // 1. OBSTACLE: Category-wide Top 10
        $obstacleCategories = Category::whereHas('groups', function($q) {
            $q->where('game_type', 'obstacle');
        })->get();

        foreach($obstacleCategories as $cat) {
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
                    'type' => 'obstacle',
                    'title' => 'DRONE OBSTACLE',
                    'subtitle' => 'TOP 10 TERPANTAS • ' . $cat->name,
                    'teams' => $standings
                ];
            }
        }

        // 2. SOCCER: Group Standings (Chunked by 2 groups per slide)
        $soccerGroups = Group::where('game_type', '!=', 'obstacle')
            ->with(['category', 'groupTeams.team'])
            ->get()
            ->groupBy('game_type');

        foreach($soccerGroups as $gameType => $groups) {
            $gameName = $gameType === 'isobot' ? 'ISOBOT SOCCER' : 'DRONE SKY SOCCER';
            
            foreach($groups->chunk(2) as $chunk) {
                $groupsArr = [];
                foreach($chunk as $g) {
                    $groupsArr[] = $g;
                }
                $slides[] = [
                    'type' => 'soccer',
                    'title' => $gameName,
                    'subtitle' => 'KEDUDUKAN KUMPULAN',
                    'groups' => $groupsArr
                ];
            }
        }

        return $slides;
    }

    public $lastCallTs = null;
    #[Computed]
    public function calledMatches()
    {
        $calls = \Illuminate\Support\Facades\Cache::get('live_tv_calls', []);
        $activeMatches = [];
        
        foreach ($calls as $matchId => $callData) {
            if ($callData['expires_at'] > now()->timestamp) {
                $match = TournamentMatch::with(['homeTeam', 'awayTeam', 'group.category'])->find($matchId);
                // Only show call if it's still scheduled
                if ($match && $match->status === 'scheduled') {
                    $activeMatches[] = $match;
                }
            }
        }
        
        return $activeMatches;
    }
};
?>

<div class="h-full grid grid-cols-1 lg:grid-cols-[1fr_2.5fr] gap-8" wire:poll.5s>
    
    <!-- LEFT: LIVE ACTION -->
    <div class="flex flex-col gap-6 h-full relative" 
         x-data="{ 
            activeLeft: 0, 
            totalLeft: {{ $this->fieldChunks()->count() }},
            init() {
                if (this.totalLeft > 1) {
                    setInterval(() => {
                        this.activeLeft = (this.activeLeft + 1) % this.totalLeft;
                    }, 10000); // 10s per match slide
                }
            }
         }">
        
        <div class="flex items-center gap-3">
            <svg class="w-8 h-8 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
            <h2 class="text-2xl font-black text-white tracking-widest uppercase">Aksi Semasa</h2>
        </div>
         
        @if($this->fieldChunks()->isEmpty())
            <div class="flex-1 flex items-center justify-center border-2 border-dashed border-white/10 rounded-2xl">
                <p class="text-white/40 font-bold tracking-widest uppercase">Tiada Perlawanan</p>
            </div>
        @else
            <div class="flex-1 relative overflow-hidden">
                @foreach($this->fieldChunks() as $idx => $chunk)
                    <div x-show="activeLeft === {{ $idx }}"
                         x-transition:enter="transition ease-out duration-500"
                         x-transition:enter-start="opacity-0 -translate-x-8"
                         x-transition:enter-end="opacity-100 translate-x-0"
                         x-transition:leave="transition ease-in duration-300"
                         x-transition:leave-start="opacity-100 translate-x-0"
                         x-transition:leave-end="opacity-0 translate-x-8"
                         class="absolute inset-0 flex flex-col gap-4 overflow-y-auto pr-2"
                         style="display: none; scrollbar-width: none; -ms-overflow-style: none;">
                         
                        @php
                            $active = $chunk['active'];
                            $upcoming = $chunk['upcoming'];
                        @endphp
                        
                        <!-- ACTIVE MATCHES -->
                        @if($active->isNotEmpty())
                            <div class="flex flex-col gap-2">
                                <div class="flex items-center gap-2 mb-1">
                                    <div class="w-2.5 h-2.5 rounded-full bg-primary animate-pulse"></div>
                                    <h2 class="text-lg font-black text-primary tracking-widest uppercase">Sedang Berlangsung</h2>
                                </div>
                                
                                @foreach($active as $match)
                                    <div class="bg-white/5 border border-primary/30 rounded-xl p-3 relative overflow-hidden shrink-0">
                                        <div class="absolute inset-0 border-2 border-primary/20 rounded-xl animate-pulse pointer-events-none"></div>
                                        
                                        <p class="text-[10px] font-bold text-white/50 tracking-widest uppercase mb-1.5">
                                            PADANG {{ $match->field_number ?? '?' }} • {{ $match->group->category->name ?? '' }}
                                        </p>
                                        
                                        @if($match->group && $match->group->game_type === 'obstacle')
                                            <!-- Obstacle Match -->
                                            <div class="flex justify-between items-center">
                                                <div>
                                                    <h4 class="text-base font-bold text-white leading-tight">{{ $match->homeTeam->team_name ?? 'BYE' }}</h4>
                                                    <p class="text-[9px] text-white/40 mt-0.5 uppercase">{{ $match->round_name }}</p>
                                                </div>
                                                <div class="text-right">
                                                    <span class="text-[10px] font-bold text-emerald-400 bg-emerald-400/10 px-2 py-1 rounded-lg">Sesi Penerbangan</span>
                                                </div>
                                            </div>
                                        @else
                                            <!-- Soccer Match -->
                                            <div class="grid grid-cols-[1fr_auto_1fr] gap-2 items-center">
                                                <div class="text-right overflow-hidden">
                                                    <h4 class="text-sm font-bold text-white leading-tight truncate">{{ $match->homeTeam->team_name ?? 'BYE' }}</h4>
                                                    <p class="text-[9px] text-white/40 mt-0.5 truncate">{{ $match->homeTeam->school_name ?? '' }}</p>
                                                </div>
                                                <div class="flex flex-col items-center shrink-0">
                                                    <div class="bg-black/40 px-2.5 py-1 rounded-xl border border-white/10 flex items-center gap-1.5">
                                                        <span class="text-lg font-black text-primary">{{ $match->home_score ?? 0 }}</span>
                                                        <span class="text-white/30 font-bold">-</span>
                                                        <span class="text-lg font-black text-primary">{{ $match->away_score ?? 0 }}</span>
                                                    </div>
                                                </div>
                                                <div class="text-left overflow-hidden">
                                                    <h4 class="text-sm font-bold text-white leading-tight truncate">{{ $match->awayTeam->team_name ?? 'BYE' }}</h4>
                                                    <p class="text-[9px] text-white/40 mt-0.5 truncate">{{ $match->awayTeam->school_name ?? '' }}</p>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        <!-- UPCOMING MATCHES -->
                        @if($upcoming->isNotEmpty())
                            <div class="flex flex-col gap-2 mt-2">
                                <div class="flex items-center gap-2 mb-1">
                                    <svg class="w-4 h-4 text-white/40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    <h2 class="text-sm font-black text-white/50 tracking-widest uppercase">Seterusnya</h2>
                                </div>
                                
                                @foreach($upcoming as $match)
                                    <div class="bg-white/5 border border-white/10 rounded-xl p-3 relative overflow-hidden shrink-0 opacity-75">
                                        <p class="text-[10px] font-bold text-white/50 tracking-widest uppercase mb-1.5">
                                            PADANG {{ $match->field_number ?? '?' }} • {{ $match->group->category->name ?? '' }}
                                        </p>
                                        
                                        @if($match->group && $match->group->game_type === 'obstacle')
                                            <!-- Obstacle Match -->
                                            <div class="flex justify-between items-center">
                                                <div>
                                                    <h4 class="text-base font-bold text-white leading-tight">{{ $match->homeTeam->team_name ?? 'BYE' }}</h4>
                                                    <p class="text-[9px] text-white/40 mt-0.5 uppercase">{{ $match->round_name }}</p>
                                                </div>
                                                <div class="text-right">
                                                    <span class="text-[9px] font-bold text-white/50 uppercase tracking-widest">Menunggu Giliran</span>
                                                </div>
                                            </div>
                                        @else
                                            <!-- Soccer Match -->
                                            <div class="grid grid-cols-[1fr_auto_1fr] gap-2 items-center">
                                                <div class="text-right overflow-hidden">
                                                    <h4 class="text-sm font-bold text-white leading-tight truncate">{{ $match->homeTeam->team_name ?? 'BYE' }}</h4>
                                                    <p class="text-[9px] text-white/40 mt-0.5 truncate">{{ $match->homeTeam->school_name ?? '' }}</p>
                                                </div>
                                                <div class="flex flex-col items-center shrink-0">
                                                    <div class="bg-black/40 px-2 py-1 rounded-lg border border-white/10 flex items-center gap-1.5">
                                                        <span class="text-sm font-black text-white/50">0</span>
                                                        <span class="text-white/30 font-bold">-</span>
                                                        <span class="text-sm font-black text-white/50">0</span>
                                                    </div>
                                                </div>
                                                <div class="text-left overflow-hidden">
                                                    <h4 class="text-sm font-bold text-white leading-tight truncate">{{ $match->awayTeam->team_name ?? 'BYE' }}</h4>
                                                    <p class="text-[9px] text-white/40 mt-0.5 truncate">{{ $match->awayTeam->school_name ?? '' }}</p>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <!-- RIGHT: CAROUSEL -->
    <div class="h-full bg-white/5 border border-white/10 rounded-3xl p-8 flex flex-col relative" 
         x-data="{ 
            activeSlide: 0,
            totalSlides: {{ count($this->slides()) }},
            init() {
                if (this.totalSlides > 1) {
                    setInterval(() => {
                        this.activeSlide = (this.activeSlide + 1) % this.totalSlides;
                    }, 12000); // Rotate every 12s
                }
            }
         }">
        @if(count($this->slides()) === 0)
            <div class="flex-1 flex items-center justify-center">
                <p class="text-white/40 font-bold text-xl tracking-widest uppercase">Tiada Rekod Kedudukan Semasa</p>
            </div>
        @else
            @foreach($this->slides() as $idx => $slide)
                <div x-show="activeSlide === {{ $idx }}" 
                     x-transition:enter="transition ease-out duration-700"
                     x-transition:enter-start="opacity-0 translate-x-12"
                     x-transition:enter-end="opacity-100 translate-x-0"
                     x-transition:leave="transition ease-in duration-500"
                     x-transition:leave-start="opacity-100 translate-x-0"
                     x-transition:leave-end="opacity-0 -translate-x-12"
                     class="absolute inset-8 flex flex-col"
                     style="display: none;">
                    
                    <div class="flex items-center gap-4 mb-8">
                        @if($slide['title'] === 'ISOBOT SOCCER')
                            <div class="bg-primary/20 p-3 rounded-2xl text-primary">
                                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M14 10l-2 1m0 0l-2-1m2 1v2.5M20 7l-2 1m2-1l-2-1m2 1v2.5M14 4l-2-1-2 1M4 7l2-1M4 7l2 1M4 7v2.5M12 21l-2-1m2 1l2-1m-2 1v-2.5M6 18l-2-1v-2.5M18 18l2-1v-2.5"/></svg>
                            </div>
                        @elseif($slide['title'] === 'DRONE SKY SOCCER')
                            <div class="bg-secondary/20 p-3 rounded-2xl text-secondary">
                                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                        @else
                            <div class="bg-emerald-500/20 p-3 rounded-2xl text-emerald-400">
                                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                            </div>
                        @endif
                        
                        <div>
                            <h2 class="text-3xl font-black text-white tracking-widest uppercase">{{ $slide['title'] }}</h2>
                            <p class="text-lg font-bold text-white/50 tracking-widest uppercase mt-1">{{ $slide['subtitle'] }}</p>
                        </div>
                    </div>      
                    
                    @if($slide['type'] === 'obstacle')
                        <!-- OBSTACLE TABLE -->
                        <div class="bg-black/40 rounded-2xl border border-white/10 overflow-hidden shadow-2xl">
                            <table class="w-full text-left">
                                <thead>
                                    <tr class="bg-white/5 border-b border-white/10">
                                        <th class="py-4 px-6 text-white/50 font-bold uppercase tracking-widest w-20">No</th>
                                        <th class="py-4 px-6 text-white/50 font-bold uppercase tracking-widest">Pasukan</th>
                                        <th class="py-4 px-6 text-white/50 font-bold uppercase tracking-widest">Sekolah</th>
                                        <th class="py-4 px-6 text-white/50 font-bold uppercase tracking-widest text-right">Masa Rasmi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($slide['teams'] as $i => $gt)
                                        <tr class="border-b border-white/5 last:border-0">
                                            <td class="py-4 px-6 font-black text-2xl {{ $i < 3 ? 'text-primary' : 'text-white/40' }}">{{ $i + 1 }}</td>
                                            <td class="py-4 px-6 font-bold text-xl text-white">{{ $gt->team->team_name }}</td>
                                            <td class="py-4 px-6 text-white/60 font-medium">{{ $gt->team->school_name }}</td>
                                            <td class="py-4 px-6 text-right">
                                                @php
                                                    if ($gt->goals_for > 0) {
                                                        $tM = floor($gt->goals_for / 60000);
                                                        $tS = floor(($gt->goals_for % 60000) / 1000);
                                                        $tMs = $gt->goals_for % 1000;
                                                        $timeStr = sprintf('%02d:%02d.%03d', $tM, $tS, $tMs);
                                                    } else {
                                                        $timeStr = 'BELUM LARI';
                                                    }
                                                @endphp
                                                <span class="font-black text-2xl {{ $gt->goals_for > 0 ? 'text-emerald-400' : 'text-white/20' }}">{{ $timeStr }}</span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        
                    @elseif($slide['type'] === 'soccer')
                        <!-- SOCCER TABLES -->
                        <div class="grid grid-cols-2 gap-8 h-full">
                            @foreach($slide['groups'] as $g)
                                <div class="bg-black/40 rounded-2xl border border-white/10 overflow-hidden flex flex-col shadow-2xl">
                                    <div class="bg-white/10 px-6 py-4 flex justify-between items-center">
                                        <h3 class="font-black text-xl text-white">KUMPULAN {{ $g->group_letter }}</h3>
                                        <span class="text-xs font-bold text-white/50 uppercase tracking-widest">{{ $g->category->name }}</span>
                                    </div>
                                    <table class="w-full text-left">
                                        <thead>
                                            <tr class="border-b border-white/10">
                                                <th class="py-3 px-4 text-white/50 font-bold uppercase tracking-widest text-xs">Pskn</th>
                                                <th class="py-3 px-4 text-white/50 font-bold uppercase tracking-widest text-xs text-center">P</th>
                                                <th class="py-3 px-4 text-white/50 font-bold uppercase tracking-widest text-xs text-center">M</th>
                                                <th class="py-3 px-4 text-white/50 font-bold uppercase tracking-widest text-xs text-center">S</th>
                                                <th class="py-3 px-4 text-white/50 font-bold uppercase tracking-widest text-xs text-center">K</th>
                                                <th class="py-3 px-4 text-white/50 font-bold uppercase tracking-widest text-xs text-center">+/-</th>
                                                <th class="py-3 px-4 text-primary font-black uppercase tracking-widest text-xs text-center">Pt</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($g->getStandings() as $i => $gt)
                                                <tr class="border-b border-white/5 last:border-0">
                                                    <td class="py-3 px-4 font-bold text-white">{{ $gt->team->team_name }}</td>
                                                    <td class="py-3 px-4 text-center font-medium text-white/70">{{ $gt->played }}</td>
                                                    <td class="py-3 px-4 text-center font-medium text-emerald-400">{{ $gt->won }}</td>
                                                    <td class="py-3 px-4 text-center font-medium text-white/50">{{ $gt->drawn }}</td>
                                                    <td class="py-3 px-4 text-center font-medium text-red-400">{{ $gt->lost }}</td>
                                                    <td class="py-3 px-4 text-center font-medium text-white/70">{{ $gt->goal_difference }}</td>
                                                    <td class="py-3 px-4 text-center font-black text-primary text-lg">{{ $gt->points }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endforeach
        @endif
        
        <!-- Progress Indicators -->
        <div class="absolute bottom-8 left-8 right-8 flex justify-center gap-3 z-10">
            <template x-for="i in totalSlides" :key="i">
                <div class="h-1.5 rounded-full transition-all duration-300"
                     :class="activeSlide === (i-1) ? 'w-12 bg-primary' : 'w-4 bg-white/20'"></div>
            </template>
        </div>
    </div>

    @php $callingMatches = $this->calledMatches(); @endphp
    @if(count($callingMatches) > 0)
        <!-- CALLING OVERLAY -->
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-md transition-all duration-300 p-8 overflow-hidden">
            <div class="w-full h-full max-w-7xl mx-auto flex flex-col justify-center items-center gap-8">
                
                <div class="flex items-center justify-center gap-4 mb-4">
                    <svg class="w-16 h-16 text-yellow-300 animate-bounce" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                    <h1 class="text-6xl font-black text-white tracking-widest uppercase text-center shadow-black drop-shadow-2xl">Panggilan Perlawanan</h1>
                    <svg class="w-16 h-16 text-yellow-300 animate-bounce" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                </div>

                <div class="grid grid-cols-1 {{ count($callingMatches) > 1 ? 'md:grid-cols-2' : '' }} gap-8 w-full max-h-full">
                    @foreach($callingMatches as $match)
                        <div class="bg-gradient-to-b from-red-600 to-red-900 border-4 border-red-500 rounded-3xl p-10 text-center shadow-[0_0_80px_rgba(239,68,68,0.4)] w-full flex flex-col justify-center animate-pulse">
                            <div class="bg-black/40 rounded-2xl p-6 border border-white/10 mb-6">
                                <h2 class="text-4xl font-extrabold text-yellow-300 uppercase tracking-widest mb-1">PADANG {{ $match->field_number ?? '?' }}</h2>
                                <p class="text-lg font-bold text-white/70 uppercase tracking-widest">{{ $match->group->category->name ?? '' }} • {{ $match->round_name }}</p>
                            </div>

                            @if($match->group && $match->group->game_type === 'obstacle')
                                <!-- Obstacle Details -->
                                <div class="mb-4 px-4">
                                    <h3 class="text-3xl font-black text-white leading-tight mb-2 break-words">{{ $match->homeTeam->team_name ?? 'BYE' }}</h3>
                                    <p class="text-lg text-white/80 font-bold uppercase break-words">{{ $match->homeTeam->school_name ?? '' }}</p>
                                </div>
                            @else
                                <!-- Soccer Details -->
                                <div class="flex items-center justify-center gap-4 mt-4">
                                    <div class="flex-1 text-right break-words">
                                        <h3 class="text-3xl lg:text-4xl font-black text-white leading-tight mb-2">{{ $match->homeTeam->team_name ?? 'BYE' }}</h3>
                                        <p class="text-sm lg:text-base text-white/80 font-bold uppercase">{{ $match->homeTeam->school_name ?? '' }}</p>
                                    </div>
                                    <div class="shrink-0 w-16 h-16 lg:w-20 lg:h-20 bg-black/50 rounded-full flex items-center justify-center border-4 border-white/10">
                                        <span class="text-xl lg:text-2xl font-black text-white/50 italic">VS</span>
                                    </div>
                                    <div class="flex-1 text-left break-words">
                                        <h3 class="text-3xl lg:text-4xl font-black text-white leading-tight mb-2">{{ $match->awayTeam->team_name ?? 'BYE' }}</h3>
                                        <p class="text-sm lg:text-base text-white/80 font-bold uppercase">{{ $match->awayTeam->school_name ?? '' }}</p>
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
                
                <p class="mt-4 text-3xl font-black text-yellow-300 uppercase tracking-widest animate-bounce drop-shadow-xl text-center">SILA LAPOR DIRI KE PADANG SEGERA!</p>
            </div>
        </div>
    @endif
</div>
