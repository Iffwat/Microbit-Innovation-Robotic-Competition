<?php

use App\Models\Category;
use App\Models\Group;
use App\Models\Team;
use App\Models\GroupTeam;
use App\Models\TournamentMatch;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public $gameType;
    public $categorySlug;
    public $category;

    // Late Team Modal State
    public bool $showAddLateTeamModal = false;
    public ?int $selectedLateTeamId = null;
    public ?int $selectedTargetGroupId = null;
    
    public function mount($gameType, $categorySlug)
    {
        $this->gameType = $gameType;
        $this->categorySlug = $categorySlug;
        $this->category = Category::where('slug', $categorySlug)->firstOrFail();
    }

    #[Computed]
    public function groups()
    {
        return Group::with(['groupTeams.team'])
            ->where('category_id', $this->category->id)
            ->where('game_type', $this->gameType)
            ->orderBy('group_name')
            ->get();
    }

    #[Computed]
    public function unassignedTeams()
    {
        $assignedTeamIds = GroupTeam::whereHas('group', function($q) {
            $q->where('category_id', $this->category->id)
              ->where('game_type', $this->gameType);
        })->pluck('team_id');

        return Team::where('category_id', $this->category->id)
            ->where('game_type', $this->gameType)
            ->whereNotIn('id', $assignedTeamIds)
            ->orderBy('team_name')
            ->get();
    }

    public function assignField(int $groupId, ?string $fieldNumber)
    {
        $group = Group::where('id', $groupId)->where('category_id', $this->category->id)->firstOrFail();
        $group->field_number = empty($fieldNumber) ? null : $fieldNumber;
        $group->save();
    }

    public function openAddLateTeamModal(?int $groupId = null): void
    {
        $this->selectedTargetGroupId = $groupId;
        $this->selectedLateTeamId = null;
        $this->showAddLateTeamModal = true;
    }

    public function closeAddLateTeamModal(): void
    {
        $this->showAddLateTeamModal = false;
        $this->selectedLateTeamId = null;
        $this->selectedTargetGroupId = null;
    }

    public function addLateTeam(): void
    {
        $this->validate([
            'selectedLateTeamId' => 'required|exists:teams,id',
            'selectedTargetGroupId' => 'required|exists:groups,id',
        ], [
            'selectedLateTeamId.required' => 'Sila pilih pasukan yang hendak dimasukkan.',
            'selectedTargetGroupId.required' => 'Sila pilih kumpulan sasaran.',
        ]);

        $team = Team::findOrFail($this->selectedLateTeamId);
        $group = Group::where('id', $this->selectedTargetGroupId)
            ->where('category_id', $this->category->id)
            ->firstOrFail();

        \Illuminate\Support\Facades\DB::beginTransaction();
        try {
            // 1. Mark team status as checked_in
            if ($team->status !== 'checked_in') {
                $team->update([
                    'status' => 'checked_in',
                    'checked_in_at' => now(),
                ]);
            }

            // 2. Add team to GroupTeam
            GroupTeam::firstOrCreate([
                'group_id' => $group->id,
                'team_id' => $team->id,
            ]);

            // 3. If fixtures already exist for this group, generate additional matches
            $existingMatchesCount = TournamentMatch::where('group_id', $group->id)->count();

            if ($existingMatchesCount > 0) {
                if ($this->gameType === 'obstacle') {
                    // Append single time trial run slot
                    TournamentMatch::create([
                        'category_id' => $this->category->id,
                        'group_id' => $group->id,
                        'stage' => 'group',
                        'round_name' => 'Larian ' . ($existingMatchesCount + 1),
                        'home_team_id' => $team->id,
                        'away_team_id' => null,
                        'field_number' => $group->field_number ?? 'Course 1',
                        'status' => 'scheduled'
                    ]);
                } else {
                    // Round-robin: create match against all other teams in the group
                    $otherTeamIds = $group->groupTeams()
                        ->where('team_id', '!=', $team->id)
                        ->pluck('team_id');

                    $extraMatchCount = 0;
                    foreach ($otherTeamIds as $otherTeamId) {
                        $alreadyMatched = TournamentMatch::where('group_id', $group->id)
                            ->where(function($q) use ($team, $otherTeamId) {
                                $q->where(function($q2) use ($team, $otherTeamId) {
                                    $q2->where('home_team_id', $team->id)->where('away_team_id', $otherTeamId);
                                })->orWhere(function($q2) use ($team, $otherTeamId) {
                                    $q2->where('home_team_id', $otherTeamId)->where('away_team_id', $team->id);
                                });
                            })->exists();

                        if (!$alreadyMatched) {
                            $extraMatchCount++;
                            TournamentMatch::create([
                                'category_id' => $this->category->id,
                                'group_id' => $group->id,
                                'stage' => 'group',
                                'round_name' => 'Kumpulan ' . $group->group_letter . ' - Tambahan ' . $extraMatchCount,
                                'home_team_id' => $team->id,
                                'away_team_id' => $otherTeamId,
                                'field_number' => $group->field_number,
                                'status' => 'scheduled'
                            ]);
                        }
                    }
                }
            }

            \Illuminate\Support\Facades\DB::commit();
            $this->closeAddLateTeamModal();
            $this->js("alert('Pasukan {$team->team_name} berjaya dimasukkan ke {$group->group_name} dan jadual perlawanan telah dikemaskini!');");
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            $this->js("alert('Ralat: {$e->getMessage()}');");
        }
    }

    public function removeTeamFromGroup(int $groupId, int $teamId): void
    {
        $group = Group::where('id', $groupId)->where('category_id', $this->category->id)->firstOrFail();
        $team = Team::findOrFail($teamId);

        // Check if this team has completed any match
        $hasCompletedMatches = TournamentMatch::where('group_id', $group->id)
            ->where(function($q) use ($teamId) {
                $q->where('home_team_id', $teamId)->orWhere('away_team_id', $teamId);
            })
            ->whereIn('status', ['completed', 'in_progress'])
            ->exists();

        if ($hasCompletedMatches) {
            $this->js("alert('Pasukan {$team->team_name} tidak boleh dikeluarkan kerana telah mempunyai rekod perlawanan yang selesai atau sedang berlangsung!');");
            return;
        }

        \Illuminate\Support\Facades\DB::beginTransaction();
        try {
            // Delete scheduled matches for this team in this group
            TournamentMatch::where('group_id', $group->id)
                ->where(function($q) use ($teamId) {
                    $q->where('home_team_id', $teamId)->orWhere('away_team_id', $teamId);
                })
                ->where('status', 'scheduled')
                ->delete();

            // Delete group team record
            GroupTeam::where('group_id', $group->id)->where('team_id', $teamId)->delete();

            \Illuminate\Support\Facades\DB::commit();
            $this->js("alert('Pasukan {$team->team_name} telah dikeluarkan daripada {$group->group_name}.');");
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            $this->js("alert('Ralat: {$e->getMessage()}');");
        }
    }

    public function generateFixtures()
    {
        $groups = $this->groups;
        
        \Illuminate\Support\Facades\DB::beginTransaction();
        try {
            TournamentMatch::where('category_id', $this->category->id)
                ->whereHas('group', function($q) {
                    $q->where('game_type', $this->gameType);
                })
                ->where('stage', 'group')
                ->delete();

            foreach ($groups as $group) {
                $teams = $group->groupTeams()->pluck('team_id')->toArray();
                
                if ($this->gameType === 'obstacle') {
                    // For obstacle, it's a time trial (solo run)
                    foreach ($teams as $index => $teamId) {
                        TournamentMatch::create([
                            'category_id' => $this->category->id,
                            'group_id' => $group->id,
                            'stage' => 'group',
                            'round_name' => 'Larian ' . ($index + 1),
                            'home_team_id' => $teamId,
                            'away_team_id' => null, // No opponent
                            'field_number' => $group->field_number ?? 'Course 1',
                            'status' => 'scheduled'
                        ]);
                    }
                } else {
                    // Round Robin for Isobot & Sky Soccer
                    $teamCount = count($teams);
                    if ($teamCount < 2) continue;

                    if ($teamCount % 2 != 0) {
                        $teams[] = null;
                        $teamCount++;
                    }

                    $rounds = $teamCount - 1;
                    $matchesPerRound = $teamCount / 2;

                    for ($round = 0; $round < $rounds; $round++) {
                        for ($match = 0; $match < $matchesPerRound; $match++) {
                            $home = $teams[$match];
                            $away = $teams[$teamCount - 1 - $match];

                            if ($home !== null && $away !== null) {
                                TournamentMatch::create([
                                    'category_id' => $this->category->id,
                                    'group_id' => $group->id,
                                    'stage' => 'group',
                                    'round_name' => 'Kumpulan ' . $group->group_letter . ' - P' . ($round + 1),
                                    'home_team_id' => $home,
                                    'away_team_id' => $away,
                                    'field_number' => $group->field_number,
                                    'status' => 'scheduled'
                                ]);
                            }
                        }

                        $temp = $teams[$teamCount - 1];
                        for ($i = $teamCount - 1; $i > 1; $i--) {
                            $teams[$i] = $teams[$i - 1];
                        }
                        $teams[1] = $temp;
                    }
                }
            }
            \Illuminate\Support\Facades\DB::commit();
            session()->flash('success', 'Senarai giliran perlawanan/larian berjaya dijana!');
            return redirect()->route('admin.matches.index');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            $this->js("alert('Ralat: {$e->getMessage()}');");
        }
    }

    public function deleteFixtures()
    {
        \Illuminate\Support\Facades\DB::beginTransaction();
        try {
            TournamentMatch::where('category_id', $this->category->id)
                ->whereHas('group', function($q) {
                    $q->where('game_type', $this->gameType);
                })
                ->where('stage', 'group')
                ->delete();

            $groupIds = $this->groups->pluck('id');
            GroupTeam::whereIn('group_id', $groupIds)->update([
                'played' => 0,
                'won' => 0,
                'drawn' => 0,
                'lost' => 0,
                'goals_for' => 0,
                'goals_against' => 0,
                'goal_difference' => 0,
                'points' => 0,
            ]);

            \Illuminate\Support\Facades\DB::commit();
            $this->js("alert('Jadual perlawanan dan statistik kumpulan telah berjaya dipadam.');");
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            $this->js("alert('Ralat: {$e->getMessage()}');");
        }
    }
};
?>

