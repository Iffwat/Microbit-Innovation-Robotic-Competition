<?php

use App\Models\Category;
use App\Models\Team;
use App\Models\Group;
use App\Models\GroupTeam;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

new class extends Component
{
    public string $activeTab = 'isobot'; // isobot, sky_soccer, obstacle

    public function getCategoriesProperty()
    {
        $allowedSlugs = match($this->activeTab) {
            'isobot'     => ['u12', 'u15', 'u20', 'ppki'],
            'sky_soccer' => ['u12_ppki', 'u15_u20'],
            'obstacle'   => ['u12_ppki', 'u15_u20'],
            default      => []
        };

        return Category::whereIn('slug', $allowedSlugs)
            ->orderBy('sort_order')
            ->get()
            ->map(function ($cat) {
                $cat->checked_in_teams = Team::where('category_id', $cat->id)
                    ->where('game_type', $this->activeTab)
                    ->where('status', 'checked_in')
                    ->count();
                $cat->groups_count = Group::where('category_id', $cat->id)
                    ->where('game_type', $this->activeTab)
                    ->count();
                return $cat;
            });
    }

    public function switchTab(string $tab)
    {
        $this->activeTab = $tab;
    }

    public function generateGroups(int $categoryId): void
    {
        $category = Category::findOrFail($categoryId);

        $teams = Team::where('category_id', $category->id)
            ->where('game_type', $this->activeTab)
            ->where('status', 'checked_in')
            ->inRandomOrder() // randomize within each school first
            ->get();

        if ($teams->isEmpty()) {
            $this->js("alert('Tiada pasukan yang hadir untuk dijana kumpulan.')");
            return;
        }

        $totalTeams = $teams->count();
        $isObstacle = $this->activeTab === 'obstacle';
        
        if ($isObstacle) {
            $numGroups = 2;
        } elseif ($category->isRoundRobinOnly() || $category->slug === 'ppki') {
            // PPKI is pure Round Robin (All teams in 1 Single Group)
            $numGroups = 1;
        } else {
            $defaultPerGroup = $category->teams_per_group ?: 5;
            $rawGroups = (int) ceil($totalTeams / $defaultPerGroup);

            // Ensure the generated number of groups is ALWAYS EVEN (targeting 4-5 teams per group)
            if ($totalTeams <= 3) {
                $numGroups = 1;
            } elseif ($totalTeams <= 7) {
                $numGroups = 2;
            } elseif ($rawGroups % 2 === 0 && $rawGroups >= 2) {
                $numGroups = $rawGroups;
            } else {
                // If rawGroups is odd, compare candidate even group counts (rawGroups - 1 vs rawGroups + 1)
                $kLower = max(2, $rawGroups - 1);
                $kUpper = $rawGroups + 1;

                $avgLower = $totalTeams / $kLower;
                $avgUpper = $totalTeams / $kUpper;

                // We want average group size as close to 4.5 (min 4, max 5) as possible
                $scoreLower = abs($avgLower - 4.5);
                $scoreUpper = abs($avgUpper - 4.5);

                if ($scoreUpper <= $scoreLower && ($totalTeams / $kUpper) >= 3.0) {
                    $numGroups = $kUpper;
                } else {
                    $numGroups = $kLower;
                }
            }
        }

        // --- School-aware Greedy Distribution Algorithm ---
        // 1. Normalize school names to catch variations (e.g. SK vs Sekolah Kebangsaan)
        $teams->each(function($team) {
            $name = strtoupper(trim($team->school_name));
            $name = str_replace(
                ['SEKOLAH MENENGAH KEBANGSAAN ', 'SEKOLAH KEBANGSAAN ', 'SMK ', 'SK ', 'SJKC ', 'SJKT '], 
                '', 
                $name
            );
            $team->normalized_school = trim($name);
        });

        // 2. Group teams by normalized school, sort schools by team count DESC
        $bySchool = $teams
            ->groupBy('normalized_school')
            ->sortByDesc(fn($g) => $g->count())
            ->values();

        DB::beginTransaction();
        try {
            // Remove existing groups for this category+game
            $existingGroupIds = Group::where('category_id', $category->id)
                ->where('game_type', $this->activeTab)
                ->pluck('id');
            GroupTeam::whereIn('group_id', $existingGroupIds)->delete();
            Group::whereIn('id', $existingGroupIds)->delete();

            $letters = range('A', 'Z');
            $groups  = [];
            $groupsArray = array_fill(0, $numGroups, []);

            for ($i = 0; $i < $numGroups; $i++) {
                $autoField = null;
                
                if ($this->activeTab === 'sky_soccer') {
                    $autoField = 'Arena Sky Soccer'; // Sky Soccer only 1 field
                } elseif ($this->activeTab === 'isobot') {
                    if ($category->slug === 'u12') {
                        $autoField = (string)(($i % 8) + 1); // Padang 1 to 8
                    } else {
                        $autoField = (string)(($i % 2) + 9); // Padang 9 to 10
                    }
                } elseif ($this->activeTab === 'obstacle') {
                    $autoField = 'Course ' . (($i % 2) + 1); // Course 1 & 2
                }
                if ($isObstacle) {
                    $groups[] = Group::create([
                        'category_id' => $category->id,
                        'game_type'   => $this->activeTab,
                        'group_name'  => 'Course ' . ($i + 1),
                        'group_letter'=> (string)($i + 1),
                        'field_number'=> $autoField,
                    ]);
                } else {
                    $letter   = $letters[$i % 26];
                    $groups[] = Group::create([
                        'category_id' => $category->id,
                        'game_type'   => $this->activeTab,
                        'group_name'  => 'Kumpulan ' . $letter,
                        'group_letter'=> $letter,
                        'field_number'=> $autoField,
                    ]);
                }
            }

            // 3. Assign teams greedily to groups minimizing same-school clashes
            foreach ($bySchool as $schoolTeams) {
                foreach ($schoolTeams as $team) {
                    $bestGroupId = -1;
                    $minSameSchool = PHP_INT_MAX;
                    $minTotal = PHP_INT_MAX;

                    for ($i = 0; $i < $numGroups; $i++) {
                        $sameSchoolCount = collect($groupsArray[$i])->where('normalized_school', $team->normalized_school)->count();
                        $totalCount = count($groupsArray[$i]);

                        if ($sameSchoolCount < $minSameSchool) {
                            $minSameSchool = $sameSchoolCount;
                            $minTotal = $totalCount;
                            $bestGroupId = $i;
                        } elseif ($sameSchoolCount == $minSameSchool && $totalCount < $minTotal) {
                            $minTotal = $totalCount;
                            $bestGroupId = $i;
                        }
                    }

                    $groupsArray[$bestGroupId][] = $team;
                    
                    // Save to DB immediately
                    GroupTeam::create([
                        'group_id' => $groups[$bestGroupId]->id,
                        'team_id'  => $team->id,
                    ]);
                }
            }

            // 4. Detect any unavoidable same-school conflicts
            $conflicts = 0;
            foreach ($groupsArray as $gTeams) {
                $schools = collect($gTeams)->pluck('normalized_school');
                if ($schools->count() !== $schools->unique()->count()) {
                    $conflicts++;
                }
            }

            DB::commit();

            $msg = "Berjaya menjana $numGroups kumpulan/laluan untuk {$category->name} ($totalTeams pasukan).";
            if ($conflicts > 0) {
                $msg .= " NOTA: $conflicts kumpulan mempunyai 2 pasukan dari sekolah yang sama (tidak dapat dielakkan).";
            } else {
                $msg = "Berjaya! Tiada sekolah yang sama dalam satu " . ($isObstacle ? "laluan (course)" : "kumpulan") . ".";
            }
            $this->js("alert('" . addslashes($msg) . "')");

        } catch (\Exception $e) {
            DB::rollBack();
            $this->js("alert('Ralat: " . addslashes($e->getMessage()) . "')");
        }
    }

    public function deleteGroups(int $categoryId): void
    {
        $category = Category::findOrFail($categoryId);
        DB::beginTransaction();
        try {
            $existingGroupIds = Group::where('category_id', $category->id)
                ->where('game_type', $this->activeTab)
                ->pluck('id');
                
            \App\Models\TournamentMatch::whereIn('group_id', $existingGroupIds)->delete();
            GroupTeam::whereIn('group_id', $existingGroupIds)->delete();
            Group::whereIn('id', $existingGroupIds)->delete();
            DB::commit();
            $this->js("alert('Semua rekod bagi {$category->name} telah dipadam.')");
        } catch (\Exception $e) {
            DB::rollBack();
            $this->js("alert('Ralat: " . addslashes($e->getMessage()) . "')");
        }
    }

    // DEV ONLY
    public function devBulkCheckin(int $categoryId): void
    {
        if (!app()->environment('local')) return;

        $category = Category::findOrFail($categoryId);
        $count = Team::where('category_id', $category->id)
            ->where('game_type', $this->activeTab)
            ->where('status', 'registered')
            ->update(['status' => 'checked_in', 'checked_in_at' => now()]);

        $this->js("alert('[DEV] {$count} pasukan {$category->name} telah ditanda Hadir.')");
    }

    public function devResetCheckin(int $categoryId): void
    {
        if (!app()->environment('local')) return;

        $category = Category::findOrFail($categoryId);
        Team::where('category_id', $category->id)
            ->where('game_type', $this->activeTab)
            ->update(['status' => 'registered', 'checked_in_at' => null]);

        $this->js("alert('[DEV] Semua pasukan {$category->name} ditukar semula ke Berdaftar.')");
    }
};
?>

