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
    public string $messageType    = ''; // success | error | warning
    public bool   $isLocked       = false;

    // Fast Edit Form State
    public bool   $isEditing       = false;
    public ?int   $editingTeamId   = null;
    public string $editTeamName    = '';
    public string $editSchoolName  = '';
    public string $editPlayer1     = '';
    public string $editPlayer2     = '';
    public string $editPlayer3     = '';
    public string $editMentorName  = '';
    public string $editMentorEmail = '';

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

    public function openEdit(int $teamId): void
    {
        $team = Team::findOrFail($teamId);
        $this->editingTeamId   = $team->id;
        $this->editTeamName    = $team->team_name;
        $this->editSchoolName  = $team->school_name;
        $this->editPlayer1     = $team->player_1 ?? '';
        $this->editPlayer2     = $team->player_2 ?? '';
        $this->editPlayer3     = $team->player_3 ?? '';
        $this->editMentorName  = $team->mentor_name ?? '';
        $this->editMentorEmail = $team->mentor_email ?? '';
        $this->isEditing       = true;
    }

    public function closeEdit(): void
    {
        $this->isEditing     = false;
        $this->editingTeamId = null;
    }

    public function saveTeam(): void
    {
        if (!$this->editingTeamId) return;

        $team = Team::findOrFail($this->editingTeamId);

        $this->validate([
            'editTeamName'   => 'required|string|max:255',
            'editSchoolName' => 'required|string|max:255',
            'editPlayer1'    => 'nullable|string|max:255',
            'editPlayer2'    => 'nullable|string|max:255',
            'editPlayer3'    => 'nullable|string|max:255',
            'editMentorName' => 'nullable|string|max:255',
            'editMentorEmail'=> 'nullable|string|max:255',
        ]);

        $team->update([
            'team_name'    => $this->editTeamName,
            'school_name'  => $this->editSchoolName,
            'player_1'     => $this->editPlayer1 ?: null,
            'player_2'     => $this->editPlayer2 ?: null,
            'player_3'     => $this->editPlayer3 ?: null,
            'mentor_name'  => $this->editMentorName ?: null,
            'mentor_email' => $this->editMentorEmail ?: null,
        ]);

        $this->message       = "✨ Maklumat pasukan {$team->team_name} berjaya dikemaskini!";
        $this->messageType   = 'success';
        $this->isEditing     = false;
        $this->editingTeamId = null;
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
        <p class="font-bold text-red-700 text-sm">🔒 {{ __('Kehadiran Dikunci — Hubungi Master Admin untuk buka kunci.') }}</p>
    </div>
    @endif

    {{-- Search and Filters Box --}}
    <div class="bg-white rounded-2xl border border-base-200 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-base-200 flex items-center justify-between">
            <h2 class="font-bold text-base-content">{{ __('Cari & Tandakan Kehadiran') }}</h2>
            <span class="text-xs text-base-content/50">{{ __('Kaunter Pendaftaran & Semakan') }}</span>
        </div>
        <div class="p-5 flex flex-col md:flex-row gap-3">
            <div class="flex-1 relative">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-base-content/30" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input wire:model.live.debounce.300ms="search" type="text" placeholder="{{ __('Taip nama pasukan atau nama sekolah...') }}"
                       class="w-full pl-10 pr-4 py-2.5 border-2 border-base-300 rounded-xl text-sm focus:outline-none focus:border-primary focus:ring-4 focus:ring-primary/10 transition-all" autofocus />
            </div>
            <select wire:model.live="filterGame" class="border-2 border-base-300 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:border-primary bg-white transition-all">
                <option value="">{{ __('Semua Permainan') }}</option>
                <option value="isobot">{{ __('Isobot Soccer') }}</option>
                <option value="sky_soccer">{{ __('Drone Sky Soccer') }}</option>
                <option value="obstacle">{{ __('Drone Obstacle') }}</option>
            </select>
            <select wire:model.live="filterCategory" class="border-2 border-base-300 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:border-primary bg-white transition-all">
                <option value="">{{ __('Semua Kategori') }}</option>
                @foreach($this->categories as $cat)
                    <option value="{{ $cat->slug }}">{{ $cat->name }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div wire:loading.flex wire:target="search" class="items-center gap-2 text-primary text-sm">
        <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
        {{ __('Mencari pasukan...') }}
    </div>

    {{-- Search Results --}}
    @if(strlen(trim($search)) >= 2)
        @if($this->results->isEmpty())
            <div class="bg-blue-50 border border-blue-100 rounded-2xl px-5 py-4 text-sm text-blue-700">
                {{ __('Tiada pasukan dijumpai untuk carian') }} "<strong>{{ $search }}</strong>"
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
                                <h3 class="font-bold text-base text-base-content">{{ $team->team_name }}</h3>
                                <span class="text-xs font-semibold bg-primary/10 text-primary px-2.5 py-0.5 rounded-full">{{ $team->game_type_label }}</span>
                                <span class="text-xs font-medium bg-base-200 text-base-content/60 px-2 py-0.5 rounded-full">{{ $team->category->name }}</span>
                                @if($team->status === 'checked_in') <span class="status-badge-present">● {{ __('Hadir') }}</span>
                                @elseif($team->status === 'absent') <span class="status-badge-absent">● {{ __('Tidak Hadir') }}</span>
                                @else <span class="status-badge-pending">● {{ __('Berdaftar') }}</span>
                                @endif
                            </div>
                            <p class="text-xs text-base-content/60 mt-1 font-medium">🏫 {{ $team->school_name }}</p>
                            
                            {{-- Players preview --}}
                            <div class="flex items-center gap-3 mt-2 text-xs text-base-content/70 flex-wrap">
                                <span class="font-semibold text-base-content/40 uppercase tracking-wider text-[10px]">{{ __('Pemain:') }}</span>
                                @if($team->player_1) <span><strong>1.</strong> {{ $team->player_1 }}</span> @endif
                                @if($team->player_2) <span><strong>2.</strong> {{ $team->player_2 }}</span> @endif
                                @if($team->player_3) <span class="text-base-content/50"><strong>3.</strong> {{ $team->player_3 }} ({{ __('Rizab') }})</span> @endif
                            </div>

                            @if($team->checked_in_at)
                                <p class="text-xs text-emerald-600 mt-2 font-medium">✓ {{ __('Ditanda hadir pada') }} {{ $team->checked_in_at->format('h:i A') }}</p>
                            @endif
                        </div>

                        {{-- Action Buttons --}}
                        <div class="flex gap-2 flex-wrap items-center shrink-0">
                            {{-- Fast Edit Button (Instant Tukar Pemain / Pasukan) --}}
                            <button wire:click="openEdit({{ $team->id }})" 
                                    title="{{ __('Tukar nama pasukan atau nama pemain di kaunter') }}"
                                    class="inline-flex items-center gap-1.5 border border-base-300 hover:border-primary hover:text-primary text-base-content/70 text-xs font-bold px-3.5 py-2 rounded-xl transition-all hover:bg-primary/5">
                                ✏️ {{ __('Tukar Pemain') }}
                            </button>

                            @if(!$isLocked)
                                @if($team->status !== 'checked_in')
                                    <button wire:click="checkIn({{ $team->id }})" wire:loading.attr="disabled"
                                            class="inline-flex items-center gap-1.5 bg-emerald-500 hover:bg-emerald-600 text-white text-xs font-bold px-4 py-2 rounded-xl shadow-sm transition-colors">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        {{ __('Tandakan Hadir') }}
                                    </button>
                                @endif
                                @if($team->status !== 'absent')
                                    <button wire:click="markAbsent({{ $team->id }})" wire:loading.attr="disabled"
                                            class="inline-flex items-center gap-1.5 border-2 border-red-200 text-red-500 hover:bg-red-50 text-xs font-bold px-3.5 py-2 rounded-xl transition-colors">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                        {{ __('Tidak Hadir') }}
                                    </button>
                                @endif
                                @if($team->status !== 'registered')
                                    <button wire:click="undoStatus({{ $team->id }})" wire:confirm="Anda pasti mahu tukar semula status pasukan ini?"
                                            class="text-xs font-medium text-base-content/40 hover:text-base-content px-2.5 py-2 rounded-xl hover:bg-base-200 transition-colors">
                                        ↩ {{ __('Undo') }}
                                    </button>
                                @endif
                            @endif
                        </div>
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
            <p class="text-base font-medium">{{ __('Taip sekurang-kurangnya 2 huruf untuk mencari pasukan') }}</p>
        </div>
    @endif

    {{-- ========================================================== --}}
    {{-- FAST EDIT POPUP MODAL (TUKAR NAMA PASUKAN / PEMAIN)        --}}
    {{-- ========================================================== --}}
    @if($isEditing)
        <div class="fixed inset-0 z-50 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-white rounded-3xl border border-base-200 shadow-2xl max-w-xl w-full overflow-hidden animate-scale-in">
                <div class="px-6 py-4 border-b border-base-200 flex items-center justify-between bg-base-100">
                    <div class="flex items-center gap-2">
                        <span class="text-xl">✏️</span>
                        <div>
                            <h3 class="font-bold text-base text-base-content">{{ __('Kemaskini Pasukan & Tukar Pemain') }}</h3>
                            <p class="text-xs text-base-content/50">{{ __('Pertukaran maklumat rasmi pasukan semasa semak masuk') }}</p>
                        </div>
                    </div>
                    <button wire:click="closeEdit" class="text-base-content/40 hover:text-base-content text-xl font-bold p-1">✕</button>
                </div>

                <form wire:submit="saveTeam" class="p-6 space-y-4 max-h-[80vh] overflow-y-auto">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-base-content mb-1">{{ __('Nama Pasukan') }} <span class="text-red-500">*</span></label>
                            <input wire:model="editTeamName" type="text" required
                                   class="w-full px-3.5 py-2 border-2 border-base-300 rounded-xl text-sm font-bold focus:border-primary focus:outline-none" />
                            @error('editTeamName') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-base-content mb-1">{{ __('Nama Sekolah') }} <span class="text-red-500">*</span></label>
                            <input wire:model="editSchoolName" type="text" required
                                   class="w-full px-3.5 py-2 border-2 border-base-300 rounded-xl text-sm focus:border-primary focus:outline-none" />
                            @error('editSchoolName') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    {{-- Players Section --}}
                    <div class="bg-base-200/50 rounded-2xl p-4 space-y-3">
                        <p class="text-[11px] font-black uppercase tracking-wider text-base-content/50">{{ __('Senarai Nama Pemain') }}</p>
                        <div>
                            <label class="block text-xs font-semibold text-base-content mb-1">{{ __('Pemain 1 (Kapten)') }}</label>
                            <input wire:model="editPlayer1" type="text" placeholder="{{ __('Nama penuh pemain 1') }}"
                                   class="w-full px-3.5 py-2 bg-white border border-base-300 rounded-xl text-sm focus:border-primary focus:outline-none" />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-base-content mb-1">{{ __('Pemain 2') }}</label>
                            <input wire:model="editPlayer2" type="text" placeholder="{{ __('Nama penuh pemain 2') }}"
                                   class="w-full px-3.5 py-2 bg-white border border-base-300 rounded-xl text-sm focus:border-primary focus:outline-none" />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-base-content mb-1">{{ __('Pemain 3 (Rizab / Simpanan)') }}</label>
                            <input wire:model="editPlayer3" type="text" placeholder="{{ __('Nama penuh pemain 3') }}"
                                   class="w-full px-3.5 py-2 bg-white border border-base-300 rounded-xl text-sm focus:border-primary focus:outline-none" />
                        </div>
                    </div>

                    {{-- Mentor Section --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-base-content mb-1">{{ __('Nama Guru / Mentor') }}</label>
                            <input wire:model="editMentorName" type="text" placeholder="{{ __('Nama guru pengiring') }}"
                                   class="w-full px-3.5 py-2 border border-base-300 rounded-xl text-sm focus:border-primary focus:outline-none" />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-base-content mb-1">{{ __('No. Telefon / Emel') }}</label>
                            <input wire:model="editMentorEmail" type="text" placeholder="{{ __('012-3456789') }}"
                                   class="w-full px-3.5 py-2 border border-base-300 rounded-xl text-sm focus:border-primary focus:outline-none" />
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-base-200">
                        <button type="button" wire:click="closeEdit" class="px-4 py-2 text-sm font-semibold text-base-content/60 hover:text-base-content rounded-xl hover:bg-base-200 transition-colors">
                            {{ __('Batal') }}
                        </button>
                        <button type="submit" wire:loading.attr="disabled"
                                class="inline-flex items-center gap-2 bg-primary hover:bg-primary/90 text-white text-sm font-bold px-5 py-2.5 rounded-xl shadow-lg transition-colors">
                            <svg wire:loading.remove wire:target="saveTeam" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            <svg wire:loading wire:target="saveTeam" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                            {{ __('Simpan Perubahan') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