<div class="space-y-6 animate-slide-up">
    
    @php
        $gameLabel = match($gameType) {
            'isobot' => 'Isobot Soccer',
            'sky_soccer' => 'Drone Sky Soccer',
            'obstacle' => 'Drone Obstacle',
            default => 'Permainan'
        };
        $isObstacle = $gameType === 'obstacle';
    @endphp

    <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.groups.index') }}" class="w-10 h-10 bg-white rounded-full flex items-center justify-center shadow-sm border border-base-200 text-base-content/50 hover:text-primary transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            </a>
            <div>
                <h2 class="text-xl font-extrabold text-base-content">Senarai @if($isObstacle) Laluan @else Kumpulan @endif: {{ $category->name }} ({{ $gameLabel }})</h2>
                <p class="text-sm text-base-content/60 mt-0.5">{{ $this->groups->count() }} @if($isObstacle) laluan @else kumpulan @endif dijana</p>
            </div>
        </div>
        
        <div class="flex items-center gap-2 flex-wrap">
            {{-- Late Team Injection Button --}}
            @if($this->unassignedTeams->isNotEmpty() && $this->groups->isNotEmpty())
                <button wire:click="openAddLateTeamModal()" 
                        class="bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-bold py-2.5 px-4 rounded-xl transition-colors flex items-center gap-2 shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    <span>Masukkan Pasukan Lewat</span>
                    <span class="bg-white/20 text-white px-2 py-0.5 rounded-full text-xs font-black">{{ $this->unassignedTeams->count() }}</span>
                </button>
            @endif

            @if(!$isObstacle)
            <button wire:click="generateFixtures" 
                    wire:confirm="Sistem akan menjana padanan Round-Robin untuk semua kumpulan dalam kategori ini. Teruskan?"
                    wire:loading.attr="disabled"
                    class="bg-primary hover:bg-primary/90 text-white text-sm font-bold py-2.5 px-4 rounded-xl transition-colors flex items-center gap-2 shadow-sm">
                <span wire:loading.remove wire:target="generateFixtures" class="flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    Jana Jadual Perlawanan
                </span>
                <span wire:loading wire:target="generateFixtures">Menjana...</span>
            </button>
            @else
            <button wire:click="generateFixtures" 
                    wire:confirm="Sistem akan menjana jadual giliran Larian (Time Trial) untuk laluan ini. Teruskan?"
                    wire:loading.attr="disabled"
                    class="bg-amber-500 hover:bg-amber-600 text-white text-sm font-bold py-2.5 px-4 rounded-xl transition-colors flex items-center gap-2 shadow-sm">
                <span wire:loading.remove wire:target="generateFixtures" class="flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    Jana Giliran Larian
                </span>
                <span wire:loading wire:target="generateFixtures">Menjana...</span>
            </button>
            @endif

            <button wire:click="deleteFixtures" 
                    wire:confirm="AMARAN: Anda pasti mahu memadam semua jadual perlawanan dan mengosongkan statistik kumpulan ini?"
                    wire:loading.attr="disabled"
                    class="bg-white border-2 border-red-200 text-red-500 hover:bg-red-50 text-sm font-bold py-2.5 px-4 rounded-xl transition-colors flex items-center gap-2 shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                Padam Jadual
            </button>
        </div>
    </div>

    @if($this->groups->isEmpty())
        <div class="bg-amber-50 border border-amber-200 rounded-2xl px-6 py-8 text-center text-amber-800">
            <svg class="w-12 h-12 mx-auto mb-3 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <h3 class="font-bold text-lg">Tiada @if($isObstacle) Laluan @else Kumpulan @endif</h3>
            <p class="text-sm mt-1">Belum dijana untuk kategori ini. Sila kembali ke muka hadapan dan klik "Jana".</p>
            <a href="{{ route('admin.groups.index') }}" class="inline-block mt-4 bg-amber-500 text-white px-5 py-2 rounded-xl text-sm font-bold hover:bg-amber-600 transition-colors">Kembali</a>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
            @foreach($this->groups as $group)
                <div class="bg-white rounded-2xl border border-base-200 shadow-sm overflow-hidden flex flex-col">
                    <div class="bg-base-200/50 px-5 py-3 border-b border-base-200 flex flex-col gap-2">
                        <div class="flex justify-between items-center">
                            <h3 class="font-extrabold text-lg text-primary">{{ $group->group_name }}</h3>
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-bold bg-white px-2.5 py-1 rounded-full text-base-content/60 shadow-sm">{{ $group->groupTeams->count() }} Pasukan</span>
                                @if($this->unassignedTeams->isNotEmpty())
                                    <button wire:click="openAddLateTeamModal({{ $group->id }})" 
                                            class="text-xs font-bold text-emerald-600 hover:text-emerald-700 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 px-2 py-0.5 rounded-lg transition-colors flex items-center gap-1 shadow-2xs"
                                            title="Masukkan Pasukan Lewat ke Kumpulan ini">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                        + Tambah
                                    </button>
                                @endif
                            </div>
                        </div>
                        <div class="flex items-center gap-2 mt-1">
                            <label class="text-[10px] font-bold text-base-content/40 uppercase tracking-widest shrink-0">Lokasi / Padang:</label>
                            @if($gameType === 'isobot')
                                <select wire:change="assignField({{ $group->id }}, $event.target.value)" 
                                        wire:loading.class="opacity-50"
                                        class="select select-bordered select-xs flex-1 bg-white focus:outline-none focus:border-primary text-xs font-semibold">
                                    <option value="">- Belum Ditetapkan -</option>
                                    @for($i=1; $i<=15; $i++)
                                        <option value="{{ $i }}" {{ $group->field_number == $i ? 'selected' : '' }}>Padang {{ $i }}</option>
                                    @endfor
                                </select>
                            @elseif($gameType === 'sky_soccer')
                                <span class="text-xs font-bold text-base-content bg-base-200 px-3 py-1 rounded-lg flex-1">Arena Sky Soccer (Tetap)</span>
                            @else
                                <span class="text-xs font-bold text-base-content bg-base-200 px-3 py-1 rounded-lg flex-1">{{ $group->group_name }} (Laluan Khas)</span>
                            @endif
                        </div>
                    </div>
                    
                    <div class="flex-1 p-0">
                        <table class="w-full text-sm">
                            <thead class="bg-base-100 border-b border-base-200">
                                <tr>
                                    <th class="text-left py-2 px-4 text-[10px] font-bold text-base-content/40 uppercase tracking-widest w-8">#</th>
                                    <th class="text-left py-2 px-4 text-[10px] font-bold text-base-content/40 uppercase tracking-widest">Pasukan</th>
                                    <th class="text-right py-2 px-4 text-[10px] font-bold text-base-content/40 uppercase tracking-widest w-12">Tindakan</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-base-100">
                                @foreach($group->groupTeams as $idx => $gt)
                                    <tr class="hover:bg-base-50 transition-colors group/row">
                                        <td class="py-3 px-4 text-xs font-semibold text-base-content/30">{{ $idx + 1 }}</td>
                                        <td class="py-3 px-4">
                                            <p class="font-bold text-base-content">{{ $gt->team->team_name }}</p>
                                            <p class="text-[10px] text-base-content/50 mt-0.5 leading-tight">🏫 {{ $gt->team->school_name }}</p>
                                        </td>
                                        <td class="py-3 px-4 text-right">
                                            <button wire:click="removeTeamFromGroup({{ $group->id }}, {{ $gt->team->id }})"
                                                    wire:confirm="Anda pasti mahu mengeluarkan pasukan {{ $gt->team->team_name }} daripada {{ $group->group_name }}?"
                                                    class="opacity-0 group-hover/row:opacity-100 text-red-400 hover:text-red-600 p-1 rounded-lg hover:bg-red-50 transition-all"
                                                    title="Keluarkan Pasukan">
                                                <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Late Team Injection Modal --}}
    @if($showAddLateTeamModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4 animate-fade-in">
            <div class="bg-white rounded-3xl shadow-2xl w-full max-w-lg overflow-hidden animate-slide-up border border-base-200">
                <div class="p-6 border-b border-base-200 bg-base-50 flex justify-between items-center">
                    <div>
                        <h3 class="font-black text-xl text-base-content">➕ Masukkan Pasukan Lewat</h3>
                        <p class="text-xs text-base-content/50 mt-0.5">Suntik pasukan ke dalam kumpulan & jana perlawanan tambahan.</p>
                    </div>
                    <button wire:click="closeAddLateTeamModal" class="w-8 h-8 rounded-full bg-base-200 text-base-content/50 hover:text-base-content flex items-center justify-center font-bold">✕</button>
                </div>

                <div class="p-6 space-y-5">
                    @if($this->unassignedTeams->isEmpty())
                        <div class="bg-amber-50 border border-amber-200 rounded-2xl p-4 text-sm text-amber-800">
                            <p class="font-bold">Tiada Pasukan Belum Diundi</p>
                            <p class="text-xs mt-1">Semua pasukan berdaftar bagi kategori ini telah dimasukkan ke dalam kumpulan.</p>
                        </div>
                    @else
                        {{-- Select Late Team --}}
                        <div class="space-y-1.5">
                            <label class="text-xs font-bold text-base-content uppercase tracking-wider">1. Pilih Pasukan Lewat:</label>
                            <select wire:model.live="selectedLateTeamId" class="select select-bordered w-full rounded-xl text-sm focus:border-primary">
                                <option value="">-- Sila Pilih Pasukan --</option>
                                @foreach($this->unassignedTeams as $uTeam)
                                    <option value="{{ $uTeam->id }}">
                                        {{ $uTeam->team_name }} (🏫 {{ $uTeam->school_name }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Select Target Group --}}
                        <div class="space-y-1.5">
                            <label class="text-xs font-bold text-base-content uppercase tracking-wider">2. Pilih Kumpulan Sasaran:</label>
                            <select wire:model.live="selectedTargetGroupId" class="select select-bordered w-full rounded-xl text-sm focus:border-primary">
                                <option value="">-- Sila Pilih Kumpulan --</option>
                                @foreach($this->groups as $grp)
                                    @php
                                        $isSameSchool = false;
                                        if ($selectedLateTeamId) {
                                            $lateTeam = $this->unassignedTeams->firstWhere('id', $selectedLateTeamId);
                                            if ($lateTeam) {
                                                $isSameSchool = $grp->groupTeams->contains(fn($gt) => strtolower(trim($gt->team->school_name)) === strtolower(trim($lateTeam->school_name)));
                                            }
                                        }
                                    @endphp
                                    <option value="{{ $grp->id }}">
                                        {{ $grp->group_name }} ({{ $grp->groupTeams->count() }} Pasukan) {{ $isSameSchool ? '⚠️ [Konflik Sekolah Sama]' : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        @if($selectedLateTeamId && $selectedTargetGroupId)
                            @php
                                $targetGrp = $this->groups->firstWhere('id', $selectedTargetGroupId);
                                $lateTeamObj = $this->unassignedTeams->firstWhere('id', $selectedLateTeamId);
                                $hasSameSchool = $targetGrp && $lateTeamObj ? $targetGrp->groupTeams->contains(fn($gt) => strtolower(trim($gt->team->school_name)) === strtolower(trim($lateTeamObj->school_name))) : false;
                            @endphp

                            @if($hasSameSchool)
                                <div class="bg-red-50 border border-red-200 text-red-700 p-3.5 rounded-2xl text-xs flex items-start gap-2">
                                    <svg class="w-4 h-4 shrink-0 mt-0.5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                    <span><strong>Peringatan:</strong> {{ $targetGrp->group_name }} sudah mempunyai pasukan dari sekolah yang sama (<strong>{{ $lateTeamObj->school_name }}</strong>). Sebaiknya pilih kumpulan lain jika ada pilihan.</span>
                                </div>
                            @else
                                <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 p-3.5 rounded-2xl text-xs flex items-start gap-2">
                                    <svg class="w-4 h-4 shrink-0 mt-0.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    <span><strong>Sesuai!</strong> Tiada konflik sekolah dalam {{ $targetGrp->group_name }}. Pasukan akan dijadualkan menentang {{ $targetGrp->groupTeams->count() }} pasukan sedia ada tanpa memadam sebarang skor perlawanan lama.</span>
                                </div>
                            @endif
                        @endif
                    @endif
                </div>

                <div class="p-4 border-t border-base-200 bg-base-50 flex justify-end gap-2">
                    <button wire:click="closeAddLateTeamModal" class="px-5 py-2.5 bg-base-200 hover:bg-base-300 text-base-content font-bold rounded-xl transition-colors text-sm">
                        Batal
                    </button>
                    @if($this->unassignedTeams->isNotEmpty())
                    <button wire:click="addLateTeam" 
                            wire:loading.attr="disabled"
                            {{ !$selectedLateTeamId || !$selectedTargetGroupId ? 'disabled' : '' }}
                            class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl transition-colors text-sm flex items-center gap-2 shadow-sm disabled:opacity-50">
                        <span wire:loading.remove wire:target="addLateTeam">Sahkan & Masukkan</span>
                        <span wire:loading wire:target="addLateTeam">Memproses...</span>
                    </button>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>
