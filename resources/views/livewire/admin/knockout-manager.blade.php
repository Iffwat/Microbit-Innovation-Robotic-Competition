<?php

use Livewire\Component;
use App\Models\Category;
use App\Models\Group;
use App\Models\GroupTeam;
use App\Models\TournamentMatch;
use Illuminate\Support\Facades\DB;

new class extends Component {
    public $categories = [];
    public $selectedCategory = null;

    public function mount()
    {
        $this->categories = Category::all();
    }

    public function selectCategory($id)
    {
        $this->selectedCategory = $id;
    }

    public function generateKnockout($categoryId, $includeCup = false)
    {
        $category = Category::find($categoryId);
        $groups = Group::where('category_id', $categoryId)
                       ->where('game_type', '!=', 'obstacle') // Ignore obstacle
                       ->orderBy('group_letter')
                       ->get();

        if ($groups->count() === 0) {
            session()->flash('error', 'Kategori ini tiada kumpulan bola sepak.');
            return;
        }

        // Delete existing knockouts for this category
        TournamentMatch::where('category_id', $categoryId)
            ->whereIn('stage', ['trophy_knockout', 'cup_knockout'])
            ->delete();

        $standings = [];
        foreach ($groups as $group) {
            $standings[$group->group_letter] = $group->getStandings();
        }

        $groupNames = $groups->pluck('group_letter')->toArray();
        $groupCount = count($groupNames);

        // Check if group count is supported: 2, 4, 8, 13, 16
        if (!in_array($groupCount, [2, 4, 8, 13, 16])) {
            session()->flash('error', 'Penjanaan automatik menyokong 2, 4, 8, 13, atau 16 kumpulan. Kategori ini ada ' . $groupCount . ' kumpulan.');
            $this->selectedCategory = null;
            return;
        }

        if ($groupCount === 13) {
            $this->createBracketFor13Groups($categoryId, 'trophy_knockout', $standings, $groupNames, false);
            if ($includeCup) {
                $this->createBracketFor13Groups($categoryId, 'cup_knockout', $standings, $groupNames, true);
            }
            $roundName = 'Pusingan ke-16 (13 Juara + 3 Naib Juara Terbaik)';
        } else {
            $this->createBracket($categoryId, 'trophy_knockout', $standings, $groupNames, 0, 1);
            
            if ($includeCup) {
                $this->createBracket($categoryId, 'cup_knockout', $standings, $groupNames, 2, 3);
            }

            $roundName = match($groupCount) {
                16 => 'Pusingan ke-32',
                8 => 'Pusingan ke-16',
                4 => 'Suku Akhir',
                2 => 'Separuh Akhir',
            };
        }

        session()->flash('success', 'Perlawanan ' . $roundName . ' telah dijana berjaya secara silang untuk ' . $groupCount . ' Kumpulan!');
        $this->selectedCategory = null;
    }

    public function deleteKnockout($categoryId)
    {
        TournamentMatch::where('category_id', $categoryId)
            ->whereIn('stage', ['trophy_knockout', 'cup_knockout'])
            ->delete();

        session()->flash('success', 'Carta kalah mati bagi kategori ini telah berjaya dipadam.');
        $this->selectedCategory = null;
    }

    private function createBracketFor13Groups($categoryId, $stage, $standings, $groupNames, bool $isCup = false)
    {
        if (!$isCup) {
            // Trophy: 13 Group Champions + 3 Best Runners-up = 16 teams
            $champions = [];
            $runnersUp = [];
            foreach ($groupNames as $g) {
                if (isset($standings[$g][0])) $champions[$g] = $standings[$g][0];
                if (isset($standings[$g][1])) $runnersUp[$g] = $standings[$g][1];
            }

            // Sort runners-up by: Points DESC, Goal Difference DESC, Goals For DESC
            uasort($runnersUp, function($a, $b) {
                if ($b->points !== $a->points) return $b->points <=> $a->points;
                if ($b->goal_difference !== $a->goal_difference) return $b->goal_difference <=> $a->goal_difference;
                return $b->goals_for <=> $a->goals_for;
            });

            $bestRunnersUp = array_slice($runnersUp, 0, 3);
            $bestRunnersUpTeams = array_values($bestRunnersUp);

            // 16 teams organized into 8 matches
            $firstRoundPairs = [
                [$champions['A']->team_id ?? null, $bestRunnersUpTeams[2]->team_id ?? null],
                [$champions['B']->team_id ?? null, $champions['M']->team_id ?? null],
                [$champions['C']->team_id ?? null, $bestRunnersUpTeams[1]->team_id ?? null],
                [$champions['D']->team_id ?? null, $champions['L']->team_id ?? null],
                [$champions['E']->team_id ?? null, $bestRunnersUpTeams[0]->team_id ?? null],
                [$champions['F']->team_id ?? null, $champions['K']->team_id ?? null],
                [$champions['G']->team_id ?? null, $champions['J']->team_id ?? null],
                [$champions['H']->team_id ?? null, $champions['I']->team_id ?? null],
            ];
        } else {
            // Cup: Remaining 10 Runners-up + 6 Best 3rd-place teams = 16 teams
            $allRunnersUp = [];
            $thirdPlace = [];
            foreach ($groupNames as $g) {
                if (isset($standings[$g][1])) $allRunnersUp[$g] = $standings[$g][1];
                if (isset($standings[$g][2])) $thirdPlace[$g] = $standings[$g][2];
            }

            uasort($allRunnersUp, function($a, $b) {
                if ($b->points !== $a->points) return $b->points <=> $a->points;
                if ($b->goal_difference !== $a->goal_difference) return $b->goal_difference <=> $a->goal_difference;
                return $b->goals_for <=> $a->goals_for;
            });

            uasort($thirdPlace, function($a, $b) {
                if ($b->points !== $a->points) return $b->points <=> $a->points;
                if ($b->goal_difference !== $a->goal_difference) return $b->goal_difference <=> $a->goal_difference;
                return $b->goals_for <=> $a->goals_for;
            });

            $cupRunnersUp = array_slice($allRunnersUp, 3);
            $bestThird = array_slice($thirdPlace, 0, 6);

            $cupPool = array_merge(array_values($cupRunnersUp), array_values($bestThird));

            $firstRoundPairs = [];
            for ($i = 0; $i < 8; $i++) {
                $home = $cupPool[$i]->team_id ?? null;
                $away = $cupPool[15 - $i]->team_id ?? null;
                $firstRoundPairs[] = [$home, $away];
            }
        }

        // Generate Pusingan ke-16 (8 matches)
        foreach ($firstRoundPairs as $i => $pair) {
            TournamentMatch::create([
                'category_id' => $categoryId,
                'stage' => $stage,
                'round_name' => 'Pusingan ke-16',
                'bracket_position' => $i + 1,
                'home_team_id' => $pair[0],
                'away_team_id' => $pair[1],
                'status' => 'scheduled',
                'field_number' => ($i % 8) + 1,
            ]);
        }

        // Suku Akhir (4 matches)
        for ($m = 0; $m < 4; $m++) {
            TournamentMatch::create([
                'category_id' => $categoryId,
                'stage' => $stage,
                'round_name' => 'Suku Akhir',
                'bracket_position' => $m + 1,
                'home_team_id' => null,
                'away_team_id' => null,
                'status' => 'scheduled',
                'field_number' => ($m % 4) + 1,
            ]);
        }

        // Separuh Akhir (2 matches)
        for ($m = 0; $m < 2; $m++) {
            TournamentMatch::create([
                'category_id' => $categoryId,
                'stage' => $stage,
                'round_name' => 'Separuh Akhir',
                'bracket_position' => $m + 1,
                'home_team_id' => null,
                'away_team_id' => null,
                'status' => 'scheduled',
                'field_number' => ($m % 2) + 1,
            ]);
        }

        // Akhir (1 match)
        TournamentMatch::create([
            'category_id' => $categoryId,
            'stage' => $stage,
            'round_name' => 'Akhir',
            'bracket_position' => 1,
            'home_team_id' => null,
            'away_team_id' => null,
            'status' => 'scheduled',
            'field_number' => 1,
        ]);

        // Penentuan Tempat Ke-3 (1 match)
        TournamentMatch::create([
            'category_id' => $categoryId,
            'stage' => $stage,
            'round_name' => 'Penentuan Tempat Ke-3',
            'bracket_position' => 1,
            'home_team_id' => null,
            'away_team_id' => null,
            'status' => 'scheduled',
            'field_number' => 2,
        ]);
    }

    private function createBracket($categoryId, $stage, $standings, $groupNames, $pos1, $pos2)
    {
        $groupCount = count($groupNames);
        
        // 1. Determine Starting Round Name
        $rounds = [];
        if ($groupCount >= 16) $rounds[] = 'Pusingan ke-32';
        if ($groupCount >= 8)  $rounds[] = 'Pusingan ke-16';
        if ($groupCount >= 4)  $rounds[] = 'Suku Akhir';
        if ($groupCount >= 2)  $rounds[] = 'Separuh Akhir';
        $rounds[] = 'Akhir';
        $rounds[] = 'Penentuan Tempat Ke-3';

        $startingRound = $rounds[0];
        $matchesInStartingRound = $groupCount; // 16 groups = 16 matches (32 teams)

        // 2. Generate First Round (With Teams crossed-over)
        $bracketMatches = [];
        for ($i = 0; $i < $matchesInStartingRound; $i++) {
            $isEven = ($i % 2 === 0);
            $pairIndex = $isEven ? $i + 1 : $i - 1;
            
            $homeGroup = $groupNames[$i];
            $awayGroup = $groupNames[$pairIndex];
            
            $homeTeam = isset($standings[$homeGroup][$pos1]) ? $standings[$homeGroup][$pos1]->team_id : null;
            $awayTeam = isset($standings[$awayGroup][$pos2]) ? $standings[$awayGroup][$pos2]->team_id : null;
            
            // Generate match
            $match = TournamentMatch::create([
                'category_id' => $categoryId,
                'stage' => $stage,
                'round_name' => $startingRound,
                'bracket_position' => $i + 1,
                'home_team_id' => $homeTeam,
                'away_team_id' => $awayTeam,
                'status' => 'scheduled',
                'field_number' => ($i % 8) + 1, // Distribute fields
            ]);
            
            $bracketMatches[$i + 1] = $match;
        }

        // 3. Generate Subsequent Placeholder Rounds
        $currentMatchesCount = $matchesInStartingRound;
        for ($r = 1; $r < count($rounds) - 1; $r++) { // Stop before 3rd place
            $roundName = $rounds[$r];
            $currentMatchesCount = $currentMatchesCount / 2;
            
            for ($m = 0; $m < $currentMatchesCount; $m++) {
                TournamentMatch::create([
                    'category_id' => $categoryId,
                    'stage' => $stage,
                    'round_name' => $roundName,
                    'bracket_position' => $m + 1,
                    'home_team_id' => null, // Placeholder
                    'away_team_id' => null, // Placeholder
                    'status' => 'scheduled',
                    'field_number' => ($m % 4) + 1,
                ]);
            }
        }

        // 4. Generate 3rd Place Match
        TournamentMatch::create([
            'category_id' => $categoryId,
            'stage' => $stage,
            'round_name' => 'Penentuan Tempat Ke-3',
            'bracket_position' => 1,
            'home_team_id' => null,
            'away_team_id' => null,
            'status' => 'scheduled',
            'field_number' => 2,
        ]);
    }
};
?>

