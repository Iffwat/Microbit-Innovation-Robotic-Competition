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
            $standings[$group->group_letter] = GroupTeam::where('group_id', $group->id)
                ->orderBy('points', 'desc')
                ->orderByRaw('(goals_for - goals_against) desc')
                ->orderBy('goals_for', 'desc')
                ->get();
        }

        $groupNames = $groups->pluck('group_letter')->toArray();
        $groupCount = count($groupNames);

        // Check if group count is a power of 2 (2, 4, 8, 16)
        if (!in_array($groupCount, [2, 4, 8, 16])) {
            session()->flash('error', 'Penjanaan automatik hanya menyokong 2, 4, 8, atau 16 kumpulan. Kategori ini ada ' . $groupCount . ' kumpulan.');
            $this->selectedCategory = null;
            return;
        }

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

        session()->flash('success', 'Perlawanan ' . $roundName . ' telah dijana berjaya secara silang untuk ' . $groupCount . ' Kumpulan!');
        $this->selectedCategory = null;
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
        $half = $matchesInStartingRound / 2;
        
        // Upper Bracket (Match 1 to Half)
        for ($i = 0; $i < $half; $i++) {
            $groupA = $groupNames[$i * 2];
            $groupB = $groupNames[$i * 2 + 1];
            
            $this->createMatch($categoryId, $stage, $startingRound, $i + 1, 
                $standings[$groupA][$pos1]->team_id ?? null, 
                $standings[$groupB][$pos2]->team_id ?? null);
        }

        // Lower Bracket (Match Half+1 to Full)
        for ($i = 0; $i < $half; $i++) {
            $groupA = $groupNames[$i * 2 + 1];
            $groupB = $groupNames[$i * 2];
            
            $this->createMatch($categoryId, $stage, $startingRound, $half + $i + 1, 
                $standings[$groupA][$pos1]->team_id ?? null, 
                $standings[$groupB][$pos2]->team_id ?? null);
        }

        // 3. Pre-create empty placeholder matches for subsequent rounds
        $subsequentRounds = array_slice($rounds, 1);
        foreach ($subsequentRounds as $rName) {
            $matchesInRound = match($rName) {
                'Pusingan ke-16' => 8,
                'Suku Akhir' => 4,
                'Separuh Akhir' => 2,
                'Akhir' => 1,
                'Penentuan Tempat Ke-3' => 1,
            };

            for ($j = 1; $j <= $matchesInRound; $j++) {
                $this->createMatch($categoryId, $stage, $rName, $j, null, null);
            }
        }
    }

    private function createMatch($categoryId, $stage, $roundName, $bracketPos, $homeTeamId, $awayTeamId)
    {
        TournamentMatch::create([
            'category_id' => $categoryId,
            'stage' => $stage,
            'round_name' => $roundName,
            'bracket_position' => $bracketPos,
            'home_team_id' => $homeTeamId,
            'away_team_id' => $awayTeamId,
            'status' => 'scheduled'
        ]);
    }
};
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-end gap-4">
        <div>
            <h2 class="text-2xl font-bold">Penjana Pusingan Kalah Mati (Knockout)</h2>
            <p class="text-base-content/60 mt-1">Jana jadual kalah mati secara automatik berdasarkan kedudukan peringkat kumpulan bersilang (FIFA Standard).</p>
        </div>
    </div>

    <!-- Alert -->
    @if(session('success'))
        <div class="flex items-center gap-3 bg-emerald-50 border border-emerald-200 text-emerald-800 px-5 py-4 rounded-2xl shadow-sm">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            <span class="font-medium text-sm">{{ session('success') }}</span>
        </div>
    @endif
    @if(session('error'))
        <div class="flex items-center gap-3 bg-red-50 border border-red-200 text-red-800 px-5 py-4 rounded-2xl shadow-sm">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            <span class="font-medium text-sm">{{ session('error') }}</span>
        </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($categories as $category)
            @php
                $groups = \App\Models\Group::where('category_id', $category->id)->where('game_type', '!=', 'obstacle')->get();
                $hasGroups = $groups->count() > 0;
                $hasKnockouts = \App\Models\TournamentMatch::where('category_id', $category->id)->whereIn('stage', ['trophy_knockout', 'cup_knockout'])->exists();
            @endphp
            <div class="bg-white rounded-3xl border-2 {{ $hasKnockouts ? 'border-primary shadow-primary/10' : 'border-base-200 shadow-sm' }} overflow-hidden flex flex-col">
                <div class="p-6 flex-1">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center shrink-0">
                            <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                        </div>
                        <h3 class="font-bold text-lg text-base-content">{{ $category->name }}</h3>
                    </div>
                    
                    @if($hasGroups)
                        <div class="space-y-2 mt-4">
                            <div class="flex justify-between items-center bg-base-50 px-4 py-2 rounded-xl text-sm">
                                <span class="text-base-content/60 font-medium">Jumlah Kumpulan:</span>
                                <span class="font-bold text-base-content">{{ $groups->count() }} ({{ $groups->pluck('group_letter')->join(', ') }})</span>
                            </div>
                            <div class="flex justify-between items-center bg-base-50 px-4 py-2 rounded-xl text-sm">
                                <span class="text-base-content/60 font-medium">Status Kalah Mati:</span>
                                @if($hasKnockouts)
                                    <span class="font-bold text-primary">Telah Dijana</span>
                                @else
                                    <span class="font-bold text-amber-500">Belum Dijana</span>
                                @endif
                            </div>
                        </div>
                    @else
                        <p class="text-sm text-base-content/50 mt-4 italic">Kategori ini tidak mempunyai peringkat liga/kumpulan.</p>
                    @endif
                </div>

                <div class="p-4 bg-base-50 border-t border-base-200">
                    @if(!$hasGroups)
                        <button class="w-full bg-base-200 text-base-content/40 font-bold py-2.5 rounded-xl cursor-not-allowed text-sm">Tiada Sokongan</button>
                    @elseif($hasKnockouts)
                        <div class="flex gap-2">
                            <a href="{{ route('admin.knockout.show', $category->id) }}" class="flex-1 text-center bg-white border-2 border-primary text-primary hover:bg-primary hover:text-white font-bold py-2.5 rounded-xl transition-colors text-sm">
                                Lihat Carta
                            </a>
                            @if(session('auth_role') === 'master')
                            <button wire:click="selectCategory({{ $category->id }})" class="px-3 bg-base-200 hover:bg-red-100 hover:text-red-600 text-base-content/60 font-bold rounded-xl transition-colors text-sm" title="Jana Semula">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            </button>
                            @endif
                        </div>
                      @else
                          @if(session('auth_role') === 'master')
                          <button wire:click="selectCategory({{ $category->id }})" class="w-full bg-primary hover:bg-primary/90 text-white font-bold py-2.5 rounded-xl shadow-lg shadow-primary/20 transition-colors text-sm">
                              Jana Kalah Mati
                          </button>
                          @else
                          <button disabled class="w-full bg-base-200 text-base-content/40 font-bold py-2.5 rounded-xl cursor-not-allowed text-sm">
                              Menunggu Admin
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
                    <h3 class="font-black text-xl text-base-content">Jana Perlawanan: {{ $cat->name }}</h3>
                </div>
                
                <div class="p-6 space-y-6">
                    <div class="bg-blue-50 border border-blue-200 text-blue-800 p-4 rounded-2xl text-sm leading-relaxed">
                        Kategori ini mempunyai <strong>{{ $groupCount }} Kumpulan</strong>. Sistem akan menyusun perlawanan secara silang (Piawaian FIFA) berdasarkan kedudukan terkini peringkat kumpulan.
                    </div>

                    @if($hasKnockouts)
                        <div class="bg-red-50 border border-red-200 text-red-700 p-4 rounded-2xl text-sm font-medium flex items-start gap-3">
                            <svg class="w-5 h-5 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                            AMARAN: Data kalah mati (beserta skor) yang sedia ada untuk kategori ini akan dipadam dan dijana semula secara automatik jika anda meneruskan.
                        </div>
                    @endif

                    <div class="space-y-3">
                        <button wire:click="generateKnockout({{ $cat->id }}, false)" class="w-full bg-primary hover:bg-primary/90 text-white font-bold py-4 rounded-2xl shadow-lg shadow-primary/20 transition-all flex flex-col items-center justify-center gap-1">
                            <span>Jana Pusingan Trofi Sahaja</span>
                            <span class="text-xs font-medium text-white/70">(Juara & Naib Juara Kumpulan sahaja)</span>
                        </button>
                        
                        <button wire:click="generateKnockout({{ $cat->id }}, true)" class="w-full bg-secondary hover:bg-secondary/90 text-white font-bold py-4 rounded-2xl shadow-lg shadow-secondary/20 transition-all flex flex-col items-center justify-center gap-1">
                            <span>Jana Trofi & Piala (Khas U12)</span>
                            <span class="text-xs font-medium text-white/70">(Trofi: Top 2 | Piala: Tempat 3 & 4)</span>
                        </button>
                    </div>
                </div>

                <div class="p-4 border-t border-base-200 bg-base-50 flex justify-end">
                    <button wire:click="$set('selectedCategory', null)" class="px-6 py-2.5 bg-base-200 hover:bg-base-300 text-base-content font-bold rounded-xl transition-colors text-sm">
                        Batal
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
