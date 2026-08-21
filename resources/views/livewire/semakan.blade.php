<?php

use App\Models\Team;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;

new class extends Component {
    
    #[Url(as: 'q')]
    public string $search = '';

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
                <div wire:key="team-{{ $team->id }}" class="bg-white rounded-2xl border border-base-200 shadow-sm hover:border-primary hover:shadow-md transition-all p-5">
                    <div class="flex justify-between items-start mb-3">
                        <div>
                            <h4 class="font-bold text-base-content text-base">{{ $team->team_name }}</h4>
                            <p class="text-xs text-base-content/50 mt-0.5">🏫 {{ $team->school_name }}</p>
                        </div>
                        <div class="text-right shrink-0 ml-3">
                            <span class="block text-xs font-semibold bg-primary/10 text-primary px-2 py-0.5 rounded-full mb-1">{{ $team->game_type_label }}</span>
                            <span class="text-xs font-medium bg-base-200 text-base-content/60 px-2 py-0.5 rounded-full">{{ $team->category->name }}</span>
                        </div>
                    </div>
                    <div class="flex gap-2 mb-4">
                        @if($team->status === 'checked_in') <span class="status-badge-present">● Hadir</span>
                        @elseif($team->status === 'absent') <span class="status-badge-absent">● Tidak Hadir</span>
                        @else <span class="status-badge-pending">● Berdaftar</span>
                        @endif
                        @if($team->groupTeams->isNotEmpty())
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-700">Kumpulan {{ $team->groupTeams->first()->group->name }}</span>
                        @else
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-base-200 text-base-content/50">Belum Diundi</span>
                        @endif
                    </div>
                    <div class="bg-base-200/50 rounded-xl p-3">
                        <p class="text-[10px] font-bold uppercase tracking-widest text-base-content/40 mb-2">Senarai Pemain</p>
                        <div class="space-y-1 text-sm">
                            @if($team->player_1) <p><span class="font-bold text-primary">1.</span> {{ $team->player_1 }}</p> @endif
                            @if($team->player_2) <p><span class="font-bold text-primary">2.</span> {{ $team->player_2 }}</p> @endif
                            @if($team->player_3) <p><span class="font-bold text-primary">3.</span> {{ $team->player_3 }} <span class="text-xs text-base-content/40">(Rizab)</span></p> @endif
                        </div>
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
</div>
