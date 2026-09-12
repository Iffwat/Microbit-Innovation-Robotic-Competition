<?php

use Livewire\Component;
use App\Models\Category;
use App\Models\TournamentMatch;

new class extends Component {
    public Category $category;
    public $activeTab = 'trophy_knockout';
    
    // We don't use wire:model directly on the models to avoid hydration issues with lots of matches
    // Instead we use discrete actions
    
    public function mount(Category $category)
    {
        $this->category = $category;
    }

    public function getMatchesProperty()
    {
        return TournamentMatch::with(['homeTeam', 'awayTeam', 'winner'])
            ->where('category_id', $this->category->id)
            ->where('stage', $this->activeTab)
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
            <p class="text-base-content/60 mt-1 ml-10">{{ __('Urus padang dan jadual masa untuk setiap perlawanan kalah mati.') }}</p>
        </div>
    </div>

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
                <div class="bg-base-100 px-6 py-4 border-b border-base-200">
                    <h3 class="text-xl font-bold text-base-content">{{ __($roundName) }}</h3>
                </div>
                
                <div class="p-6 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6">
                    @foreach($roundMatches as $match)
                        <div class="border-2 border-base-200 rounded-2xl bg-white flex flex-col relative pt-5 shadow-sm hover:shadow-md transition-shadow {{ $match->winner_team_id ? 'border-emerald-300' : '' }}">
                            
                            <!-- Match ID Badge -->
                            <div class="absolute -top-3 left-1/2 -translate-x-1/2 bg-primary px-4 py-1 rounded-full text-xs font-extrabold text-white shadow-sm whitespace-nowrap">
                                {{ __('Perlawanan') }} {{ $match->bracket_position }}
                            </div>

                            <!-- Teams -->
                            <div class="p-4 flex flex-col relative z-10">
                                <!-- Home Team -->
                                <div class="flex justify-between items-center px-4 py-3 rounded-t-xl border border-base-200 border-b-0 {{ ($match->winner_team_id === $match->home_team_id || $match->status === 'bye') ? 'bg-emerald-50 border-emerald-200' : 'bg-base-50' }}">
                                    <div class="font-bold text-sm truncate pr-2 {{ $match->home_team_id ? '' : 'text-base-content/40 italic' }}">
                                        {{ $match->homeTeam->team_name ?? __('Menunggu...') }}
                                    </div>
                                    <div class="font-black text-lg {{ $match->status === 'bye' ? 'text-emerald-600' : ($match->home_score !== null ? 'text-primary' : 'text-base-content/20') }}">
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
                                    <div class="font-bold text-sm truncate pr-2 {{ ($match->away_team_id || $match->status === 'bye') ? '' : 'text-base-content/40 italic' }}">
                                        @if($match->status === 'bye')
                                            <span class="text-xs font-black text-emerald-700 bg-emerald-100/70 px-2 py-0.5 rounded-full uppercase tracking-wider">BYE (Laluan Percuma)</span>
                                        @else
                                            {{ $match->awayTeam->team_name ?? __('Menunggu...') }}
                                        @endif
                                    </div>
                                    <div class="font-black text-lg {{ $match->away_score !== null ? 'text-primary' : 'text-base-content/20' }}">
                                        {{ $match->status === 'bye' ? '-' : ($match->away_score ?? '-') }}
                                    </div>
                                </div>
                            </div>

                            <!-- Settings (Field) -->
                            <div class="p-4 border-t border-base-100 mt-auto rounded-b-2xl bg-base-50/50">
                                @if($match->status === 'bye')
                                    <div class="text-xs font-bold text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-xl py-2 px-3 text-center flex items-center justify-center gap-1.5">
                                        <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        <span>{{ __('Layak Terus ke Pusingan ke-16') }}</span>
                                    </div>
                                @else
                                    <select wire:change="updateField({{ $match->id }}, $event.target.value)" class="w-full bg-white border-2 border-base-200 rounded-xl px-3 py-2.5 text-sm font-bold text-base-content/70 focus:ring-4 focus:ring-primary/20 focus:border-primary transition-all">
                                        <option value="">-- {{ __('Tetapkan Padang') }} --</option>
                                        @for($i=1; $i<=15; $i++)
                                            <option value="{{ $i }}" {{ $match->field_number == $i ? 'selected' : '' }}>{{ __('Padang') }} {{ $i }}</option>
                                        @endfor
                                        <option value="Arena Sky Soccer" {{ $match->field_number === 'Arena Sky Soccer' ? 'selected' : '' }}>Arena Sky Soccer</option>
                                    </select>
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
</div>
