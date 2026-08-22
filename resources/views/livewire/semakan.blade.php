<?php

use App\Models\Team;
use App\Models\Group;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;

new class extends Component {
    
    #[Url(as: 'q')]
    public string $search = '';

    public ?int $viewGroupId = null;

    public function viewGroup($groupId)
    {
        $this->viewGroupId = $groupId;
        $this->dispatch('open-group-modal');
    }

    public function closeGroupModal()
    {
        $this->viewGroupId = null;
        $this->dispatch('close-group-modal');
    }

    #[Computed]
    public function selectedGroup()
    {
        if (!$this->viewGroupId) return null;
        return Group::with(['groupTeams.team'])->find($this->viewGroupId);
    }

    #[Computed]
    public function teams()
    {
        if (strlen(trim($this->search)) < 3) {
            return collect();
        }
        
        return Team::with(['category', 'groupTeams.group'])
            ->where(function ($q) {
                $q->where('team_name', 'like', '%' . $this->search . '%')
                  ->orWhere('school_name', 'like', '%' . $this->search . '%');
            })
            ->orderBy('school_name')
            ->orderBy('team_name')
            ->limit(50)
            ->get();
    }
};
?>

<div class="space-y-8 animate-slide-up">
    {{-- Tunjuk poster jika carian kosong --}}
    @if(strlen(trim($search)) < 3)
    <div class="flex justify-center">
        <img src="{{ asset('images/Poster.jpeg') }}" alt="Poster MIRC" class="rounded-3xl shadow-xl max-w-full h-auto md:max-w-3xl border-4 border-white transition-all hover:scale-[1.02]">
    </div>
    @endif

    {{-- Search Hero --}}
    <div class="relative overflow-hidden bg-gradient-to-br from-primary via-[#1e3a8a] to-secondary rounded-3xl p-8 md:p-12 text-white text-center">
        <div class="absolute inset-0 pointer-events-none">
            <div class="absolute -top-10 -right-10 w-64 h-64 bg-white/5 rounded-full blur-3xl"></div>
            <div class="absolute -bottom-10 -left-10 w-48 h-48 bg-accent/10 rounded-full blur-3xl"></div>
        </div>
        <div class="relative">
            <h2 class="text-2xl md:text-3xl font-extrabold mb-2">Portal Semakan Pasukan</h2>
            <p class="text-white/70 text-sm mb-8">Taip nama sekolah atau nama pasukan anda untuk melihat status pendaftaran.</p>
            <div class="w-full max-w-xl mx-auto relative">
                <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-white/50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input wire:model.live.debounce.300ms="search" type="text"
                       placeholder="Contoh: SK Bandar Baru..."
                       class="w-full pl-12 pr-4 py-4 bg-white/15 backdrop-blur-sm border-2 border-white/30 rounded-2xl text-white placeholder:text-white/50 text-base focus:outline-none focus:border-white/60 focus:bg-white/20 transition-all"
                       autofocus />
                <div wire:loading.flex wire:target="search" class="absolute right-4 top-1/2 -translate-y-1/2 items-center">
                    <svg class="animate-spin w-5 h-5 text-white/70" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                </div>
            </div>
            @if(strlen(trim($search)) > 0 && strlen(trim($search)) < 3)
                <p class="text-white/60 text-sm mt-3">Sila taip sekurang-kurangnya 3 huruf.</p>
            @endif
        </div>
    </div>

    {{-- Results --}}
    @if(strlen(trim($search)) >= 3)
        @if($this->teams->count() > 0)
            <div class="flex items-center gap-3 mb-2">
                <h3 class="text-base font-bold text-base-content">Hasil Carian</h3>
                <span class="text-xs font-semibold bg-primary/10 text-primary px-2.5 py-1 rounded-full">{{ $this->teams->count() }} pasukan</span>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach($this->teams as $team)
                @php
                    // Get the group matching this team's game_type
                    $teamGroup = $team->groupTeams
                        ->filter(fn($gt) => optional($gt->group)->game_type === $team->game_type)
                        ->first();
                    $groupLetter = $teamGroup?->group?->group_letter;
                    $groupName   = $teamGroup?->group?->group_name;

                    // Color per game type
                    $gameColors = [
                        'isobot'     => ['bg' => 'bg-blue-600',   'light' => 'bg-blue-50 text-blue-700 border-blue-200'],
                        'sky_soccer' => ['bg' => 'bg-violet-600', 'light' => 'bg-violet-50 text-violet-700 border-violet-200'],
                        'obstacle'   => ['bg' => 'bg-amber-500',  'light' => 'bg-amber-50 text-amber-700 border-amber-200'],
                    ];
                    $gc = $gameColors[$team->game_type] ?? ['bg' => 'bg-primary', 'light' => 'bg-primary/10 text-primary border-primary/20'];
                @endphp
                <div wire:key="team-{{ $team->id }}"
                     class="bg-white rounded-2xl border border-base-200 shadow-sm hover:shadow-md hover:border-primary/30 transition-all overflow-hidden">

                    {{-- Top bar with game colour --}}
                    <div class="h-1.5 w-full {{ $gc['bg'] }}"></div>

                    <div class="p-5">
                        {{-- Header row: team name + group badge --}}
                        <div class="flex items-start justify-between gap-3 mb-4">
                            <div class="flex-1 min-w-0">
                                <h4 class="font-extrabold text-base-content text-base leading-tight">{{ $team->team_name }}</h4>
                                <p class="text-xs text-base-content/50 mt-1">🏫 {{ $team->school_name }}</p>
                            </div>

                            {{-- Prominent group letter --}}
                            @if($groupLetter)
                                <div class="flex flex-col items-center shrink-0 group" wire:click="viewGroup({{ $teamGroup->group->id }})" role="button">
                                    <div class="{{ $gc['bg'] }} group-hover:bg-opacity-80 text-white w-14 h-14 rounded-2xl flex items-center justify-center shadow-lg shadow-{{ explode('-', $gc['bg'])[1] ?? 'primary' }}/20 transition-all group-hover:scale-105 active:scale-95 cursor-pointer">
                                        <span class="text-2xl font-black">{{ $groupLetter }}</span>
                                    </div>
                                    <span class="text-[10px] font-bold text-base-content/40 mt-1.5 uppercase tracking-widest group-hover:text-primary transition-colors">Kumpulan</span>
                                </div>
                            @else
                                <div class="flex flex-col items-center shrink-0">
                                    <div class="bg-base-200 w-14 h-14 rounded-2xl flex items-center justify-center">
                                        <svg class="w-6 h-6 text-base-content/20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    </div>
                                    <span class="text-[10px] font-bold text-base-content/30 mt-1 uppercase tracking-widest">Belum Diundi</span>
                                </div>
                            @endif
                        </div>

                        {{-- Badges row --}}
                        <div class="flex flex-wrap gap-2 mb-4">
                            {{-- Game type --}}
                            <span class="inline-flex items-center text-xs font-bold px-2.5 py-1 rounded-full border {{ $gc['light'] }}">
                                {{ $team->game_type_label }}
                            </span>
                            {{-- Category --}}
                            <span class="inline-flex items-center text-xs font-medium px-2.5 py-1 rounded-full bg-base-200 text-base-content/60">
                                {{ $team->category->name }}
                            </span>
                            {{-- Attendance status --}}
                            @if($team->status === 'checked_in')
                                <span class="status-badge-present">● Hadir</span>
                            @elseif($team->status === 'absent')
                                <span class="status-badge-absent">● Tidak Hadir</span>
                            @else
                                <span class="status-badge-pending">● Berdaftar</span>
                            @endif
                        </div>

                        {{-- Players list --}}
                        <div class="bg-base-200/40 rounded-xl p-3 mb-4">
                            <p class="text-[10px] font-bold uppercase tracking-widest text-base-content/40 mb-2">Senarai Pemain</p>
                            <div class="space-y-1 text-sm">
                                @if($team->player_1) <p><span class="font-bold text-primary mr-1">1.</span>{{ $team->player_1 }}</p> @endif
                                @if($team->player_2) <p><span class="font-bold text-primary mr-1">2.</span>{{ $team->player_2 }}</p> @endif
                                @if($team->player_3) <p><span class="font-bold text-primary mr-1">3.</span>{{ $team->player_3 }} <span class="text-xs text-base-content/40">(Rizab)</span></p> @endif
                            </div>
                        </div>

                        {{-- Explicit View Group Button for UX --}}
                        @if($groupLetter)
                            <button wire:click="viewGroup({{ $teamGroup->group->id }})" 
                                    class="w-full py-2.5 flex items-center justify-center gap-2 bg-base-200 hover:bg-base-300 text-base-content/70 hover:text-base-content text-sm font-bold rounded-xl transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                Lihat Senarai Lawan (Kump. {{ $groupLetter }})
                            </button>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        @else
            <div class="bg-amber-50 border border-amber-200 rounded-2xl px-6 py-5">
                <h3 class="font-bold text-amber-800">Tiada Rekod Dijumpai</h3>
                <p class="text-sm text-amber-700 mt-1">Tiada pasukan atau sekolah dijumpai untuk carian "<strong>{{ $search }}</strong>". Pastikan ejaan anda betul.</p>
            </div>
        @endif
    @endif

    {{-- Group Modal --}}
    <div x-data="{ open: false }" 
         x-on:open-group-modal.window="open = true" 
         x-on:close-group-modal.window="open = false">
        
        <div x-show="open" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                
                {{-- Backdrop --}}
                <div x-show="open" 
                     x-transition:enter="ease-out duration-300" 
                     x-transition:enter-start="opacity-0" 
                     x-transition:enter-end="opacity-100" 
                     x-transition:leave="ease-in duration-200" 
                     x-transition:leave-start="opacity-100" 
                     x-transition:leave-end="opacity-0" 
                     class="fixed inset-0 transition-opacity bg-black/50 backdrop-blur-sm" 
                     aria-hidden="true" 
                     wire:click="closeGroupModal"></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                {{-- Modal Panel --}}
                <div x-show="open" 
                     x-transition:enter="ease-out duration-300" 
                     x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" 
                     x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" 
                     x-transition:leave="ease-in duration-200" 
                     x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" 
                     x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" 
                     class="inline-block w-full max-w-lg overflow-hidden text-left align-bottom transition-all transform bg-base-100 rounded-3xl shadow-2xl sm:my-8 sm:align-middle border border-base-200">
                    
                    @if($this->selectedGroup)
                        @php
                            $sgc = [
                                'isobot'     => 'bg-blue-600',
                                'sky_soccer' => 'bg-violet-600',
                                'obstacle'   => 'bg-amber-500',
                            ][$this->selectedGroup->game_type] ?? 'bg-primary';
                        @endphp
                        
                        <div class="relative px-6 py-5 {{ $sgc }} text-white flex justify-between items-center">
                            <div>
                                <h3 class="text-2xl font-black" id="modal-title">{{ $this->selectedGroup->group_name }}</h3>
                                <p class="text-white/70 text-xs mt-0.5 font-medium uppercase tracking-widest">{{ $this->selectedGroup->category->name ?? '' }} • {{ str_replace('_', ' ', $this->selectedGroup->game_type) }}</p>
                            </div>
                            <button wire:click="closeGroupModal" class="text-white/50 hover:text-white bg-white/10 hover:bg-white/20 p-2 rounded-xl transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                        
                        <div class="p-6">
                            <p class="text-sm font-bold text-base-content/50 mb-3 uppercase tracking-widest">Senarai Pasukan (Lawan)</p>
                            
                            <div class="space-y-3">
                                @foreach($this->selectedGroup->groupTeams as $index => $gt)
                                    <div class="flex items-center gap-4 bg-base-200/50 p-4 rounded-2xl border border-base-200 hover:border-primary/30 transition-colors {{ $gt->team_id === $this->viewGroupId ? 'ring-2 ring-primary bg-primary/5' : '' }}">
                                        <div class="w-8 h-8 rounded-full bg-base-300 text-base-content/50 flex items-center justify-center font-bold text-sm shrink-0">
                                            {{ $index + 1 }}
                                        </div>
                                        <div>
                                            <h4 class="font-bold text-base-content leading-tight">{{ $gt->team->team_name }}</h4>
                                            <p class="text-xs text-base-content/60 mt-0.5">🏫 {{ $gt->team->school_name }}</p>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        
                        <div class="px-6 py-4 bg-base-200/50 flex justify-end">
                            <button wire:click="closeGroupModal" class="bg-white border border-base-300 hover:bg-base-200 text-base-content font-bold px-6 py-2.5 rounded-xl transition-colors">
                                Tutup
                            </button>
                        </div>
                    @else
                        <div class="p-8 text-center">
                            <span class="loading loading-spinner loading-lg text-primary"></span>
                            <p class="mt-4 text-base-content/50 font-medium">Memuatkan kumpulan...</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
