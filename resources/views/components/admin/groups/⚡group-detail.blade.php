<?php

use App\Models\Category;
use App\Models\Group;
use Livewire\Component;

new class extends Component
{
    public $gameType;
    public $categorySlug;
    public $category;
    
    public function mount($gameType, $categorySlug)
    {
        $this->gameType = $gameType;
        $this->categorySlug = $categorySlug;
        $this->category = Category::where('slug', $categorySlug)->firstOrFail();
    }

    public function getGroupsProperty()
    {
        return Group::with(['groupTeams.team'])
            ->where('category_id', $this->category->id)
            ->where('game_type', $this->gameType)
            ->orderBy('group_name')
            ->get();
    }

    public function assignField(int $groupId, ?string $fieldNumber)
    {
        $group = Group::where('id', $groupId)->where('category_id', $this->category->id)->firstOrFail();
        $group->field_number = empty($fieldNumber) ? null : $fieldNumber;
        $group->save();
    }

    public function generateFixtures()
    {
        $groups = $this->groups;
        
        \Illuminate\Support\Facades\DB::beginTransaction();
        try {
            \App\Models\TournamentMatch::where('category_id', $this->category->id)
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
                        \App\Models\TournamentMatch::create([
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
                                \App\Models\TournamentMatch::create([
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
            \App\Models\TournamentMatch::where('category_id', $this->category->id)
                ->whereHas('group', function($q) {
                    $q->where('game_type', $this->gameType);
                })
                ->where('stage', 'group')
                ->delete();

            $groupIds = $this->groups->pluck('id');
            \App\Models\GroupTeam::whereIn('group_id', $groupIds)->update([
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

    <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.groups.index') }}" class="w-10 h-10 bg-white rounded-full flex items-center justify-center shadow-sm border border-base-200 text-base-content/50 hover:text-primary transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            </a>
            <div>
                <h2 class="text-xl font-extrabold text-base-content">Senarai @if($isObstacle) Laluan @else Kumpulan @endif: {{ $category->name }} ({{ $gameLabel }})</h2>
                <p class="text-sm text-base-content/60 mt-0.5">{{ $this->groups->count() }} @if($isObstacle) laluan @else kumpulan @endif dijana</p>
            </div>
        </div>
        
        <div class="flex items-center gap-2">
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
                            <span class="text-xs font-bold bg-white px-2.5 py-1 rounded-full text-base-content/60 shadow-sm">{{ $group->groupTeams->count() }} Pasukan</span>
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
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-base-100">
                                @foreach($group->groupTeams as $idx => $gt)
                                    <tr class="hover:bg-base-50 transition-colors">
                                        <td class="py-3 px-4 text-xs font-semibold text-base-content/30">{{ $idx + 1 }}</td>
                                        <td class="py-3 px-4">
                                            <p class="font-bold text-base-content">{{ $gt->team->team_name }}</p>
                                            <p class="text-[10px] text-base-content/50 mt-0.5 leading-tight">{{ $gt->team->school_name }}</p>
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
</div>
