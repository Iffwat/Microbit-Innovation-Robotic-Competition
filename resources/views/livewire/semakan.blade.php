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
    public string $activeTab = 'search'; // 'search', 'giliran', 'standings', 'knockout'

    #[Url(as: 'q')]
    public string $search = '';

    public ?int $viewTeamId = null;
    
    public string $selectedFilterId = '';
    public ?int $selectedKnockoutCategoryId = null;

    // Giliran / Field Queue Filters
    public string $selectedFieldFilter = '';
    public string $queueSearch = '';

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
        $this->activeTab = 'search';
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
        
        $matches = TournamentMatch::with(['homeTeam', 'awayTeam', 'group', 'category'])
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
    public function availableFields()
    {
        return TournamentMatch::whereNotNull('field_number')
            ->select('field_number')
            ->distinct()
            ->orderByRaw('LENGTH(field_number)')
            ->orderBy('field_number')
            ->pluck('field_number');
    }

    #[Computed]
    public function activeLiveCalls()
    {
        $calls = \Illuminate\Support\Facades\Cache::get('live_tv_calls', []);
        $activeCallMatchIds = [];
        foreach ($calls as $mId => $cData) {
            if (isset($cData['expires_at']) && $cData['expires_at'] > now()->timestamp) {
                $activeCallMatchIds[] = (int)$mId;
            }
        }
        return $activeCallMatchIds;
    }

    public function getFieldQueueData(string $field)
    {
        $sortBy = \Illuminate\Support\Facades\Cache::get('field_sort_' . $field, str_contains($field, 'Sky Soccer') ? 'interleaved_round' : 'default');

        $matches = TournamentMatch::with(['homeTeam', 'awayTeam', 'category', 'group'])
            ->where('field_number', $field)
            ->get();

        if ($sortBy === 'interleaved_round') {
            $sorted = $matches->sort(function ($a, $b) {
                $statusOrder = ['in_progress' => 1, 'scheduled' => 2, 'completed' => 3, 'walkover' => 4, 'bye' => 4];
                $sA = $statusOrder[$a->status] ?? 5;
                $sB = $statusOrder[$b->status] ?? 5;
                if ($sA !== $sB) return $sA <=> $sB;

                preg_match('/P(\d+)/i', (string)$a->round_name, $matchA);
                preg_match('/P(\d+)/i', (string)$b->round_name, $matchB);
                $rA = isset($matchA[1]) ? (int)$matchA[1] : 999;
                $rB = isset($matchB[1]) ? (int)$matchB[1] : 999;
                if ($rA !== $rB) return $rA <=> $rB;

                $catA = $a->category->sort_order ?? $a->category_id;
                $catB = $b->category->sort_order ?? $b->category_id;
                if ($catA !== $catB) return $catA <=> $catB;

                $grpA = $a->group->group_letter ?? $a->group->group_name ?? '';
                $grpB = $b->group->group_letter ?? $b->group->group_name ?? '';
                if ($grpA !== $grpB) return strcmp($grpA, $grpB);

                if ($a->scheduled_time && $b->scheduled_time) {
                    return $a->scheduled_time <=> $b->scheduled_time;
                }
                return $a->id <=> $b->id;
            })->values();
        } elseif ($sortBy === 'group_asc') {
            $sorted = $matches->sort(function ($a, $b) {
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

                if ($a->scheduled_time && $b->scheduled_time) {
                    return $a->scheduled_time <=> $b->scheduled_time;
                }
                return $a->id <=> $b->id;
            })->values();
        } elseif ($sortBy === 'category_asc') {
            $sorted = $matches->sort(function ($a, $b) {
                $statusOrder = ['in_progress' => 1, 'scheduled' => 2, 'completed' => 3, 'walkover' => 4, 'bye' => 4];
                $sA = $statusOrder[$a->status] ?? 5;
                $sB = $statusOrder[$b->status] ?? 5;
                if ($sA !== $sB) return $sA <=> $sB;

                $catA = $a->category->sort_order ?? $a->category_id;
                $catB = $b->category->sort_order ?? $b->category_id;
                if ($catA !== $catB) return $catA <=> $catB;

                preg_match('/P(\d+)/i', (string)$a->round_name, $matchA);
                preg_match('/P(\d+)/i', (string)$b->round_name, $matchB);
                $rA = isset($matchA[1]) ? (int)$matchA[1] : 999;
                $rB = isset($matchB[1]) ? (int)$matchB[1] : 999;
                if ($rA !== $rB) return $rA <=> $rB;

                if ($a->scheduled_time && $b->scheduled_time) {
                    return $a->scheduled_time <=> $b->scheduled_time;
                }
                return $a->id <=> $b->id;
            })->values();
        } else {
            $sorted = $matches->sort(function ($a, $b) {
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

        $activeMatch = $sorted->firstWhere('status', 'in_progress');
        $scheduledMatches = $sorted->where('status', 'scheduled')->values();
        $completedCount = $sorted->where('status', 'completed')->count();

        return [
            'field' => $field,
            'active' => $activeMatch,
            'upcoming' => $scheduledMatches,
            'completed_count' => $completedCount,
            'total_pending' => $scheduledMatches->count() + ($activeMatch ? 1 : 0),
        ];
    }

    public function getTeamTurnStatus(int $teamId)
    {
        // 1. Is team involved in an active Live TV call?
        $activeCallIds = $this->activeLiveCalls;
        if (!empty($activeCallIds)) {
            $calledMatch = TournamentMatch::with(['homeTeam', 'awayTeam', 'category'])
                ->whereIn('id', $activeCallIds)
                ->where(function ($q) use ($teamId) {
                    $q->where('home_team_id', $teamId)->orWhere('away_team_id', $teamId);
                })
                ->where('status', 'scheduled')
                ->first();

            if ($calledMatch) {
                return [
                    'state' => 'called',
                    'match' => $calledMatch,
                    'field' => $calledMatch->field_number,
                    'opponent' => $calledMatch->home_team_id === $teamId ? $calledMatch->awayTeam : $calledMatch->homeTeam,
                    'title' => __('PANGGILAN LAPOR DIRI!'),
                    'message' => __('Pasukan anda sedang dipanggil ke :field!', ['field' => $calledMatch->field_number]),
                    'submessage' => __('Sila lapor diri ke meja pengadil padang dengan segera.')
                ];
            }
        }

        // 2. Is team currently playing in an in_progress match?
        $inProgressMatch = TournamentMatch::with(['homeTeam', 'awayTeam', 'category', 'group'])
            ->where(function ($q) use ($teamId) {
                $q->where('home_team_id', $teamId)->orWhere('away_team_id', $teamId);
            })
            ->where('status', 'in_progress')
            ->first();

        if ($inProgressMatch) {
            $opp = $inProgressMatch->home_team_id === $teamId ? $inProgressMatch->awayTeam : $inProgressMatch->homeTeam;
            return [
                'state' => 'in_progress',
                'match' => $inProgressMatch,
                'field' => $inProgressMatch->field_number,
                'opponent' => $opp,
                'title' => __('SEDANG BERLANGSUNG SEKARANG'),
                'message' => __('Perlawanan anda sedang berlangsung di :field', ['field' => $inProgressMatch->field_number]),
                'submessage' => $opp ? __('Lawan:') . ' ' . $opp->team_name : ''
            ];
        }

        // 3. Find the next scheduled match for this team
        $nextScheduled = TournamentMatch::with(['homeTeam', 'awayTeam', 'category', 'group'])
            ->where(function ($q) use ($teamId) {
                $q->where('home_team_id', $teamId)->orWhere('away_team_id', $teamId);
            })
            ->where('status', 'scheduled')
            ->orderBy('scheduled_time')
            ->orderBy('id')
            ->first();

        if (!$nextScheduled) {
            $completedCount = TournamentMatch::where(function ($q) use ($teamId) {
                $q->where('home_team_id', $teamId)->orWhere('away_team_id', $teamId);
            })->where('status', 'completed')->count();

            if ($completedCount > 0) {
                return [
                    'state' => 'completed',
                    'title' => __('SEMUA PERLAWANAN SELESAI'),
                    'message' => __('Semua perlawanan pasukan anda telah selesai dimainkan.'),
                    'submessage' => __('Sila semak kedudukan terkini dalam tab Kedudukan atau tunggu carta Kalah Mati.')
                ];
            }

            return [
                'state' => 'none',
                'title' => __('TIADA PERLAWANAN DIJADUALKAN'),
                'message' => __('Jadual perlawanan belum dikeluarkan atau tiada giliran menunggu.'),
                'submessage' => ''
            ];
        }

        // Calculate queue position on its assigned field
        $field = $nextScheduled->field_number;
        $queueData = $field ? $this->getFieldQueueData($field) : null;

        $turnPosition = null;
        $matchesAhead = null;
        $activeOnField = null;

        if ($queueData) {
            $activeOnField = $queueData['active'];
            $upcomingList = $queueData['upcoming'];
            
            $matchIndex = $upcomingList->search(function ($m) use ($nextScheduled) {
                return $m->id === $nextScheduled->id;
            });

            if ($matchIndex !== false) {
                $turnPosition = $matchIndex + 1;
                $matchesAhead = $matchIndex;
            }
        }

        $opponent = $nextScheduled->home_team_id === $teamId ? $nextScheduled->awayTeam : $nextScheduled->homeTeam;

        if ($turnPosition === 1) {
            return [
                'state' => 'next_up',
                'match' => $nextScheduled,
                'field' => $field,
                'opponent' => $opponent,
                'turn_position' => 1,
                'matches_ahead' => 0,
                'active_on_field' => $activeOnField,
                'title' => __('🔥 GILIRAN SETERUSNYA (NEXT MATCH)!'),
                'message' => __('Perlawanan anda adalah giliran berikutnya di :field.', ['field' => $field ?? __('Padang')]),
                'submessage' => __('Sila bersedia di tepi padang') . ($nextScheduled->scheduled_time ? ' (' . __('Anggaran:') . ' ' . $nextScheduled->scheduled_time->format('h:i A') . ')' : '')
            ];
        }

        return [
            'state' => 'queued',
            'match' => $nextScheduled,
            'field' => $field,
            'opponent' => $opponent,
            'turn_position' => $turnPosition,
            'matches_ahead' => $matchesAhead,
            'active_on_field' => $activeOnField,
            'title' => $turnPosition ? __('Giliran ke-:pos di :field', ['pos' => $turnPosition, 'field' => $field]) : __('⏳ PERLAWANAN MENUNGGU'),
            'message' => ($matchesAhead !== null && $matchesAhead > 0) 
                ? ($matchesAhead === 1 ? __('Lagi 1 perlawanan sebelum giliran anda') : __('Lagi :count perlawanan sebelum giliran anda', ['count' => $matchesAhead]))
                : __('Menunggu giliran di padang'),
            'submessage' => $nextScheduled->scheduled_time ? __('Anggaran Masa:') . ' ' . $nextScheduled->scheduled_time->format('h:i A') : ''
        ];
    }

    public function getMatchTurnBadge(TournamentMatch $match, int $teamId)
    {
        if ($match->status === 'completed') {
            return ['type' => 'completed', 'label' => '✓ ' . __('Selesai'), 'class' => 'bg-emerald-100 text-emerald-800 border border-emerald-200'];
        }
        if ($match->status === 'in_progress') {
            return ['type' => 'in_progress', 'label' => '🔴 ' . __('Sedang Berlangsung'), 'class' => 'bg-red-500 text-white animate-pulse shadow-md shadow-red-500/30'];
        }

        if (in_array($match->id, $this->activeLiveCalls)) {
            return ['type' => 'calling', 'label' => '🚨 ' . __('PANGGILAN LAPOR DIRI!'), 'class' => 'bg-amber-400 text-black font-black animate-bounce'];
        }

        if ($match->field_number) {
            $queueData = $this->getFieldQueueData($match->field_number);
            $idx = $queueData['upcoming']->search(fn($m) => $m->id === $match->id);
            if ($idx !== false) {
                if ($idx === 0) {
                    return ['type' => 'next', 'label' => '🔥 ' . __('Giliran Seterusnya (Next Up)'), 'class' => 'bg-amber-500 text-white font-black animate-pulse shadow-sm shadow-amber-500/30'];
                } else {
                    $turnNum = $idx + 1;
                    return ['type' => 'queue', 'label' => '⏳ ' . __('Giliran ke-:pos', ['pos' => $turnNum]) . ' (' . ($idx === 1 ? __('Lagi 1 perlawanan') : __('Lagi :count perlawanan', ['count' => $idx])) . ')', 'class' => 'bg-blue-100 text-blue-800 border border-blue-200'];
                }
            }
        }

        return ['type' => 'scheduled', 'label' => '⏳ ' . __('Menunggu'), 'class' => 'bg-base-200 text-base-content/70'];
    }

    #[Computed]
    public function allFieldQueues()
    {
        $fields = $this->availableFields;
        if ($this->selectedFieldFilter) {
            $fields = $fields->filter(fn($f) => (string)$f === (string)$this->selectedFieldFilter);
        }

        $queues = [];
        $search = strtolower(trim($this->queueSearch));

        foreach ($fields as $field) {
            $data = $this->getFieldQueueData($field);
            if ($search !== '') {
                $fieldMatches = str_contains(strtolower($field), $search);
                
                $activeMatchesSearch = false;
                if ($data['active']) {
                    $h = strtolower($data['active']->homeTeam->team_name ?? '');
                    $a = strtolower($data['active']->awayTeam->team_name ?? '');
                    $hs = strtolower($data['active']->homeTeam->school_name ?? '');
                    $as = strtolower($data['active']->awayTeam->school_name ?? '');
                    if (str_contains($h, $search) || str_contains($a, $search) || str_contains($hs, $search) || str_contains($as, $search)) {
                        $activeMatchesSearch = true;
                    }
                }

                $upcomingMatchesSearch = $data['upcoming']->filter(function ($m) use ($search) {
                    $h = strtolower($m->homeTeam->team_name ?? '');
                    $a = strtolower($m->awayTeam->team_name ?? '');
                    $hs = strtolower($m->homeTeam->school_name ?? '');
                    $as = strtolower($m->awayTeam->school_name ?? '');
                    return str_contains($h, $search) || str_contains($a, $search) || str_contains($hs, $search) || str_contains($as, $search);
                });

                if (!$fieldMatches && !$activeMatchesSearch && $upcomingMatchesSearch->isEmpty()) {
                    continue;
                }
            }
            $queues[] = $data;
        }

        return collect($queues);
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

<div class="min-h-screen bg-base-200 pb-24" wire:poll.10s>
    <!-- Top Nav / Header -->
    <div class="bg-primary text-primary-content sticky top-0 z-40 shadow-md">
        <div class="max-w-3xl mx-auto px-4 py-3 flex items-center justify-between">
            <div>
                <h1 class="text-xl font-black italic tracking-wider uppercase flex items-center gap-2">
                    <svg class="w-6 h-6 text-yellow-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                    {{ __('Hab Peserta') }}
                </h1>
                <p class="text-[10px] font-bold text-primary-content/70 tracking-widest uppercase">{{ __('Portal Rasmi Kejohanan') }}</p>
            </div>
            
            <div class="flex items-center gap-2">
                {{-- Language Toggle --}}
                <div class="join border border-white/20 rounded-lg overflow-hidden bg-black/20 text-white">
                    <a href="{{ route('lang.switch', 'ms') }}"
                       class="join-item px-2.5 py-1 text-xs font-bold transition-colors {{ app()->getLocale() === 'ms' ? 'bg-white text-primary' : 'text-white/70 hover:text-white' }}">MS</a>
                    <a href="{{ route('lang.switch', 'en') }}"
                       class="join-item px-2.5 py-1 text-xs font-bold transition-colors {{ app()->getLocale() === 'en' ? 'bg-white text-primary' : 'text-white/70 hover:text-white' }}">EN</a>
                </div>
                <a href="/" class="btn btn-sm btn-ghost btn-circle text-white">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" /></svg>
                </a>
            </div>
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
                    <h2 class="text-3xl font-black text-base-content leading-tight">{{ __('Semak Status & Jadual Pasukan Anda.') }}</h2>
                    <p class="text-sm text-base-content/60">{{ __('Taip nama pasukan atau nama sekolah untuk melihat maklumat terperinci pendaftaran dan jadual penuh.') }}</p>
                </div>

                <div class="bg-white p-2 rounded-2xl shadow-lg border border-base-200 flex items-center gap-2 sticky top-20 z-30 animate-slide-up">
                    <div class="pl-3 text-base-content/40">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                    </div>
                    <input wire:model.live.debounce.500ms="search" type="text" placeholder="{{ __('Contoh: SK Gombak...') }}" class="flex-1 bg-transparent py-3 text-base font-medium focus:outline-none w-full" autofocus>
                    @if(strlen($search) > 0)
                        <button wire:click="$set('search', '')" class="p-2 text-base-content/40 hover:text-base-content rounded-xl transition-colors">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    @endif
                </div>

                <div class="mt-8 space-y-4">
                    <div wire:loading class="w-full text-center py-8">
                        <span class="loading loading-spinner text-primary"></span>
                        <p class="text-sm text-base-content/50 mt-2 font-medium">{{ __('Mencari rekod...') }}</p>
                    </div>

                    <div wire:loading.remove>
                        @if(strlen(trim($search)) >= 3)
                            @if($this->searchResults->isEmpty())
                                <div class="text-center py-12 bg-white rounded-3xl border border-base-200 border-dashed">
                                    <p class="text-lg font-bold text-base-content/40">{{ __('Tiada pasukan dijumpai.') }}</p>
                                </div>
                            @else
                                <p class="text-xs font-bold text-base-content/50 uppercase tracking-widest px-2 mb-3">{{ __('Hasil Carian') }} ({{ $this->searchResults->count() }})</p>
                                <div class="grid grid-cols-1 gap-3">
                                    @foreach($this->searchResults as $team)
                                        <button wire:click="viewTeam({{ $team->id }})" class="w-full text-left bg-white border-2 border-base-200 hover:border-primary p-4 rounded-2xl shadow-sm transition-all flex items-center justify-between group">
                                            <div>
                                                <h3 class="font-bold text-base-content text-lg group-hover:text-primary transition-colors">{{ $team->team_name }}</h3>
                                                <p class="text-xs text-base-content/60 font-medium">🏫 {{ $team->school_name }}</p>
                                            </div>
                                            <div class="shrink-0 flex flex-col items-end gap-2">
                                                @if($team->status === 'checked_in')
                                                    <span class="bg-emerald-100 text-emerald-700 text-[10px] font-black px-2 py-1 rounded-md uppercase tracking-wider">{{ __('Hadir') }}</span>
                                                @else
                                                    <span class="bg-base-200 text-base-content/50 text-[10px] font-black px-2 py-1 rounded-md uppercase tracking-wider">{{ __('Berdaftar') }}</span>
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
                            {{ __('Kembali ke carian') }}
                        </button>

                        <!-- ID Card -->
                        <div class="bg-gradient-to-br from-primary to-primary-focus p-6 rounded-3xl shadow-xl text-white relative overflow-hidden">
                            <div class="absolute -right-4 -bottom-4 opacity-10">
                                <svg class="w-48 h-48" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2L2 22h20L12 2z"/></svg>
                            </div>
                            <div class="relative z-10">
                                <div class="inline-block bg-white/20 px-3 py-1 rounded-full text-xs font-bold tracking-widest uppercase mb-3 backdrop-blur-sm border border-white/20">
                                    {{ $details['team']->category->name ?? __('Kategori Umum') }}
                                </div>
                                <h2 class="text-3xl font-black leading-tight">{{ $details['team']->team_name }}</h2>
                                <p class="text-white/80 font-medium mt-1">🏫 {{ $details['team']->school_name }}</p>
                                
                                <div class="mt-6 flex flex-wrap gap-3">
                                    @if($details['team']->status === 'checked_in')
                                        <div class="bg-emerald-400 text-emerald-950 text-xs font-black px-3 py-1.5 rounded-lg flex items-center gap-1.5 shadow-lg shadow-emerald-500/20">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                            {{ __('DISAHKAN HADIR') }}
                                        </div>
                                    @else
                                        <div class="bg-white/20 text-white border border-white/30 text-xs font-black px-3 py-1.5 rounded-lg flex items-center gap-1.5 backdrop-blur-md">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            {{ __('BELUM CHECK-IN') }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Personal Turn Hero Card -->
                        @php $turnStatus = $this->getTeamTurnStatus($this->viewTeamId); @endphp
                        @if($turnStatus)
                            <div class="mt-5 animate-slide-up">
                                @if($turnStatus['state'] === 'called')
                                    <div class="bg-gradient-to-r from-amber-500 via-yellow-400 to-amber-500 text-black p-5 rounded-3xl shadow-xl border-2 border-yellow-300 animate-pulse">
                                        <div class="flex items-center gap-3 mb-2">
                                            <span class="text-2xl">🚨</span>
                                            <h3 class="text-lg font-black tracking-wider uppercase">{{ $turnStatus['title'] }}</h3>
                                        </div>
                                        <p class="font-black text-base">{{ $turnStatus['message'] }}</p>
                                        <p class="text-sm font-bold opacity-90 mt-1">{{ $turnStatus['submessage'] }}</p>
                                    </div>
                                @elseif($turnStatus['state'] === 'in_progress')
                                    <div class="bg-gradient-to-r from-red-600 via-rose-600 to-red-700 text-white p-5 rounded-3xl shadow-xl border-2 border-red-400 shadow-red-500/20">
                                        <div class="flex items-center justify-between mb-2">
                                            <div class="flex items-center gap-2">
                                                <span class="w-3 h-3 rounded-full bg-white animate-ping"></span>
                                                <h3 class="text-xs font-black tracking-widest uppercase bg-black/30 px-2.5 py-1 rounded-full">{{ __('LIVE DI PADANG') }}</h3>
                                            </div>
                                            <span class="text-xs font-black bg-white text-red-600 px-3 py-1 rounded-full uppercase">{{ $turnStatus['field'] }}</span>
                                        </div>
                                        <h4 class="text-xl font-black mt-1">{{ $turnStatus['title'] }}</h4>
                                        <p class="text-sm font-bold text-white/90 mt-1">{{ $turnStatus['message'] }}</p>
                                        @if($turnStatus['submessage'])
                                            <p class="text-xs font-semibold text-white/80 mt-1">{{ $turnStatus['submessage'] }}</p>
                                        @endif
                                    </div>
                                @elseif($turnStatus['state'] === 'next_up')
                                    <div class="bg-gradient-to-r from-amber-500 to-orange-500 text-white p-5 rounded-3xl shadow-xl border-2 border-amber-300 shadow-orange-500/20">
                                        <div class="flex items-center justify-between mb-2">
                                            <span class="text-xs font-black bg-black/20 px-3 py-1 rounded-full uppercase tracking-wider flex items-center gap-1.5">
                                                <span class="w-2 h-2 rounded-full bg-yellow-300 animate-ping"></span>
                                                {{ __('GILIRAN SETERUSNYA') }}
                                            </span>
                                            <span class="text-xs font-black bg-white text-amber-600 px-3 py-1 rounded-full uppercase">{{ $turnStatus['field'] }}</span>
                                        </div>
                                        <h4 class="text-xl font-black">{{ $turnStatus['title'] }}</h4>
                                        <p class="text-sm font-bold text-white/95 mt-1">{{ $turnStatus['message'] }}</p>
                                        <p class="text-xs font-semibold text-yellow-100 mt-2 flex items-center gap-1">
                                            <span>⚠️</span>
                                            <span>{{ $turnStatus['submessage'] }}</span>
                                        </p>
                                        @if($turnStatus['active_on_field'])
                                            <div class="mt-3 pt-3 border-t border-white/20 text-xs font-medium text-white/90 flex items-center justify-between flex-wrap gap-1">
                                                <span>{{ __('Sedang berlangsung di padang:') }}</span>
                                                <span class="font-bold">{{ optional($turnStatus['active_on_field']->homeTeam)->team_name }} vs {{ optional($turnStatus['active_on_field']->awayTeam)->team_name }} ({{ $turnStatus['active_on_field']->home_score ?? 0 }} - {{ $turnStatus['active_on_field']->away_score ?? 0 }})</span>
                                            </div>
                                        @endif
                                    </div>
                                @elseif($turnStatus['state'] === 'queued')
                                    <div class="bg-white border-2 border-blue-200 p-5 rounded-3xl shadow-sm">
                                        <div class="flex items-center justify-between mb-3">
                                            <span class="text-xs font-black bg-blue-50 text-blue-700 px-3 py-1 rounded-full uppercase tracking-wider border border-blue-200">
                                                {{ $turnStatus['title'] }}
                                            </span>
                                            @if($turnStatus['submessage'])
                                                <span class="text-xs font-bold text-base-content/60 bg-base-100 px-2.5 py-1 rounded-lg">
                                                    {{ $turnStatus['submessage'] }}
                                                </span>
                                            @endif
                                        </div>
                                        <div class="flex items-baseline gap-2">
                                            <span class="text-3xl font-black text-primary">{{ $turnStatus['matches_ahead'] }}</span>
                                            <span class="text-sm font-bold text-base-content/80">{{ __('perlawanan sebelum giliran pasukan anda.') }}</span>
                                        </div>
                                        @if($turnStatus['opponent'])
                                            <p class="text-xs font-semibold text-base-content/60 mt-1">
                                                {{ __('Perlawanan seterusnya menentang:') }} <strong class="text-base-content">{{ $turnStatus['opponent']->team_name }}</strong>
                                            </p>
                                        @endif
                                        @if($turnStatus['active_on_field'])
                                            <div class="mt-3 pt-3 border-t border-base-100 text-xs text-base-content/60 flex items-center justify-between flex-wrap gap-1">
                                                <span>{{ __('Sedang aktif di padang:') }}</span>
                                                <span class="font-bold text-primary">{{ optional($turnStatus['active_on_field']->homeTeam)->team_name }} vs {{ optional($turnStatus['active_on_field']->awayTeam)->team_name }} ({{ $turnStatus['active_on_field']->home_score ?? 0 }} - {{ $turnStatus['active_on_field']->away_score ?? 0 }})</span>
                                            </div>
                                        @endif
                                    </div>
                                @elseif($turnStatus['state'] === 'completed')
                                    <div class="bg-emerald-50 border border-emerald-200 p-4 rounded-3xl text-emerald-800 flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-2xl bg-emerald-100 flex items-center justify-center text-emerald-600 text-lg font-black shrink-0">
                                            ✓
                                        </div>
                                        <div>
                                            <h4 class="font-black text-sm">{{ $turnStatus['title'] }}</h4>
                                            <p class="text-xs font-medium text-emerald-700 mt-0.5">{{ $turnStatus['message'] }}</p>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @endif

                        <!-- Schedule List -->
                        <div class="mt-8">
                            <h3 class="text-sm font-black text-base-content/60 uppercase tracking-widest mb-4 flex items-center gap-2">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                {{ __('Jadual Perlawanan Pasukan') }}
                            </h3>
                            
                            @if($details['matches']->isEmpty())
                                <div class="bg-white border-2 border-base-200 border-dashed rounded-3xl p-8 text-center">
                                    <p class="text-base-content/40 font-bold">{{ __('Jadual perlawanan belum dikeluarkan.') }}</p>
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
                                            $turnBadge = $this->getMatchTurnBadge($match, $this->viewTeamId);
                                        @endphp
                                        
                                        <div class="bg-white border-2 {{ $match->status === 'in_progress' ? 'border-primary shadow-lg shadow-primary/10' : 'border-base-200' }} rounded-2xl p-5 relative overflow-hidden">
                                            @if($match->status === 'in_progress')
                                                <div class="absolute top-0 right-0 bg-primary text-white text-[9px] font-black uppercase tracking-widest px-3 py-1 rounded-bl-xl animate-pulse">
                                                    {{ __('SEDANG BERLANGSUNG') }}
                                                </div>
                                            @endif
                                            
                                            <div class="flex justify-between items-start mb-3 gap-2 flex-wrap">
                                                <div class="flex items-center gap-2 flex-wrap">
                                                    <span class="inline-block bg-base-200 text-base-content/60 text-[10px] font-bold px-2 py-0.5 rounded uppercase tracking-wider">
                                                        {{ $match->stage === 'group' ? ($match->group->group_name ?? __('Kumpulan')) : $match->round_name }}
                                                    </span>
                                                    <span class="text-[10px] font-bold px-2.5 py-0.5 rounded-full {{ $turnBadge['class'] }}">
                                                        {{ $turnBadge['label'] }}
                                                    </span>
                                                </div>
                                                
                                                @if($match->field_number)
                                                    <div class="text-xs font-bold text-secondary">
                                                        {{ is_numeric($match->field_number) ? __('Padang') . ' ' . $match->field_number : $match->field_number }}
                                                    </div>
                                                @endif
                                            </div>

                                            <div class="flex items-center gap-4">
                                                <!-- Opponent Info -->
                                                <div class="flex-1">
                                                    <p class="text-[10px] font-bold text-base-content/40 uppercase">{{ __('Lawan') }}</p>
                                                    <h4 class="font-bold text-base-content text-lg leading-tight">{{ $opponent->team_name ?? 'TBD' }}</h4>
                                                </div>
                                                
                                                <!-- Score/Status -->
                                                <div class="shrink-0 text-center">
                                                    @if($match->status === 'completed')
                                                        @if($match->group && $match->group->game_type === 'obstacle')
                                                            <!-- Obstacle Time Result -->
                                                            <div class="bg-base-200 px-3 py-1 rounded-lg">
                                                                <span class="text-xs font-bold text-base-content/50 block">{{ __('Masa Direkod') }}</span>
                                                                <span class="text-lg font-black text-emerald-600">{{ $match->formatted_obstacle_time }}</span>
                                                            </div>
                                                        @else
                                                            <div class="bg-base-100 border border-base-200 px-4 py-2 rounded-xl flex items-center justify-center gap-3">
                                                                <span class="text-xl font-black {{ $isWin ? 'text-emerald-600' : ($isLoss ? 'text-red-500' : 'text-base-content') }}">{{ $myScore ?? 0 }}</span>
                                                                <span class="text-xs font-bold text-base-content/30">-</span>
                                                                <span class="text-xl font-black {{ $isLoss ? 'text-emerald-600' : ($isWin ? 'text-red-500' : 'text-base-content') }}">{{ $oppScore ?? 0 }}</span>
                                                            </div>
                                                            @if($isWin) <p class="text-[10px] font-bold text-emerald-600 uppercase mt-1">{{ __('MENANG') }}</p>
                                                            @elseif($isLoss) <p class="text-[10px] font-bold text-red-500 uppercase mt-1">{{ __('KALAH') }}</p>
                                                            @else <p class="text-[10px] font-bold text-base-content/50 uppercase mt-1">{{ __('SERI') }}</p>
                                                            @endif
                                                        @endif
                                                    @else
                                                        <span class="text-xs font-bold text-base-content/40 uppercase bg-base-200 px-3 py-1.5 rounded-lg block">{{ __('Belum Mula') }}</span>
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
        {{-- TAB 2: GILIRAN PADANG (LIVE FIELD QUEUES)      --}}
        {{-- ============================================== --}}
        @if($activeTab === 'giliran')
            <div class="animate-fade-in space-y-6">
                <div>
                    <div class="flex items-center justify-between gap-2">
                        <h2 class="text-2xl font-black text-base-content flex items-center gap-2">
                            <span class="relative flex h-3 w-3">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-3 w-3 bg-red-500"></span>
                            </span>
                            {{ __('Giliran Padang') }}
                        </h2>
                        <span class="text-[10px] font-black bg-primary/10 text-primary px-2.5 py-1 rounded-full uppercase tracking-wider">
                            ⚡ {{ __('Auto-Kemaskini') }}
                        </span>
                    </div>
                    <p class="text-xs text-base-content/60 mt-1">{{ __('Pantau giliran perlawanan semasa mengikut padang secara langsung.') }}</p>
                </div>

                {{-- Carian Pasukan dalam Giliran --}}
                <div class="bg-white p-2 rounded-2xl shadow-sm border border-base-200 flex items-center gap-2">
                    <div class="pl-3 text-base-content/40">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                    </div>
                    <input wire:model.live.debounce.300ms="queueSearch" type="text" placeholder="{{ __('Cari nama pasukan dalam senarai giliran...') }}" class="flex-1 bg-transparent py-2.5 text-sm font-medium focus:outline-none w-full">
                    @if(strlen($queueSearch) > 0)
                        <button wire:click="$set('queueSearch', '')" class="p-2 text-base-content/40 hover:text-base-content rounded-xl transition-colors">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    @endif
                </div>

                {{-- Field Filter Pills --}}
                <div class="overflow-x-auto pb-2 -mx-4 px-4 hide-scrollbar">
                    <div class="flex gap-2 w-max">
                        <button wire:click="$set('selectedFieldFilter', '')" 
                            class="px-4 py-2 rounded-xl text-xs font-bold border-2 transition-all whitespace-nowrap {{ $selectedFieldFilter === '' ? 'bg-primary border-primary text-white shadow-md shadow-primary/20' : 'bg-white border-base-200 text-base-content/70 hover:border-primary/50' }}">
                            🌐 {{ __('Semua Padang') }}
                        </button>
                        @foreach($this->availableFields as $f)
                            <button wire:click="$set('selectedFieldFilter', '{{ $f }}')" 
                                class="px-4 py-2 rounded-xl text-xs font-bold border-2 transition-all whitespace-nowrap {{ $selectedFieldFilter == $f ? 'bg-primary border-primary text-white shadow-md shadow-primary/20' : 'bg-white border-base-200 text-base-content/70 hover:border-primary/50' }}">
                                @if(is_numeric($f))
                                    📍 {{ __('Padang') }} {{ $f }}
                                @elseif(str_contains($f, 'Sky Soccer'))
                                    🚁 {{ $f }}
                                @elseif(str_contains($f, 'Course'))
                                    🏁 {{ $f }}
                                @else
                                    📍 {{ $f }}
                                @endif
                            </button>
                        @endforeach
                    </div>
                </div>

                {{-- Fields Live Queue Cards --}}
                @php $fieldQueues = $this->allFieldQueues; @endphp
                @if($fieldQueues->isEmpty())
                    <div class="bg-white border-2 border-base-200 border-dashed rounded-3xl p-10 text-center">
                        <p class="text-base-content/50 font-bold">{{ __('Tiada padang atau perlawanan dijumpai.') }}</p>
                    </div>
                @else
                    <div class="space-y-6">
                        @foreach($fieldQueues as $qData)
                            @php
                                $field = $qData['field'];
                                $active = $qData['active'];
                                $upcoming = $qData['upcoming'];
                                $isSky = str_contains($field, 'Sky Soccer');
                            @endphp
                            <div class="bg-white rounded-3xl border border-base-200 shadow-sm overflow-hidden">
                                {{-- Header Padang --}}
                                <div class="bg-gradient-to-r from-base-100 via-base-100 to-base-200/50 px-5 py-4 border-b border-base-200 flex items-center justify-between flex-wrap gap-2">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-xl bg-primary text-white flex items-center justify-center font-black text-sm shadow-md shadow-primary/20">
                                            @if(is_numeric($field)) {{ $field }}
                                            @elseif($isSky) 🚁
                                            @else 📍
                                            @endif
                                        </div>
                                        <div>
                                            <h3 class="font-black text-base-content text-base">
                                                {{ is_numeric($field) ? __('Padang') . ' ' . $field : $field }}
                                            </h3>
                                            <p class="text-[10px] font-bold text-primary tracking-wider uppercase">
                                                {{ $qData['total_pending'] }} {{ __('Perlawanan Berbaki') }} &middot; {{ $qData['completed_count'] }} {{ __('Selesai') }}
                                            </p>
                                        </div>
                                    </div>
                                    @if($isSky)
                                        <span class="text-[10px] font-bold bg-violet-100 text-violet-700 px-2.5 py-1 rounded-lg border border-violet-200">
                                            🔄 {{ __('Selang-Seli (U12 ➔ U15)') }}
                                        </span>
                                    @endif
                                </div>

                                <div class="p-5 space-y-4">
                                    {{-- 1. SEDANG BERLANGSUNG (LIVE NOW) --}}
                                    <div>
                                        <div class="flex items-center gap-2 mb-2">
                                            <span class="w-2 h-2 rounded-full bg-red-500 animate-ping"></span>
                                            <span class="text-[10px] font-black text-red-600 uppercase tracking-widest">{{ __('SEDANG BERLANGSUNG (LIVE)') }}</span>
                                        </div>
                                        @if($active)
                                            @php
                                                $qSearch = strtolower(trim($this->queueSearch));
                                                $isHighlighted = $qSearch && (
                                                    str_contains(strtolower($active->homeTeam->team_name ?? ''), $qSearch) || 
                                                    str_contains(strtolower($active->awayTeam->team_name ?? ''), $qSearch)
                                                );
                                            @endphp
                                            <div class="p-4 rounded-2xl border-2 {{ $isHighlighted ? 'border-primary ring-4 ring-primary/20 bg-primary/5' : 'border-red-200 bg-red-50/40' }} relative overflow-hidden">
                                                <div class="flex justify-between items-center text-[10px] font-bold text-base-content/60 mb-2">
                                                    <span>{{ $active->category->name ?? '' }} &middot; {{ $active->round_name }}</span>
                                                    <span class="bg-red-500 text-white font-black px-2 py-0.5 rounded uppercase text-[9px] animate-pulse">LIVE</span>
                                                </div>
                                                <div class="grid grid-cols-[1fr_auto_1fr] items-center gap-2">
                                                    <div class="text-right">
                                                        <h4 class="font-extrabold text-sm text-base-content truncate">{{ $active->homeTeam->team_name ?? 'BYE' }}</h4>
                                                        <p class="text-[10px] text-base-content/50 truncate">{{ $active->homeTeam->school_name ?? '' }}</p>
                                                    </div>
                                                    <div class="bg-white border border-base-200 px-3 py-1 rounded-xl shadow-xs text-center shrink-0">
                                                        <span class="text-base font-black text-primary">{{ $active->home_score ?? 0 }}</span>
                                                        <span class="text-base-content/30 font-bold mx-1">-</span>
                                                        <span class="text-base font-black text-primary">{{ $active->away_score ?? 0 }}</span>
                                                    </div>
                                                    <div class="text-left">
                                                        <h4 class="font-extrabold text-sm text-base-content truncate">{{ $active->awayTeam->team_name ?? 'BYE' }}</h4>
                                                        <p class="text-[10px] text-base-content/50 truncate">{{ $active->awayTeam->school_name ?? '' }}</p>
                                                    </div>
                                                </div>
                                            </div>
                                        @else
                                            <div class="p-3 bg-base-100 rounded-2xl border border-dashed border-base-200 text-center">
                                                <p class="text-xs font-bold text-base-content/40">{{ __('Tiada perlawanan sedang berlangsung.') }}</p>
                                            </div>
                                        @endif
                                    </div>

                                    {{-- 2. GILIRAN SETERUSNYA (NEXT UP - TURN #1) --}}
                                    @php $nextMatch = $upcoming->first(); @endphp
                                    <div>
                                        <div class="flex items-center justify-between mb-2">
                                            <div class="flex items-center gap-1.5">
                                                <span class="text-amber-500 text-xs">🔥</span>
                                                <span class="text-[10px] font-black text-amber-700 uppercase tracking-widest">{{ __('GILIRAN SETERUSNYA (TURN #1)') }}</span>
                                            </div>
                                            <span class="text-[9px] font-black bg-amber-100 text-amber-800 border border-amber-200 px-2 py-0.5 rounded-full uppercase">
                                                ⚠️ {{ __('Sedia di tepi padang') }}
                                            </span>
                                        </div>
                                        @if($nextMatch)
                                            @php
                                                $qSearch = strtolower(trim($this->queueSearch));
                                                $isNextHigh = $qSearch && (
                                                    str_contains(strtolower($nextMatch->homeTeam->team_name ?? ''), $qSearch) || 
                                                    str_contains(strtolower($nextMatch->awayTeam->team_name ?? ''), $qSearch)
                                                );
                                            @endphp
                                            <div class="p-4 rounded-2xl border-2 {{ $isNextHigh ? 'border-primary ring-4 ring-primary/20 bg-primary/5' : 'border-amber-200 bg-amber-50/50' }}">
                                                <div class="flex justify-between items-center text-[10px] font-bold text-amber-900/70 mb-2">
                                                    <span>{{ $nextMatch->category->name ?? '' }} &middot; {{ $nextMatch->round_name }}</span>
                                                    @if($nextMatch->scheduled_time)
                                                        <span class="bg-white/80 px-2 py-0.5 rounded font-black text-amber-800">{{ $nextMatch->scheduled_time->format('h:i A') }}</span>
                                                    @endif
                                                </div>
                                                <div class="grid grid-cols-[1fr_auto_1fr] items-center gap-2">
                                                    <div class="text-right">
                                                        <h4 class="font-extrabold text-sm text-base-content truncate">{{ $nextMatch->homeTeam->team_name ?? 'BYE' }}</h4>
                                                        <p class="text-[10px] text-base-content/50 truncate">{{ $nextMatch->homeTeam->school_name ?? '' }}</p>
                                                    </div>
                                                    <span class="text-xs font-black text-amber-700 bg-white border border-amber-200 px-2.5 py-1 rounded-xl shrink-0">VS</span>
                                                    <div class="text-left">
                                                        <h4 class="font-extrabold text-sm text-base-content truncate">{{ $nextMatch->awayTeam->team_name ?? 'BYE' }}</h4>
                                                        <p class="text-[10px] text-base-content/50 truncate">{{ $nextMatch->awayTeam->school_name ?? '' }}</p>
                                                    </div>
                                                </div>
                                            </div>
                                        @else
                                            <div class="p-3 bg-base-100 rounded-2xl border border-dashed border-base-200 text-center">
                                                <p class="text-xs font-bold text-base-content/40">{{ __('Tiada perlawanan seterusnya dijadualkan.') }}</p>
                                            </div>
                                        @endif
                                    </div>

                                    {{-- 3. SENARAI GILIRAN MENUNGGU (UPCOMING QUEUE - TURN #2, #3, ...) --}}
                                    @php $remainingQueue = $upcoming->slice(1); @endphp
                                    @if($remainingQueue->isNotEmpty())
                                        <div>
                                            <div class="flex items-center gap-1.5 mb-2">
                                                <span class="text-blue-500 text-xs">⏳</span>
                                                <span class="text-[10px] font-black text-base-content/50 uppercase tracking-widest">{{ __('SENARAI GILIRAN MENUNGGU') }} ({{ $remainingQueue->count() }})</span>
                                            </div>
                                            <div class="divide-y divide-base-100 bg-base-50 rounded-2xl border border-base-200 overflow-hidden">
                                                @foreach($remainingQueue as $queueIdx => $qMatch)
                                                    @php
                                                        $turnNumber = $queueIdx + 2;
                                                        $qSearch = strtolower(trim($this->queueSearch));
                                                        $isMatchHigh = $qSearch && (
                                                            str_contains(strtolower($qMatch->homeTeam->team_name ?? ''), $qSearch) || 
                                                            str_contains(strtolower($qMatch->awayTeam->team_name ?? ''), $qSearch)
                                                        );
                                                    @endphp
                                                    <div class="p-3 flex items-center justify-between gap-3 {{ $isMatchHigh ? 'bg-primary/10 font-bold ring-2 ring-primary/30 rounded-xl' : 'hover:bg-base-100' }} transition-colors">
                                                        <div class="flex items-center gap-2 min-w-0">
                                                            <span class="w-6 h-6 rounded-lg bg-white border border-base-200 text-xs font-black text-primary flex items-center justify-center shrink-0">
                                                                {{ $turnNumber }}
                                                            </span>
                                                            <div class="min-w-0">
                                                                <p class="text-xs font-bold text-base-content truncate">
                                                                    {{ $qMatch->homeTeam->team_name ?? 'BYE' }} <span class="text-base-content/40 font-normal">vs</span> {{ $qMatch->awayTeam->team_name ?? 'BYE' }}
                                                                </p>
                                                                <p class="text-[10px] text-base-content/50 truncate">
                                                                    {{ $qMatch->category->name ?? '' }} &middot; {{ $qMatch->round_name }}
                                                                </p>
                                                            </div>
                                                        </div>
                                                        <div class="shrink-0 text-right">
                                                            @if($qMatch->scheduled_time)
                                                                <span class="text-[10px] font-bold text-base-content/60 bg-white px-2 py-0.5 rounded border border-base-200 block">
                                                                    {{ $qMatch->scheduled_time->format('h:i A') }}
                                                                </span>
                                                            @endif
                                                            <span class="text-[9px] font-semibold text-base-content/40">
                                                                {{ __('Lagi :count perlawanan', ['count' => $turnNumber - 1]) }}
                                                            </span>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        @endif

        {{-- ============================================== --}}
        {{-- TAB 3: KEDUDUKAN LIGA (STANDINGS)              --}}
        {{-- ============================================== --}}
        @if($activeTab === 'standings')
            <div class="animate-fade-in space-y-6">
                <div>
                    <h2 class="text-2xl font-black text-base-content">{{ __('Kedudukan Awam') }}</h2>
                    <p class="text-sm text-base-content/60">{{ __('Pilih kategori untuk melihat carta kedudukan terkini peringkat kumpulan/masa.') }}</p>
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
                        <p class="text-base-content/50 font-bold">{{ __('Tiada data liga untuk kategori ini.') }}</p>
                    </div>
                @else
                    <div class="space-y-6">
                        @foreach($this->publicGroups as $group)
                            <div class="bg-white rounded-3xl border border-base-200 shadow-sm overflow-hidden">
                                <div class="bg-base-100 px-5 py-3 border-b border-base-200 flex justify-between items-center">
                                    <h3 class="font-black text-base-content">{{ $group->group_name }}</h3>
                                    <span class="text-[10px] font-bold uppercase text-base-content/40 tracking-wider">
                                        {{ $group->game_type === 'obstacle' ? __('Senarai Masa Terbaik') : __('Carta Liga') }}
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
                                                            <span class="block text-[8px] font-bold text-base-content/40 uppercase whitespace-nowrap">{{ __('Main') }}</span>
                                                            <span class="text-xs font-bold text-base-content/80">{{ $gt->played }}</span>
                                                        </div>
                                                        <div class="text-center">
                                                            <span class="block text-[8px] font-bold text-base-content/40 uppercase whitespace-nowrap">{{ __('Gol') }}</span>
                                                            <span class="text-xs font-black text-amber-600">{{ $gt->goals_for }}</span>
                                                        </div>
                                                        <div class="text-center">
                                                            <span class="block text-[8px] font-bold text-base-content/40 uppercase whitespace-nowrap">{{ __('Menang') }}</span>
                                                            <span class="text-xs font-bold text-emerald-600">{{ $gt->won }}</span>
                                                        </div>
                                                        <div class="text-center bg-base-100 rounded py-0.5 px-1">
                                                            <span class="block text-[8px] font-bold text-base-content/40 uppercase whitespace-nowrap">{{ __('Mata') }}</span>
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
        {{-- TAB 4: KALAH MATI (KNOCKOUT)                   --}}
        {{-- ============================================== --}}
        @if($activeTab === 'knockout')
            <div class="animate-fade-in space-y-6">
                <div>
                    <h2 class="text-2xl font-black text-base-content">{{ __('Carta Kalah Mati') }}</h2>
                    <p class="text-sm text-base-content/60">{{ __('Carta pusingan akhir (Knockout Bracket).') }}</p>
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
                        <p class="text-base-content/50 font-bold">{{ __('Carta kalah mati belum dijana untuk kategori ini.') }}</p>
                    </div>
                @else
                    @foreach($this->knockoutBrackets as $stage => $rounds)
                        <div class="bg-white rounded-3xl border-2 border-base-200 shadow-sm overflow-hidden mb-6">
                            <div class="bg-gradient-to-r {{ $stage === 'trophy_knockout' ? 'from-amber-400 to-yellow-500' : 'from-slate-300 to-slate-400' }} px-5 py-4">
                                <h3 class="font-black text-white text-lg">
                                    {{ $stage === 'trophy_knockout' ? __('🏆 PUSINGAN TROFI') : __('🥈 PUSINGAN PIALA') }}
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
                                                        <span class="text-sm truncate pr-2 {{ !$match->home_team_id ? 'text-base-content/30 italic' : '' }}">{{ $match->homeTeam->team_name ?? __('Menunggu...') }}</span>
                                                        <span class="font-black">{{ $match->home_score ?? '-' }}</span>
                                                    </div>
                                                    <!-- Away -->
                                                    <div class="flex justify-between items-center {{ $match->winner_team_id === $match->away_team_id ? 'text-primary font-bold' : '' }}">
                                                        <span class="text-sm truncate pr-2 {{ !$match->away_team_id ? 'text-base-content/30 italic' : '' }}">{{ $match->awayTeam->team_name ?? __('Menunggu...') }}</span>
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
            <button wire:click="setTab('search')" class="flex-1 flex flex-col items-center justify-center gap-1 h-full transition-colors {{ $activeTab === 'search' ? 'text-primary font-bold' : 'text-base-content/40 hover:text-base-content' }}">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                <span class="text-[10px] tracking-wider uppercase">{{ __('Semakan') }}</span>
            </button>
            
            <button wire:click="setTab('giliran')" class="flex-1 flex flex-col items-center justify-center gap-1 h-full transition-colors {{ $activeTab === 'giliran' ? 'text-primary font-bold' : 'text-base-content/40 hover:text-base-content' }} relative">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                <span class="text-[10px] tracking-wider uppercase">{{ __('Giliran') }}</span>
                <span class="absolute top-2 right-1/4 w-2 h-2 rounded-full bg-red-500 animate-pulse"></span>
            </button>

            <button wire:click="setTab('standings')" class="flex-1 flex flex-col items-center justify-center gap-1 h-full transition-colors {{ $activeTab === 'standings' ? 'text-primary font-bold' : 'text-base-content/40 hover:text-base-content' }}">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
                <span class="text-[10px] tracking-wider uppercase">{{ __('Kedudukan') }}</span>
            </button>

            <button wire:click="setTab('knockout')" class="flex-1 flex flex-col items-center justify-center gap-1 h-full transition-colors {{ $activeTab === 'knockout' ? 'text-primary font-bold' : 'text-base-content/40 hover:text-base-content' }}">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" /></svg>
                <span class="text-[10px] tracking-wider uppercase">{{ __('Kalah Mati') }}</span>
            </button>
        </div>
    </div>
</div>
