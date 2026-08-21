<?php

use App\Models\Category;
use App\Models\Team;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;

new class extends Component
{
    #[Url]
    public string $search = '';

    public string $filterGame     = '';
    public string $filterCategory = '';
    public ?int   $highlightedId  = null;
    public string $message        = '';
    public string $messageType    = ''; // success | error
    public bool $isLocked = false;

    public function mount(): void
    {
        $this->isLocked = session('attendance_locked', false);
    }

    public function updatedSearch(): void
    {
        $this->highlightedId = null;
    }

    #[Computed]
    public function results()
    {
        if (strlen(trim($this->search)) < 2) {
            return collect();
        }

        $query = Team::with('category')
            ->where(function ($q) {
                $q->where('team_name', 'like', '%' . $this->search . '%')
                  ->orWhere('school_name', 'like', '%' . $this->search . '%');
            });

        if ($this->filterGame) {
            $query->where('game_type', $this->filterGame);
        }

        if ($this->filterCategory) {
            $query->whereHas('category', fn($q) => $q->where('slug', $this->filterCategory));
        }

        return $query->orderBy('team_name')->limit(20)->get();
    }

    #[Computed]
    public function categories()
    {
        return Category::orderBy('sort_order')->get();
    }

    public function checkIn(int $teamId): void
    {
        if ($this->isLocked) return;

        $team = Team::findOrFail($teamId);

        if ($team->status === 'checked_in') {
            $this->message     = "⚠️ {$team->team_name} sudah ditanda hadir.";
            $this->messageType = 'warning';
            return;
        }

        $team->update([
            'status'        => 'checked_in',
            'checked_in_at' => now(),
        ]);

        $this->highlightedId = $teamId;
        $this->message       = "✅ {$team->team_name} berjaya ditanda HADIR.";
        $this->messageType   = 'success';
        unset($this->results);
    }

    public function markAbsent(int $teamId): void
    {
        if ($this->isLocked) return;

        $team = Team::findOrFail($teamId);
        $team->update(['status' => 'absent']);

        $this->message     = "❌ {$team->team_name} ditanda TIDAK HADIR.";
        $this->messageType = 'error';
        unset($this->results);
    }

    public function undoStatus(int $teamId): void
    {
        if ($this->isLocked) return;

        $team = Team::findOrFail($teamId);
        $team->update(['status' => 'registered', 'checked_in_at' => null]);

        $this->message     = "↩️ {$team->team_name} status ditukar semula kepada Berdaftar.";
        $this->messageType = 'warning';
        unset($this->results);
    }
};
?>

