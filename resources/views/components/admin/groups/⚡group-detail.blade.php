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
        
        <div class="flex gap-2">
            @if(!$isObstacle)
            <a href="#" class="bg-primary hover:bg-primary/90 text-white text-sm font-bold py-2.5 px-4 rounded-xl transition-colors flex items-center gap-2 shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                Jana Jadual Perlawanan
            </a>
            @else
            <a href="#" class="bg-primary hover:bg-primary/90 text-white text-sm font-bold py-2.5 px-4 rounded-xl transition-colors flex items-center gap-2 shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                Rekod Masa
            </a>
            @endif
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
                    <div class="bg-base-200/50 px-5 py-3 border-b border-base-200 flex justify-between items-center">
                        <h3 class="font-extrabold text-lg text-primary">{{ $group->group_name }}</h3>
                        <span class="text-xs font-bold bg-white px-2.5 py-1 rounded-full text-base-content/60 shadow-sm">{{ $group->groupTeams->count() }} Pasukan</span>
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