<div class="space-y-6 animate-slide-up">
    <!-- Header -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-white border border-base-200 rounded-3xl p-6 shadow-sm">
        <div>
            <h1 class="text-2xl font-black text-base-content">🏆 {{ __('Pengurusan Kalah Mati') }}</h1>
            <p class="text-sm text-base-content/60 mt-1">{{ __('Jana dan pantau carta pusingan kalah mati (Knockout Brackets) selepas tamat peringkat kumpulan.') }}</p>
        </div>
    </div>

    <!-- Notifications -->
    @if(session('success'))
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-5 py-4 rounded-2xl flex items-center gap-3">
            <svg class="w-5 h-5 text-emerald-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            <span class="font-bold text-sm">{{ session('success') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-50 border border-red-200 text-red-800 px-5 py-4 rounded-2xl flex items-center gap-3">
            <svg class="w-5 h-5 text-red-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            <span class="font-bold text-sm">{{ session('error') }}</span>
        </div>
    @endif

    <!-- Category Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($categories as $category)
            @php
                $hasKnockouts = \App\Models\TournamentMatch::where('category_id', $category->id)->whereIn('stage', ['trophy_knockout', 'cup_knockout'])->exists();
                $groups = \App\Models\Group::where('category_id', $category->id)->where('game_type', '!=', 'obstacle')->get();
                $hasGroups = $groups->isNotEmpty();
            @endphp
            <div class="bg-white rounded-3xl border border-base-200 shadow-sm overflow-hidden flex flex-col justify-between hover:border-primary/50 transition-all">
                <div class="p-6">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-2xl bg-primary/10 text-primary flex items-center justify-center font-black">
                            {{ substr($category->name, 0, 1) }}
                        </div>
                        <h3 class="font-bold text-lg text-base-content">{{ $category->name }}</h3>
                    </div>
                    
                    @if($hasGroups)
                        <div class="space-y-2 mt-4">
                            <div class="flex justify-between items-center bg-base-50 px-4 py-2 rounded-xl text-sm">
                                <span class="text-base-content/60 font-medium">{{ __('Jumlah Kumpulan:') }}</span>
                                <span class="font-bold text-base-content">{{ $groups->count() }} ({{ $groups->pluck('group_letter')->join(', ') }})</span>
                            </div>
                            <div class="flex justify-between items-center bg-base-50 px-4 py-2 rounded-xl text-sm">
                                <span class="text-base-content/60 font-medium">{{ __('Status Kalah Mati:') }}</span>
                                @if($hasKnockouts)
                                    <span class="font-bold text-primary">{{ __('Telah Dijana') }}</span>
                                @else
                                    <span class="font-bold text-amber-500">{{ __('Belum Dijana') }}</span>
                                @endif
                            </div>
                        </div>
                    @else
                        <p class="text-sm text-base-content/50 mt-4 italic">{{ __('Kategori ini tidak mempunyai peringkat liga/kumpulan.') }}</p>
                    @endif
                </div>

                <div class="p-4 bg-base-50 border-t border-base-200">
                    @if(!$hasGroups)
                        <button class="w-full bg-base-200 text-base-content/40 font-bold py-2.5 rounded-xl cursor-not-allowed text-sm">{{ __('Tiada Sokongan') }}</button>
                    @elseif($hasKnockouts)
                        <div class="flex gap-2">
                            <a href="{{ route('admin.knockout.show', $category->id) }}" class="flex-1 text-center bg-white border-2 border-primary text-primary hover:bg-primary hover:text-white font-bold py-2.5 rounded-xl transition-colors text-sm">
                                {{ __('Lihat Carta') }}
                            </a>
                            @if(session('auth_role') === 'master')
                            <button wire:click="selectCategory({{ $category->id }})" class="px-3 bg-base-200 hover:bg-primary/10 hover:text-primary text-base-content/60 font-bold rounded-xl transition-colors text-sm" title="{{ __('Jana Semula') }}">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            </button>
                            <button wire:click="deleteKnockout({{ $category->id }})" 
                                    wire:confirm="AMARAN: Anda pasti mahu MEMADAM seluruh carta kalah mati untuk {{ $category->name }}?"
                                    class="px-3 bg-base-200 hover:bg-red-100 hover:text-red-600 text-base-content/60 font-bold rounded-xl transition-colors text-sm" title="{{ __('Padam Kalah Mati') }}">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                            @endif
                        </div>
                      @else
                          @if(session('auth_role') === 'master')
                          <button wire:click="selectCategory({{ $category->id }})" class="w-full bg-primary hover:bg-primary/90 text-white font-bold py-2.5 rounded-xl shadow-lg shadow-primary/20 transition-colors text-sm">
                              {{ __('Jana Kalah Mati') }}
                          </button>
                          @else
                          <button disabled class="w-full bg-base-200 text-base-content/40 font-bold py-2.5 rounded-xl cursor-not-allowed text-sm">
                              {{ __('Menunggu Admin') }}
                          </button>
                          @endif
                      @endif
                </div>
            </div>
        @endforeach
    </div>

    <!-- Generator Modal -->
    @if($selectedCategory)
        @php
            $cat = \App\Models\Category::find($selectedCategory);
            $hasKnockouts = \App\Models\TournamentMatch::where('category_id', $cat->id)->whereIn('stage', ['trophy_knockout', 'cup_knockout'])->exists();
            $groupCount = \App\Models\Group::where('category_id', $cat->id)->where('game_type', '!=', 'obstacle')->count();
        @endphp
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm">
            <div class="bg-white rounded-3xl shadow-2xl w-full max-w-lg overflow-hidden animate-slide-up">
                <div class="p-6 border-b border-base-200 bg-base-50">
                    <h3 class="font-black text-xl text-base-content">{{ __('Jana Perlawanan:') }} {{ $cat->name }}</h3>
                </div>
                
                <div class="p-6 space-y-6">
                    <div class="bg-blue-50 border border-blue-200 text-blue-800 p-4 rounded-2xl text-sm leading-relaxed">
                        {{ __('Kategori ini mempunyai') }} <strong>{{ $groupCount }} {{ __('Kumpulan') }}</strong>.
                        @if($groupCount === 13)
                            <span class="block mt-1 font-semibold text-blue-900">{{ __('Format Khas 13 Kumpulan: 13 Juara Kumpulan + 3 Naib Juara Terbaik akan disusun ke Pusingan ke-16 Trofi (16 Pasukan).') }}</span>
                        @else
                            {{ __('Sistem akan menyusun perlawanan secara silang (Piawaian FIFA) berdasarkan kedudukan terkini peringkat kumpulan.') }}
                        @endif
                    </div>

                    @if($hasKnockouts)
                        <div class="bg-red-50 border border-red-200 text-red-700 p-4 rounded-2xl text-sm font-medium flex items-start gap-3">
                            <svg class="w-5 h-5 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                            {{ __('AMARAN: Data kalah mati (beserta skor) yang sedia ada untuk kategori ini akan dipadam dan dijana semula secara automatik jika anda meneruskan.') }}
                        </div>
                    @endif

                    <div class="space-y-3">
                        <button wire:click="generateKnockout({{ $cat->id }}, false)" class="w-full bg-primary hover:bg-primary/90 text-white font-bold py-4 rounded-2xl shadow-lg shadow-primary/20 transition-all flex flex-col items-center justify-center gap-1">
                            <span>{{ __('Jana Pusingan Trofi Sahaja') }}</span>
                            <span class="text-xs font-medium text-white/70">({{ __('Juara & Naib Juara Kumpulan sahaja') }})</span>
                        </button>
                        
                        <button wire:click="generateKnockout({{ $cat->id }}, true)" class="w-full bg-secondary hover:bg-secondary/90 text-white font-bold py-4 rounded-2xl shadow-lg shadow-secondary/20 transition-all flex flex-col items-center justify-center gap-1">
                            <span>{{ __('Jana Trofi & Piala (Khas U12)') }}</span>
                            <span class="text-xs font-medium text-white/70">({{ __('Trofi: Top 2 | Piala: Tempat 3 & 4') }})</span>
                        </button>

                        @if($hasKnockouts)
                        <button wire:click="deleteKnockout({{ $cat->id }})" 
                                wire:confirm="AMARAN: Anda pasti mahu MEMADAM seluruh carta kalah mati untuk {{ $cat->name }}?"
                                class="w-full bg-white border-2 border-red-200 text-red-600 hover:bg-red-50 font-bold py-3 rounded-2xl transition-all flex items-center justify-center gap-2 text-sm">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            <span>{{ __('Padam Carta Kalah Mati Sedia Ada') }}</span>
                        </button>
                        @endif
                    </div>
                </div>

                <div class="p-4 border-t border-base-200 bg-base-50 flex justify-end">
                    <button wire:click="$set('selectedCategory', null)" class="px-6 py-2.5 bg-base-200 hover:bg-base-300 text-base-content font-bold rounded-xl transition-colors text-sm">
                        {{ __('Batal') }}
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