<div class="space-y-5 animate-slide-up">
    @if($message)
    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)" x-transition:leave="transition ease-in duration-300" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
         class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium border animate-fade-in {{ $messageType === 'success' ? 'bg-emerald-50 border-emerald-200 text-emerald-800' : ($messageType === 'error' ? 'bg-red-50 border-red-200 text-red-800' : 'bg-amber-50 border-amber-200 text-amber-800') }}">
        <span>{{ $message }}</span>
    </div>
    @endif

    @if($isLocked)
    <div class="flex items-center gap-3 bg-red-50 border border-red-200 rounded-2xl px-5 py-3">
        <p class="font-bold text-red-700 text-sm">🔒 Kehadiran Dikunci — Hubungi Master Admin untuk buka kunci.</p>
    </div>
    @endif

    <div class="bg-white rounded-2xl border border-base-200 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-base-200">
            <h2 class="font-bold text-base-content">Cari &amp; Tandakan Kehadiran</h2>
        </div>
        <div class="p-5 flex flex-col md:flex-row gap-3">
            <div class="flex-1 relative">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-base-content/30" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input wire:model.live.debounce.300ms="search" type="text" placeholder="Taip nama pasukan atau nama sekolah..."
                       class="w-full pl-10 pr-4 py-2.5 border-2 border-base-300 rounded-xl text-sm focus:outline-none focus:border-primary focus:ring-4 focus:ring-primary/10 transition-all" autofocus />
            </div>
            <select wire:model.live="filterGame" class="border-2 border-base-300 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:border-primary bg-white transition-all">
                <option value="">Semua Permainan</option>
                <option value="isobot">Isobot Soccer</option>
                <option value="sky_soccer">Drone Sky Soccer</option>
                <option value="obstacle">Drone Obstacle</option>
            </select>
            <select wire:model.live="filterCategory" class="border-2 border-base-300 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:border-primary bg-white transition-all">
                <option value="">Semua Kategori</option>
                @foreach($this->categories as $cat)
                    <option value="{{ $cat->slug }}">{{ $cat->name }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div wire:loading.flex class="items-center gap-2 text-primary text-sm">
        <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
        Mencari...
    </div>

    @if(strlen(trim($search)) >= 2)
        @if($this->results->isEmpty())
            <div class="bg-blue-50 border border-blue-100 rounded-2xl px-5 py-4 text-sm text-blue-700">
                Tiada pasukan dijumpai untuk carian "<strong>{{ $search }}</strong>"
            </div>
        @else
            <div class="space-y-3">
                @foreach($this->results as $team)
                <div wire:key="team-{{ $team->id }}"
                     class="bg-white rounded-2xl border-2 shadow-sm transition-all duration-200
                     {{ $team->status === 'checked_in' ? 'border-emerald-300 bg-emerald-50/30' : ($team->status === 'absent' ? 'border-red-200 bg-red-50/20' : 'border-base-200') }}
                     {{ $highlightedId === $team->id ? 'ring-4 ring-emerald-400 ring-offset-2' : '' }}">
                    <div class="p-5 flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div class="flex-1">
                            <div class="flex items-center gap-2 flex-wrap">
                                <h3 class="font-bold text-base-content">{{ $team->team_name }}</h3>
                                <span class="text-xs font-semibold bg-primary/10 text-primary px-2 py-0.5 rounded-full">{{ $team->game_type_label }}</span>
                                <span class="text-xs font-medium bg-base-200 text-base-content/60 px-2 py-0.5 rounded-full">{{ $team->category->name }}</span>
                                @if($team->status === 'checked_in') <span class="status-badge-present">● Hadir</span>
                                @elseif($team->status === 'absent') <span class="status-badge-absent">● Tidak Hadir</span>
                                @else <span class="status-badge-pending">● Berdaftar</span>
                                @endif
                            </div>
                            <p class="text-xs text-base-content/50 mt-1">🏫 {{ $team->school_name }}</p>
                            @if($team->checked_in_at)
                                <p class="text-xs text-emerald-600 mt-1 font-medium">✓ Ditanda hadir pada {{ $team->checked_in_at->format('h:i A') }}</p>
                            @endif
                        </div>
                        @if(!$isLocked)
                        <div class="flex gap-2 flex-wrap shrink-0">
                            @if($team->status !== 'checked_in')
                                <button wire:click="checkIn({{ $team->id }})" wire:loading.attr="disabled"
                                        class="inline-flex items-center gap-1.5 bg-emerald-500 hover:bg-emerald-600 text-white text-xs font-bold px-4 py-2 rounded-xl transition-colors">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    Tandakan Hadir
                                </button>
                            @endif
                            @if($team->status !== 'absent')
                                <button wire:click="markAbsent({{ $team->id }})" wire:loading.attr="disabled"
                                        class="inline-flex items-center gap-1.5 border-2 border-red-200 text-red-500 hover:bg-red-50 text-xs font-bold px-4 py-2 rounded-xl transition-colors">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    Tidak Hadir
                                </button>
                            @endif
                            @if($team->status !== 'registered')
                                <button wire:click="undoStatus({{ $team->id }})" wire:confirm="Anda pasti mahu tukar semula status pasukan ini?"
                                        class="text-xs font-medium text-base-content/40 hover:text-base-content px-3 py-2 rounded-xl hover:bg-base-200 transition-colors">↩ Undo</button>
                            @endif
                        </div>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        @endif
    @else
        <div class="flex flex-col items-center gap-4 py-16 text-base-content/30">
            <div class="w-20 h-20 bg-base-200 rounded-3xl flex items-center justify-center">
                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>
            <p class="text-base font-medium">Taip sekurang-kurangnya 2 huruf untuk mencari pasukan</p>
        </div>
    @endif
</div>