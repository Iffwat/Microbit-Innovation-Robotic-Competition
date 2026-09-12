<?php

use Livewire\Component;
use App\Models\Category;
use App\Models\TournamentMatch;
use Illuminate\Support\Facades\DB;

new class extends Component {
    public Category $category;
    public $activeTab = 'trophy_knockout';

    // Swap / Reassign Modal State
    public bool $showSwapModal = false;
    public ?int $sourceMatchId = null;
    public string $sourceSlot = 'away'; // 'home' or 'away'
    public ?string $selectedTargetKey = null; // format: "{match_id}:{slot}"
    public bool $forceResetScores = false;
    
    public function mount(Category $category)
    {
        $this->category = $category;
    }

    public function getMatchesProperty()
    {
        return TournamentMatch::with(['homeTeam', 'awayTeam', 'winner'])
            ->where('category_id', $this->category->id)
            ->where('stage', $this->activeTab)
            ->orderBy('id')
            ->get()
            ->groupBy('round_name');
    }

    public function updateField($matchId, $fieldNumber)
    {
        TournamentMatch::where('id', $matchId)->update(['field_number' => $fieldNumber ?: null]);
    }

    public function updateTime($matchId, $time)
    {
        TournamentMatch::where('id', $matchId)->update(['scheduled_time' => $time ?: null]);
    }

    public function openSwapModal(int $matchId, string $slot = 'away'): void
    {
        $match = TournamentMatch::with(['homeTeam', 'awayTeam'])->find($matchId);
        if (!$match) return;

        $this->sourceMatchId = $matchId;
        if ($match->status === 'bye' || !$match->away_team_id) {
            $this->sourceSlot = 'home';
        } else {
            $this->sourceSlot = $slot;
        }

        $this->selectedTargetKey = null;
        $this->forceResetScores = false;
        $this->showSwapModal = true;
    }

    public function closeSwapModal(): void
    {
        $this->showSwapModal = false;
        $this->sourceMatchId = null;
        $this->selectedTargetKey = null;
        $this->forceResetScores = false;
    }

    public function getSourceMatchProperty()
    {
        if (!$this->sourceMatchId) return null;
        return TournamentMatch::with(['homeTeam', 'awayTeam'])->find($this->sourceMatchId);
    }

    public function getSelectedSourceTeamProperty()
    {
        $m = $this->sourceMatch;
        if (!$m) return null;
        return ($this->sourceSlot === 'home') ? $m->homeTeam : $m->awayTeam;
    }

    public function getSwapCandidatesProperty()
    {
        if (!$this->sourceMatchId) return collect();
        $sourceMatch = $this->sourceMatch;
        if (!$sourceMatch) return collect();

        $matchesInRound = TournamentMatch::with(['homeTeam', 'awayTeam'])
            ->where('category_id', $this->category->id)
            ->where('stage', $this->activeTab)
            ->where('round_name', $sourceMatch->round_name)
            ->orderBy('bracket_position')
            ->get();

        $candidates = [];
        foreach ($matchesInRound as $m) {
            // Home slot
            if ($m->home_team_id && $m->homeTeam) {
                if (!($m->id === $sourceMatch->id && $this->sourceSlot === 'home')) {
                    $candidates[] = [
                        'match_id' => $m->id,
                        'slot' => 'home',
                        'bracket_position' => $m->bracket_position,
                        'team_id' => $m->home_team_id,
                        'team_name' => $m->homeTeam->team_name,
                        'school_name' => $m->homeTeam->school_name,
                        'is_bye' => ($m->status === 'bye'),
                        'status' => $m->status,
                        'is_same_match' => ($m->id === $sourceMatch->id),
                    ];
                }
            }

            // Away slot (only for non-bye)
            if ($m->status !== 'bye' && $m->away_team_id && $m->awayTeam) {
                if (!($m->id === $sourceMatch->id && $this->sourceSlot === 'away')) {
                    $candidates[] = [
                        'match_id' => $m->id,
                        'slot' => 'away',
                        'bracket_position' => $m->bracket_position,
                        'team_id' => $m->away_team_id,
                        'team_name' => $m->awayTeam->team_name,
                        'school_name' => $m->awayTeam->school_name,
                        'is_bye' => false,
                        'status' => $m->status,
                        'is_same_match' => ($m->id === $sourceMatch->id),
                    ];
                }
            }
        }

        return collect($candidates);
    }

    public function getSelectedCandidateProperty()
    {
        if (!$this->selectedTargetKey) return null;
        return $this->swapCandidates->first(function($c) {
            return ($c['match_id'] . ':' . $c['slot']) === $this->selectedTargetKey;
        });
    }

    public function executeSwap(): void
    {
        if (!$this->sourceMatchId || !$this->selectedTargetKey) {
            session()->flash('swap_error', 'Sila pilih sasaran pasukan untuk ditukar.');
            return;
        }

        $parts = explode(':', $this->selectedTargetKey);
        if (count($parts) !== 2) {
            session()->flash('swap_error', 'Format sasaran pertukaran tidak sah.');
            return;
        }

        $targetMatchId = (int)$parts[0];
        $targetSlot = $parts[1];

        $sourceMatch = TournamentMatch::find($this->sourceMatchId);
        $targetMatch = TournamentMatch::find($targetMatchId);

        if (!$sourceMatch || !$targetMatch) {
            session()->flash('swap_error', 'Perlawanan tidak dijumpai.');
            return;
        }

        $hasScoresOrCompleted = ($sourceMatch->status === 'completed' || $targetMatch->status === 'completed' ||
                                 $sourceMatch->status === 'in_progress' || $targetMatch->status === 'in_progress' ||
                                 $sourceMatch->home_score !== null || $targetMatch->home_score !== null);

        if ($hasScoresOrCompleted && !$this->forceResetScores) {
            session()->flash('swap_error', 'Perlawanan ini telah dimainkan atau mempunyai skor. Sila tanda kotak pengesahan jika ingin set semula skor dan teruskan pertukaran.');
            return;
        }

        $sourceTeamId = ($this->sourceSlot === 'home') ? $sourceMatch->home_team_id : $sourceMatch->away_team_id;
        $targetTeamId = ($targetSlot === 'home') ? $targetMatch->home_team_id : $targetMatch->away_team_id;

        if (!$sourceTeamId || !$targetTeamId) {
            session()->flash('swap_error', 'Pasukan tidak dijumpai dalam slot yang dipilih.');
            return;
        }

        DB::transaction(function () use ($sourceMatch, $targetMatch, $targetSlot, $sourceTeamId, $targetTeamId, $hasScoresOrCompleted) {
            // Case 1: Swapping inside the SAME match (Home <-> Away)
            if ($sourceMatch->id === $targetMatch->id) {
                $sourceMatch->home_team_id = $targetTeamId;
                $sourceMatch->away_team_id = $sourceTeamId;
                if ($hasScoresOrCompleted) {
                    $sourceMatch->home_score = null;
                    $sourceMatch->away_score = null;
                    $sourceMatch->winner_team_id = null;
                    $sourceMatch->status = 'scheduled';
                }
                $sourceMatch->save();
                return;
            }

            // Case 2: Swapping between two different matches
            // 2a. Update Source Match
            if ($this->sourceSlot === 'home') {
                $sourceMatch->home_team_id = $targetTeamId;
            } else {
                $sourceMatch->away_team_id = $targetTeamId;
            }

            if ($sourceMatch->status === 'bye') {
                $sourceMatch->winner_team_id = $targetTeamId;
            } elseif ($hasScoresOrCompleted) {
                $sourceMatch->home_score = null;
                $sourceMatch->away_score = null;
                $sourceMatch->winner_team_id = null;
                $sourceMatch->status = 'scheduled';
            }
            $sourceMatch->save();

            // If source was BYE, advance the new seed!
            if ($sourceMatch->status === 'bye') {
                $sourceMatch->advanceKnockoutWinner();
            }

            // 2b. Update Target Match
            if ($targetSlot === 'home') {
                $targetMatch->home_team_id = $sourceTeamId;
            } else {
                $targetMatch->away_team_id = $sourceTeamId;
            }

            if ($targetMatch->status === 'bye') {
                $targetMatch->winner_team_id = $sourceTeamId;
            } elseif ($hasScoresOrCompleted) {
                $targetMatch->home_score = null;
                $targetMatch->away_score = null;
                $targetMatch->winner_team_id = null;
                $targetMatch->status = 'scheduled';
            }
            $targetMatch->save();

            // If target was BYE, advance the new seed!
            if ($targetMatch->status === 'bye') {
                $targetMatch->advanceKnockoutWinner();
            }
        });

        session()->flash('success', 'Pertukaran pasukan dalam bracket berjaya dikemaskini!');
        $this->closeSwapModal();
    }
};
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.knockout.index') }}" class="btn btn-sm btn-ghost px-2">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                </a>
                <h2 class="text-2xl font-bold">{{ $category->name }}</h2>
            </div>
            <p class="text-base-content/60 mt-1 ml-10">{{ __('Urus padang, jadual masa, dan susunan perlawanan kalah mati.') }}</p>
        </div>
    </div>

    <!-- Alerts -->
    @if(session('success'))
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-5 py-4 rounded-2xl flex items-center justify-between shadow-sm animate-fade-in">
            <div class="flex items-center gap-3">
                <svg class="w-5 h-5 text-emerald-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                <span class="font-bold text-sm">{{ session('success') }}</span>
            </div>
            <button type="button" onclick="this.parentElement.remove()" class="text-emerald-600 hover:text-emerald-900 font-bold text-sm">✕</button>
        </div>
    @endif

    <!-- Tabs -->
    @php
        $hasCup = \App\Models\TournamentMatch::where('category_id', $category->id)->where('stage', 'cup_knockout')->exists();
    @endphp
    
    @if($hasCup)
    <div class="tabs tabs-boxed bg-base-200 w-fit p-1 rounded-xl">
        <button wire:click="$set('activeTab', 'trophy_knockout')" class="tab tab-lg rounded-lg font-bold {{ $activeTab === 'trophy_knockout' ? 'tab-active bg-primary text-white' : '' }}">
            🏆 {{ __('Pusingan Trofi') }}
        </button>
        <button wire:click="$set('activeTab', 'cup_knockout')" class="tab tab-lg rounded-lg font-bold {{ $activeTab === 'cup_knockout' ? 'tab-active bg-primary text-white' : '' }}">
            🥈 {{ __('Pusingan Piala') }}
        </button>
    </div>
    @endif

    <!-- Bracket Manager View -->
    <div class="space-y-8">
        @forelse($this->matches as $roundName => $roundMatches)
            <div class="bg-white rounded-3xl border border-base-200 shadow-sm overflow-hidden">
                <div class="bg-base-100 px-6 py-4 border-b border-base-200 flex items-center justify-between">
                    <h3 class="text-xl font-bold text-base-content">{{ __($roundName) }}</h3>
                    <span class="text-xs font-semibold bg-base-200 text-base-content/60 px-3 py-1 rounded-full">{{ count($roundMatches) }} {{ __('Perlawanan') }}</span>
                </div>
                
                <div class="p-6 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6">
                    @foreach($roundMatches as $match)
                        @php
                            $sameSchool = false;
                            if ($match->homeTeam && $match->awayTeam && $match->status !== 'bye') {
                                $hSchool = trim(strtolower($match->homeTeam->school_name ?? ''));
                                $aSchool = trim(strtolower($match->awayTeam->school_name ?? ''));
                                if (!empty($hSchool) && !empty($aSchool) && $hSchool === $aSchool) {
                                    $sameSchool = true;
                                }
                            }
                        @endphp

                        <div class="border-2 rounded-2xl bg-white flex flex-col relative pt-5 shadow-sm hover:shadow-md transition-shadow {{ $sameSchool ? 'border-amber-400 ring-2 ring-amber-300/40 bg-amber-50/10' : ($match->winner_team_id ? 'border-emerald-300' : 'border-base-200') }}">
                            
                            <!-- Match ID Badge -->
                            <div class="absolute -top-3 left-1/2 -translate-x-1/2 bg-primary px-4 py-1 rounded-full text-xs font-extrabold text-white shadow-sm whitespace-nowrap">
                                {{ __('Perlawanan') }} {{ $match->bracket_position }}
                            </div>

                            <!-- Same School Clash Warning Banner -->
                            @if($sameSchool)
                                <div class="mx-4 mt-2 px-3 py-2 bg-amber-50 border-2 border-amber-300 rounded-xl text-amber-900 text-xs flex items-center justify-between gap-2 shadow-sm animate-pulse">
                                    <div class="flex items-center gap-1.5 font-bold truncate">
                                        <span class="text-amber-600 text-base shrink-0">⚠️</span>
                                        <div class="truncate">
                                            <div class="leading-tight text-[11px] font-black">Konflik Sekolah Sama</div>
                                            <div class="text-[10px] font-semibold text-amber-700 truncate">{{ $match->homeTeam->school_name }}</div>
                                        </div>
                                    </div>
                                    <button type="button" wire:click="openSwapModal({{ $match->id }}, 'away')" class="btn btn-xs bg-amber-500 hover:bg-amber-600 text-white border-0 rounded-lg shrink-0 font-bold shadow-sm">
                                        🔄 Tukar
                                    </button>
                                </div>
                            @endif

                            <!-- Teams -->
                            <div class="p-4 flex flex-col relative z-10">
                                <!-- Home Team -->
                                <div class="flex justify-between items-center px-4 py-3 rounded-t-xl border border-base-200 border-b-0 {{ ($match->winner_team_id === $match->home_team_id || $match->status === 'bye') ? 'bg-emerald-50 border-emerald-200' : 'bg-base-50' }}">
                                    <div class="truncate pr-2 min-w-0">
                                        <div class="font-bold text-sm truncate {{ $match->home_team_id ? 'text-base-content' : 'text-base-content/40 italic' }}">
                                            {{ $match->homeTeam->team_name ?? __('Menunggu...') }}
                                        </div>
                                        @if($match->homeTeam && $match->homeTeam->school_name)
                                            <div class="text-[11px] text-base-content/50 font-medium truncate mt-0.5">
                                                🏫 {{ $match->homeTeam->school_name }}
                                            </div>
                                        @endif
                                    </div>
                                    <div class="font-black text-lg shrink-0 ml-2 {{ $match->status === 'bye' ? 'text-emerald-600' : ($match->home_score !== null ? 'text-primary' : 'text-base-content/20') }}">
                                        {{ $match->status === 'bye' ? '✓' : ($match->home_score ?? '-') }}
                                    </div>
                                </div>
                                
                                <!-- VS Badge Divider -->
                                <div class="relative h-px bg-base-200 w-full z-20">
                                    <div class="absolute left-1/2 -translate-x-1/2 -translate-y-1/2 bg-white border border-base-200 px-2 py-0.5 rounded-md text-[10px] font-bold text-base-content/40 tracking-wider">
                                        {{ $match->status === 'bye' ? 'BYE' : 'VS' }}
                                    </div>
                                </div>

                                <!-- Away Team -->
                                <div class="flex justify-between items-center px-4 py-3 rounded-b-xl border border-base-200 border-t-0 {{ $match->winner_team_id === $match->away_team_id ? 'bg-emerald-50 border-emerald-200' : 'bg-base-50' }}">
                                    <div class="truncate pr-2 min-w-0">
                                        @if($match->status === 'bye')
                                            <span class="text-xs font-black text-emerald-700 bg-emerald-100/70 px-2 py-0.5 rounded-full uppercase tracking-wider">BYE (Laluan Percuma)</span>
                                        @else
                                            <div class="font-bold text-sm truncate {{ $match->away_team_id ? 'text-base-content' : 'text-base-content/40 italic' }}">
                                                {{ $match->awayTeam->team_name ?? __('Menunggu...') }}
                                            </div>
                                            @if($match->awayTeam && $match->awayTeam->school_name)
                                                <div class="text-[11px] text-base-content/50 font-medium truncate mt-0.5">
                                                    🏫 {{ $match->awayTeam->school_name }}
                                                </div>
                                            @endif
                                        @endif
                                    </div>
                                    <div class="font-black text-lg shrink-0 ml-2 {{ $match->away_score !== null ? 'text-primary' : 'text-base-content/20' }}">
                                        {{ $match->status === 'bye' ? '-' : ($match->away_score ?? '-') }}
                                    </div>
                                </div>
                            </div>

                            <!-- Settings (Field & Swap) -->
                            <div class="p-4 border-t border-base-100 mt-auto rounded-b-2xl bg-base-50/50">
                                @if($match->status === 'bye')
                                    <div class="flex items-center justify-between gap-2">
                                        <div class="text-xs font-bold text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-xl py-2 px-3 text-center flex items-center justify-center gap-1.5 flex-1">
                                            <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                            <span>{{ __('Layak Terus ke Pusingan ke-16') }}</span>
                                        </div>
                                        @if($match->home_team_id)
                                            <button type="button" wire:click="openSwapModal({{ $match->id }}, 'home')" class="btn btn-sm bg-white hover:bg-base-100 border-2 border-base-200 rounded-xl px-2.5 text-xs font-bold text-base-content/70 shadow-sm" title="Tukar Pasukan BYE">
                                                🔄 Tukar
                                            </button>
                                        @endif
                                    </div>
                                @else
                                    <div class="flex items-center gap-2">
                                        <div class="flex-1">
                                            <select wire:change="updateField({{ $match->id }}, $event.target.value)" class="w-full bg-white border-2 border-base-200 rounded-xl px-3 py-2 text-sm font-bold text-base-content/70 focus:ring-4 focus:ring-primary/20 focus:border-primary transition-all">
                                                <option value="">-- {{ __('Tetapkan Padang') }} --</option>
                                                @for($i=1; $i<=15; $i++)
                                                    <option value="{{ $i }}" {{ $match->field_number == $i ? 'selected' : '' }}>{{ __('Padang') }} {{ $i }}</option>
                                                @endfor
                                                <option value="Arena Sky Soccer" {{ $match->field_number === 'Arena Sky Soccer' ? 'selected' : '' }}>Arena Sky Soccer</option>
                                            </select>
                                        </div>
                                        @if($match->home_team_id || $match->away_team_id)
                                            <button type="button" wire:click="openSwapModal({{ $match->id }}, 'away')" class="btn btn-sm bg-white hover:bg-primary/10 text-primary border-2 border-base-200 rounded-xl px-2.5 font-bold flex items-center gap-1 shrink-0 shadow-sm" title="Tukar Pasukan / Padankan Semula">
                                                <span>🔄</span>
                                                <span class="text-xs">Tukar</span>
                                            </button>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @empty
            <div class="text-center py-12 bg-base-100 rounded-3xl border border-base-200">
                <h3 class="text-lg font-bold text-base-content/50">{{ __('Tiada perlawanan dijumpai untuk pusingan ini.') }}</h3>
            </div>
        @endforelse
    </div>

    <!-- Swap / Reassign Modal -->
    @if($showSwapModal && $this->sourceMatch)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4 animate-fade-in" style="margin-top: 0;">
            <div class="bg-white rounded-3xl max-w-xl w-full shadow-2xl border border-base-200 overflow-hidden flex flex-col max-h-[90vh]">
                
                {{-- Modal Header --}}
                <div class="px-6 py-4 bg-gradient-to-r from-primary to-blue-700 text-white flex items-center justify-between shrink-0">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-white/20 flex items-center justify-center text-lg">🔄</div>
                        <div>
                            <h3 class="font-bold text-base">Tukar Pasukan Dalam Bracket</h3>
                            <p class="text-xs text-white/80">{{ $this->sourceMatch->round_name }} &bull; Perlawanan {{ $this->sourceMatch->bracket_position }}</p>
                        </div>
                    </div>
                    <button type="button" wire:click="closeSwapModal" class="btn btn-sm btn-circle btn-ghost text-white hover:bg-white/20">✕</button>
                </div>

                {{-- Modal Body --}}
                <div class="p-6 space-y-5 overflow-y-auto flex-1">
                    @if(session()->has('swap_error'))
                        <div class="p-3 bg-red-50 border border-red-200 rounded-xl text-xs text-red-700 font-bold flex items-center gap-2">
                            <span>⚠️</span>
                            <span>{{ session('swap_error') }}</span>
                        </div>
                    @endif

                    {{-- Step 1: Pilih Pasukan Dari Perlawanan Ini --}}
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-base-content/60 mb-2">
                            1. Pilih Pasukan Yang Ingin Ditukar:
                        </label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            @if($this->sourceMatch->homeTeam)
                                <label class="flex items-center gap-3 p-3 rounded-2xl border-2 cursor-pointer transition-all {{ $sourceSlot === 'home' ? 'border-primary bg-primary/5 ring-2 ring-primary/20' : 'border-base-200 hover:border-base-300' }}">
                                    <input type="radio" wire:model.live="sourceSlot" value="home" class="radio radio-primary radio-sm">
                                    <div class="min-w-0">
                                        <span class="text-[10px] font-extrabold uppercase px-1.5 py-0.5 rounded bg-base-200 text-base-content/60">Tuan Rumah</span>
                                        <p class="font-bold text-sm text-base-content truncate mt-0.5">{{ $this->sourceMatch->homeTeam->team_name }}</p>
                                        <p class="text-[11px] text-base-content/50 truncate">🏫 {{ $this->sourceMatch->homeTeam->school_name }}</p>
                                    </div>
                                </label>
                            @endif

                            @if($this->sourceMatch->awayTeam && $this->sourceMatch->status !== 'bye')
                                <label class="flex items-center gap-3 p-3 rounded-2xl border-2 cursor-pointer transition-all {{ $sourceSlot === 'away' ? 'border-primary bg-primary/5 ring-2 ring-primary/20' : 'border-base-200 hover:border-base-300' }}">
                                    <input type="radio" wire:model.live="sourceSlot" value="away" class="radio radio-primary radio-sm">
                                    <div class="min-w-0">
                                        <span class="text-[10px] font-extrabold uppercase px-1.5 py-0.5 rounded bg-base-200 text-base-content/60">Pelawat</span>
                                        <p class="font-bold text-sm text-base-content truncate mt-0.5">{{ $this->sourceMatch->awayTeam->team_name }}</p>
                                        <p class="text-[11px] text-base-content/50 truncate">🏫 {{ $this->sourceMatch->awayTeam->school_name }}</p>
                                    </div>
                                </label>
                            @endif
                        </div>
                    </div>

                    {{-- Step 2: Pilih Sasaran Pertukaran --}}
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="block text-xs font-bold uppercase tracking-wider text-base-content/60">
                                2. Tukar Dengan Pasukan Mana? (Pusingan Sama: {{ $this->sourceMatch->round_name }}):
                            </label>
                            <span class="text-[11px] font-bold text-primary">{{ $this->swapCandidates->count() }} Pilihan</span>
                        </div>
                        
                        @if($this->swapCandidates->isEmpty())
                            <div class="p-4 bg-base-100 border border-base-200 rounded-2xl text-center text-xs text-base-content/50">
                                Tiada perlawanan lain dalam pusingan ini yang boleh ditukar.
                            </div>
                        @else
                            <div class="space-y-2 max-h-56 overflow-y-auto pr-1">
                                @foreach($this->swapCandidates as $cand)
                                    @php
                                        $candKey = $cand['match_id'] . ':' . $cand['slot'];
                                        $isSelected = ($selectedTargetKey === $candKey);
                                        // Check if target candidate creates clash with source match's other team
                                        $sourceOpponent = ($sourceSlot === 'home') ? $this->sourceMatch->awayTeam : $this->sourceMatch->homeTeam;
                                        $createsConflict = false;
                                        if ($sourceOpponent && !empty($cand['school_name'])) {
                                            $createsConflict = (trim(strtolower($sourceOpponent->school_name)) === trim(strtolower($cand['school_name'])));
                                        }
                                    @endphp
                                    <label class="flex items-center gap-3 p-3 rounded-2xl border-2 cursor-pointer transition-all {{ $isSelected ? 'border-primary bg-primary/5 ring-2 ring-primary/20' : 'border-base-200 hover:border-base-300' }}">
                                        <input type="radio" wire:model.live="selectedTargetKey" value="{{ $candKey }}" class="radio radio-primary radio-sm">
                                        <div class="min-w-0 flex-1">
                                            <div class="flex items-center gap-2 flex-wrap">
                                                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full {{ $cand['is_bye'] ? 'bg-emerald-100 text-emerald-700' : 'bg-base-200 text-base-content/60' }}">
                                                    Perlawanan {{ $cand['bracket_position'] }} &bull; {{ $cand['slot'] === 'home' ? 'Tuan Rumah' : 'Pelawat' }}
                                                    {{ $cand['is_bye'] ? '(BYE)' : '' }}
                                                </span>
                                                @if($cand['is_same_match'])
                                                    <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-blue-100 text-blue-700">
                                                        🔁 Tukar Sisi Sendiri
                                                    </span>
                                                @endif
                                                @if($createsConflict && !$cand['is_same_match'])
                                                    <span class="text-[10px] font-extrabold px-1.5 py-0.5 rounded bg-red-100 text-red-700">
                                                        ⚠️ Sama Sekolah Dgn Lawan
                                                    </span>
                                                @endif
                                            </div>
                                            <p class="font-bold text-sm text-base-content truncate mt-0.5">{{ $cand['team_name'] }}</p>
                                            <p class="text-[11px] text-base-content/50 truncate">🏫 {{ $cand['school_name'] }}</p>
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    {{-- Step 3: Summary of Swap --}}
                    @if($selectedTargetKey && $this->selectedCandidate)
                        <div class="p-4 bg-emerald-50 border border-emerald-200 rounded-2xl text-xs text-emerald-800 space-y-1.5">
                            <p class="font-bold text-sm flex items-center gap-1.5">
                                <span>📋</span>
                                <span>Ringkasan Pertukaran:</span>
                            </p>
                            <div class="pl-2 space-y-1">
                                <p>
                                    &bull; <strong>{{ $this->selectedSourceTeam->team_name ?? '' }}</strong> akan berpindah ke <strong>Perlawanan {{ $this->selectedCandidate['bracket_position'] }} ({{ $this->selectedCandidate['slot'] === 'home' ? 'Tuan Rumah' : 'Pelawat' }})</strong>
                                </p>
                                <p>
                                    &bull; <strong>{{ $this->selectedCandidate['team_name'] }}</strong> akan berpindah ke <strong>Perlawanan {{ $this->sourceMatch->bracket_position }} ({{ $sourceSlot === 'home' ? 'Tuan Rumah' : 'Pelawat' }})</strong>
                                </p>
                            </div>
                        </div>

                        {{-- Score reset confirmation checkbox if completed or in-progress --}}
                        @php
                            $targetM = \App\Models\TournamentMatch::find($this->selectedCandidate['match_id']);
                            $needsReset = ($this->sourceMatch->status === 'completed' || ($targetM && $targetM->status === 'completed') ||
                                           $this->sourceMatch->status === 'in_progress' || ($targetM && $targetM->status === 'in_progress') ||
                                           $this->sourceMatch->home_score !== null || ($targetM && $targetM->home_score !== null));
                        @endphp

                        @if($needsReset)
                            <div class="p-3 bg-amber-50 border border-amber-200 rounded-2xl">
                                <label class="flex items-start gap-2.5 cursor-pointer">
                                    <input type="checkbox" wire:model.live="forceResetScores" class="checkbox checkbox-warning checkbox-sm mt-0.5">
                                    <div class="text-xs text-amber-900 font-medium">
                                        <strong class="text-amber-800">Perlawanan mempunyai rekod skor / telah selesai:</strong>
                                        <p class="text-[11px] text-amber-700 mt-0.5">Tandakan ini untuk mengesahkan bahawa skor perlawanan akan diset semula kepada jadual baharu.</p>
                                    </div>
                                </label>
                            </div>
                        @endif
                    @endif
                </div>

                {{-- Modal Footer --}}
                <div class="px-6 py-4 bg-base-100 border-t border-base-200 flex justify-end gap-3 shrink-0">
                    <button type="button" wire:click="closeSwapModal" class="btn btn-sm btn-ghost rounded-xl">Batal</button>
                    <button type="button" wire:click="executeSwap" wire:loading.attr="disabled"
                            class="btn btn-sm bg-primary hover:bg-primary/90 text-white rounded-xl font-bold {{ !$selectedTargetKey ? 'btn-disabled opacity-50' : '' }}">
                        <span wire:loading.remove wire:target="executeSwap">🔄 Sahkan Pertukaran</span>
                        <span wire:loading wire:target="executeSwap">Menukar...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