<div class="space-y-6 animate-slide-up">

    {{-- Game Tabs --}}
    <div class="flex gap-2 overflow-x-auto pb-2">
        <button wire:click="switchTab('isobot')" 
                class="px-5 py-2.5 rounded-xl text-sm font-bold transition-all shrink-0 {{ $activeTab === 'isobot' ? 'bg-blue-600 text-white shadow-md shadow-blue-600/20' : 'bg-white border border-base-200 text-base-content/60 hover:bg-base-200' }}">
            🤖 Isobot Soccer
        </button>
        <button wire:click="switchTab('sky_soccer')" 
                class="px-5 py-2.5 rounded-xl text-sm font-bold transition-all shrink-0 {{ $activeTab === 'sky_soccer' ? 'bg-violet-600 text-white shadow-md shadow-violet-600/20' : 'bg-white border border-base-200 text-base-content/60 hover:bg-base-200' }}">
            🚁 Drone Sky Soccer
        </button>
        <button wire:click="switchTab('obstacle')" 
                class="px-5 py-2.5 rounded-xl text-sm font-bold transition-all shrink-0 {{ $activeTab === 'obstacle' ? 'bg-amber-500 text-white shadow-md shadow-amber-500/20' : 'bg-white border border-base-200 text-base-content/60 hover:bg-base-200' }}">
            🏁 Drone Obstacle
        </button>
    </div>

    {{-- Dev Tools Banner --}}
    @if(app()->environment('local'))
    <div class="bg-amber-50 border-2 border-amber-300 border-dashed rounded-2xl p-4">
        <div class="flex items-center gap-2 mb-3">
            <span class="bg-amber-400 text-amber-900 text-[10px] font-extrabold px-2 py-0.5 rounded uppercase tracking-widest">DEV ONLY</span>
            <p class="text-sm font-bold text-amber-800">Alat Pembangunan — Tidak akan muncul dalam Production</p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            @foreach($this->categories as $category)
            <div class="bg-white rounded-xl border border-amber-200 px-4 py-3 flex items-center justify-between gap-3">
                <div>
                    <p class="font-bold text-sm text-base-content">{{ $category->name }}</p>
                    <p class="text-xs text-base-content/50">{{ $category->checked_in_teams }} hadir / {{ \App\Models\Team::where('category_id', $category->id)->where('game_type', $activeTab)->count() }} jumlah</p>
                </div>
                <div class="flex gap-2">
                    <button wire:click="devBulkCheckin({{ $category->id }})"
                            wire:loading.attr="disabled"
                            class="text-xs font-bold bg-emerald-100 text-emerald-700 hover:bg-emerald-200 px-3 py-1.5 rounded-lg transition-colors">
                        ✓ Hadir Semua
                    </button>
                    <button wire:click="devResetCheckin({{ $category->id }})"
                            wire:loading.attr="disabled"
                            class="text-xs font-bold bg-red-100 text-red-600 hover:bg-red-200 px-3 py-1.5 rounded-lg transition-colors">
                        ↩ Reset
                    </button>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    @php
        $gameLabel = match($activeTab) {
            'isobot' => 'Isobot Soccer',
            'sky_soccer' => 'Drone Sky Soccer',
            'obstacle' => 'Drone Obstacle',
            default => 'Permainan'
        };
        $isObstacle = $activeTab === 'obstacle';
    @endphp

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-extrabold text-base-content">Pengurusan Kumpulan — {{ $gameLabel }}</h2>
            <p class="text-sm text-base-content/60 mt-1">
                @if($isObstacle) Jana laluan (course) untuk pengiraan masa. @else Jana kumpulan bagi pusingan liga. @endif (Hanya pasukan yang hadir akan dijana).
            </p>
        </div>
    </div>

    {{-- Category Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
        @foreach($this->categories as $category)
        <div class="bg-white border {{ $category->groups_count > 0 ? 'border-emerald-200' : 'border-base-200' }} rounded-2xl p-6 shadow-sm relative overflow-hidden transition-all hover:shadow-md">

            @if($category->groups_count > 0)
                <div class="absolute top-0 right-0 bg-emerald-500 text-white text-[10px] font-bold px-3 py-1 rounded-bl-xl uppercase tracking-widest">
                    Telah Dijana
                </div>
            @endif

            <div class="flex items-start justify-between mb-4">
                <div>
                    <h3 class="text-lg font-bold text-base-content">{{ $category->name }}</h3>
                    <p class="text-xs text-base-content/50 mt-0.5 uppercase tracking-widest font-semibold">{{ $category->format_label }}</p>
                </div>
                <div class="text-right">
                    <p class="text-3xl font-extrabold {{ $category->checked_in_teams > 0 ? 'text-emerald-600' : 'text-base-content/30' }}">
                        {{ $category->checked_in_teams }}
                    </p>
                    <p class="text-[10px] text-base-content/40 uppercase tracking-widest font-bold mt-0.5">Pasukan Hadir</p>
                </div>
            </div>

            <div class="bg-base-200/50 rounded-xl p-4 mb-5 flex justify-between items-center">
                <div>
                    <p class="text-xs text-base-content/50 font-medium">@if($isObstacle) Laluan Dijana @else Saiz Kumpulan @endif</p>
                    <p class="text-sm font-bold text-base-content">@if($isObstacle) 2 Laluan (Course) @else {{ $category->teams_per_group }} pasukan / kump. @endif</p>
                </div>
                <div class="text-right">
                    <p class="text-xs text-base-content/50 font-medium">@if($isObstacle) Status Janaan @else Kumpulan Dijana @endif</p>
                    <p class="text-sm font-bold {{ $category->groups_count > 0 ? 'text-emerald-600' : 'text-base-content/40' }}">
                        {{ $category->groups_count > 0 ? $category->groups_count . ($isObstacle ? ' Laluan' : ' Kumpulan') : '—' }}
                    </p>
                </div>
                <div class="text-right">
                    <p class="text-xs text-base-content/50 font-medium">Padang</p>
                    <p class="text-sm font-bold text-base-content">{{ $category->fields_count }}</p>
                </div>
            </div>

            <div class="flex gap-2">
                @if($category->groups_count == 0)
                    <button wire:click="generateGroups({{ $category->id }})" wire:loading.attr="disabled"
                            wire:confirm="Jana @if($isObstacle) laluan @else kumpulan @endif untuk {{ $category->name }}? Hanya pasukan yang HADIR akan dimasukkan."
                            class="flex-1 bg-primary hover:bg-primary/90 text-white text-sm font-bold py-3 px-4 rounded-xl transition-colors flex justify-center items-center gap-2 disabled:opacity-50">
                        <span wire:loading.remove wire:target="generateGroups({{ $category->id }})">
                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                            Jana @if($isObstacle) Laluan @else Kumpulan @endif
                        </span>
                        <span wire:loading wire:target="generateGroups({{ $category->id }})">Menjana...</span>
                    </button>
                @else
                    <a href="{{ route('admin.groups.show', ['game' => $activeTab, 'category' => $category->slug]) }}"
                       class="flex-1 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 border border-emerald-200 text-sm font-bold py-3 px-4 rounded-xl transition-colors flex justify-center items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        Lihat & Urus @if($isObstacle) Laluan @else Kumpulan @endif
                    </a>
                    <button wire:click="deleteGroups({{ $category->id }})"
                            wire:confirm="AMARAN! Padam semua rekod untuk {{ $category->name }}?"
                            class="bg-white border-2 border-red-200 text-red-500 hover:bg-red-50 text-sm font-bold p-3 rounded-xl transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>
                @endif
            </div>
        </div>
        @endforeach
    </div>
</div>