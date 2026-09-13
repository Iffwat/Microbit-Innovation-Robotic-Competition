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
            $roundName = $includeCup ? 'Pusingan Trofi & Piala (26 Pasukan Setiap Satu)' : 'Pusingan Trofi (26 Pasukan: 13 Juara + 13 Naib Juara)';
        } else {
            $this->createBracket($categoryId, 'trophy_knockout', $standings, $groupNames, 0, 1);
            
            if ($includeCup && $groupCount >= 4) {
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

    public function syncFifthPlace($categoryId)
    {
        TournamentMatch::syncFifthPlaceBracket($categoryId);
        session()->flash('success', 'Perlawanan Tempat Ke-5 berjaya disegerakkan!');
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

    public function createBracketFor13Groups($categoryId, $stage, $standings, $groupNames, bool $isCup = false)
    {
        if (!$isCup) {
            // Trophy: All 13 Champions (1st place) + All 13 Runners-up (2nd place) = 26 teams
            $tier1 = []; // 13 Champions
            $tier2 = []; // 13 Runners-up
            foreach ($groupNames as $g) {
                if (isset($standings[$g][0])) $tier1[$g] = $standings[$g][0];
                if (isset($standings[$g][1])) $tier2[$g] = $standings[$g][1];
            }
        } else {
            // Cup: All 13 3rd-place + All 13 4th-place = 26 teams
            $tier1 = []; // 13 3rd-place
            $tier2 = []; // 13 4th-place
            foreach ($groupNames as $g) {
                if (isset($standings[$g][2])) $tier1[$g] = $standings[$g][2];
                if (isset($standings[$g][3])) $tier2[$g] = $standings[$g][3];
            }
        }

        // Rank function:
        // 1. Points DESC
        // 2. Goal Difference DESC
        // 3. Highest Goals For DESC ("the highest goal from them are going to bye")
        // 4. Fewest Goals Against ASC
        // 5. Wins DESC
        $rankFunction = function($a, $b) {
            if ($b->points !== $a->points) return $b->points <=> $a->points;
            if ($b->goal_difference !== $a->goal_difference) return $b->goal_difference <=> $a->goal_difference;
            if ($b->goals_for !== $a->goals_for) return $b->goals_for <=> $a->goals_for;
            if ($a->goals_against !== $b->goals_against) return $a->goals_against <=> $b->goals_against;
            return $b->won <=> $a->won;
        };

        uasort($tier1, $rankFunction);
        uasort($tier2, $rankFunction);

        $rankedTier1 = array_values($tier1); // Seeds 1 to 13
        $rankedTier2 = array_values($tier2); // Seeds 14 to 26

        // Build 1-based seed map: seeds[1..26] => team_id
        $seeds = [];
        for ($i = 0; $i < 13; $i++) {
            $seeds[$i + 1] = isset($rankedTier1[$i]) ? $rankedTier1[$i]->team_id : null;
        }
        for ($i = 0; $i < 13; $i++) {
            $seeds[$i + 14] = isset($rankedTier2[$i]) ? $rankedTier2[$i]->team_id : null;
        }

        // 1. Generate Placeholder Downstream Rounds FIRST so advanceKnockoutWinner() can find them!
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

        // Separuh Akhir Tempat Ke-5 (2 matches)
        for ($m = 0; $m < 2; $m++) {
            TournamentMatch::create([
                'category_id' => $categoryId,
                'stage' => $stage,
                'round_name' => 'Separuh Akhir Tempat Ke-5',
                'bracket_position' => $m + 1,
                'home_team_id' => null,
                'away_team_id' => null,
                'status' => 'scheduled',
                'field_number' => $m + 3,
            ]);
        }

        // Penentuan Tempat Ke-5 (1 match)
        TournamentMatch::create([
            'category_id' => $categoryId,
            'stage' => $stage,
            'round_name' => 'Penentuan Tempat Ke-5',
            'bracket_position' => 1,
            'home_team_id' => null,
            'away_team_id' => null,
            'status' => 'scheduled',
            'field_number' => 3,
        ]);

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

        // Pusingan ke-16 (8 matches)
        for ($m = 0; $m < 8; $m++) {
            TournamentMatch::create([
                'category_id' => $categoryId,
                'stage' => $stage,
                'round_name' => 'Pusingan ke-16',
                'bracket_position' => $m + 1,
                'home_team_id' => null,
                'away_team_id' => null,
                'status' => 'scheduled',
                'field_number' => ($m % 8) + 1,
            ]);
        }

        // 2. Generate Pusingan ke-32 (16 matches total: 6 BYEs + 10 Real matches)
        // EXACT CONFIGURATION AS PER OFFICIAL 26-TEAM BRACKET SPECIFICATION:
        // Top 6 Seeds (highest goal / stats) get BYEs straight to P16:
        // P32 Pos 1  -> Seed 1 (BYE)  -> Feeds P16 Match 1 Home (Match 11 in diagram)
        // P32 Pos 5  -> Seed 4 (BYE)  -> Feeds P16 Match 3 Home (Match 13 in diagram)
        // P32 Pos 7  -> Seed 5 (BYE)  -> Feeds P16 Match 4 Home (Match 14 in diagram)
        // P32 Pos 9  -> Seed 2 (BYE)  -> Feeds P16 Match 5 Home (Match 15 in diagram)
        // P32 Pos 13 -> Seed 3 (BYE)  -> Feeds P16 Match 7 Home (Match 17 in diagram)
        // P32 Pos 15 -> Seed 6 (BYE)  -> Feeds P16 Match 8 Home (Match 18 in diagram)
        $byeSlots = [
            1  => 1,
            5  => 4,
            7  => 5,
            9  => 2,
            13 => 3,
            15 => 6,
        ];

        // 10 Real matches in P32:
        // P32 Pos 2  -> Seed 16 vs Seed 17 -> Winner plays Seed 1 in P16 Match 1
        // P32 Pos 3  -> Seed 8  vs Seed 25 -> Feeds P16 Match 2 Home
        // P32 Pos 4  -> Seed 9  vs Seed 24 -> Feeds P16 Match 2 Away
        // P32 Pos 6  -> Seed 13 vs Seed 20 -> Winner plays Seed 4 in P16 Match 3
        // P32 Pos 8  -> Seed 12 vs Seed 21 -> Winner plays Seed 5 in P16 Match 4
        // P32 Pos 10 -> Seed 15 vs Seed 18 -> Winner plays Seed 2 in P16 Match 5
        // P32 Pos 11 -> Seed 7  vs Seed 26 -> Feeds P16 Match 6 Home
        // P32 Pos 12 -> Seed 10 vs Seed 23 -> Feeds P16 Match 6 Away
        // P32 Pos 14 -> Seed 14 vs Seed 19 -> Winner plays Seed 3 in P16 Match 7
        // P32 Pos 16 -> Seed 11 vs Seed 22 -> Winner plays Seed 6 in P16 Match 8
        $realMatchSlots = [
            2  => [16, 17],
            3  => [8, 25],
            4  => [9, 24],
            6  => [13, 20],
            8  => [12, 21],
            10 => [15, 18],
            11 => [7, 26],
            12 => [10, 23],
            14 => [14, 19],
            16 => [11, 22],
        ];

        // Create the 6 BYE matches and auto-advance the seeds to P16!
        foreach ($byeSlots as $pos => $seedNum) {
            $seedTeamId = $seeds[$seedNum] ?? null;
            $byeMatch = TournamentMatch::create([
                'category_id' => $categoryId,
                'stage' => $stage,
                'round_name' => 'Pusingan ke-32',
                'bracket_position' => $pos,
                'home_team_id' => $seedTeamId,
                'away_team_id' => null,
                'status' => 'bye',
                'winner_team_id' => $seedTeamId,
                'field_number' => null,
            ]);
            $byeMatch->advanceKnockoutWinner();
        }

        // Create the 10 Real matches (20 teams)
        $fieldIdx = 0;
        foreach ($realMatchSlots as $pos => $pair) {
            $homeId = $seeds[$pair[0]] ?? null;
            $awayId = $seeds[$pair[1]] ?? null;

            TournamentMatch::create([
                'category_id' => $categoryId,
                'stage' => $stage,
                'round_name' => 'Pusingan ke-32',
                'bracket_position' => $pos,
                'home_team_id' => $homeId,
                'away_team_id' => $awayId,
                'status' => 'scheduled',
                'field_number' => ($fieldIdx % 8) + 1,
            ]);
            $fieldIdx++;
        }
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

        // 5. Generate 5th Place Classification Bracket (for all brackets with Suku Akhir: groupCount >= 4)
        if ($groupCount >= 4) {
            // Separuh Akhir Tempat Ke-5 (2 matches)
            for ($m = 0; $m < 2; $m++) {
                TournamentMatch::create([
                    'category_id' => $categoryId,
                    'stage' => $stage,
                    'round_name' => 'Separuh Akhir Tempat Ke-5',
                    'bracket_position' => $m + 1,
                    'home_team_id' => null,
                    'away_team_id' => null,
                    'status' => 'scheduled',
                    'field_number' => $m + 3,
                ]);
            }

            // Penentuan Tempat Ke-5 (1 match)
            TournamentMatch::create([
                'category_id' => $categoryId,
                'stage' => $stage,
                'round_name' => 'Penentuan Tempat Ke-5',
                'bracket_position' => 1,
                'home_team_id' => null,
                'away_team_id' => null,
                'status' => 'scheduled',
                'field_number' => 3,
            ]);
        } elseif ($groupCount === 2 && $stage === 'trophy_knockout') {
            // Pilihan 1: Perlawanan Penentuan Terus (1 Perlawanan Sahaja)
            // Tempat Ke-3 Kumpulan A vs Tempat Ke-3 Kumpulan B (Penentuan Tempat Ke-5)
            $groupA = $groupNames[0];
            $groupB = $groupNames[1];

            $teamA = isset($standings[$groupA][2]) ? $standings[$groupA][2]->team_id : null;
            $teamB = isset($standings[$groupB][2]) ? $standings[$groupB][2]->team_id : null;

            if ($teamA || $teamB) {
                TournamentMatch::create([
                    'category_id' => $categoryId,
                    'stage' => 'trophy_knockout',
                    'round_name' => 'Penentuan Tempat Ke-5',
                    'bracket_position' => 1,
                    'home_team_id' => $teamA,
                    'away_team_id' => $teamB,
                    'status' => 'scheduled',
                    'field_number' => 3,
                ]);
            }
        }
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
                            <button wire:click="syncFifthPlace({{ $category->id }})" class="px-2.5 bg-base-200 hover:bg-emerald-50 hover:text-emerald-700 text-base-content/60 font-bold rounded-xl transition-colors text-xs flex items-center gap-1" title="{{ __('Segerak Tempat Ke-5') }}">
                                <span>⚡</span><span class="hidden sm:inline">Ke-5</span>
                            </button>
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
                            <span class="block mt-1 font-semibold text-blue-900">{{ __('Format Khas 13 Kumpulan (26 Pasukan): Semua 13 Juara & 13 Naib Juara layak ke Trofi. Semua 13 Tempat Ke-3 & 13 Tempat Ke-4 layak ke Piala. 6 Pasukan Terbaik mendapat BYE terus ke Pusingan ke-16.') }}</span>
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
                            <span>🏆 {{ __('Jana Pusingan Trofi Sahaja') }}</span>
                            <span class="text-xs font-medium text-white/70">
                                @if($groupCount === 13)
                                    ({{ __('26 Pasukan: 13 Juara + 13 Naib Juara | 6 BYE ke P16') }})
                                @elseif($groupCount === 2)
                                    ({{ __('Juara, Naib Juara & Penentuan Tempat Ke-5') }})
                                @else
                                    ({{ __('Juara & Naib Juara Kumpulan sahaja') }})
                                @endif
                            </span>
                        </button>
                        
                        @if($groupCount >= 4)
                        <button wire:click="generateKnockout({{ $cat->id }}, true)" class="w-full bg-secondary hover:bg-secondary/90 text-white font-bold py-4 rounded-2xl shadow-lg shadow-secondary/20 transition-all flex flex-col items-center justify-center gap-1">
                            <span>🏆 &amp; 🥈 {{ __('Jana Pusingan Trofi & Piala Serentak') }}</span>
                            <span class="text-xs font-medium text-white/70 text-center px-4 leading-relaxed">
                                @if($groupCount === 13)
                                    {{ __('Trofi: 26 Pasukan (13 Juara + 13 Naib Juara) | Piala: 26 Pasukan (13 Ke-3 + 13 Ke-4) [6 Pasukan Terbaik Dapat BYE ke P16]') }}
                                @else
                                    ({{ __('Trofi: Top 2 | Piala: Tempat 3 & 4') }})
                                @endif
                            </span>
                        </button>
                        @endif

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
