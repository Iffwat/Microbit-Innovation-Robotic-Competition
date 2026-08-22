<?php
use App\Models\Category;
use App\Models\Team;
use App\Models\Group;
use App\Models\GroupTeam;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
?>

<div class="space-y-6 animate-slide-up">

    
    <div class="flex gap-2 overflow-x-auto pb-2">
        <button wire:click="switchTab('isobot')" 
                class="px-5 py-2.5 rounded-xl text-sm font-bold transition-all shrink-0 <?php echo e($activeTab === 'isobot' ? 'bg-blue-600 text-white shadow-md shadow-blue-600/20' : 'bg-white border border-base-200 text-base-content/60 hover:bg-base-200'); ?>">
            🤖 Isobot Soccer
        </button>
        <button wire:click="switchTab('sky_soccer')" 
                class="px-5 py-2.5 rounded-xl text-sm font-bold transition-all shrink-0 <?php echo e($activeTab === 'sky_soccer' ? 'bg-violet-600 text-white shadow-md shadow-violet-600/20' : 'bg-white border border-base-200 text-base-content/60 hover:bg-base-200'); ?>">
            🚁 Drone Sky Soccer
        </button>
        <button wire:click="switchTab('obstacle')" 
                class="px-5 py-2.5 rounded-xl text-sm font-bold transition-all shrink-0 <?php echo e($activeTab === 'obstacle' ? 'bg-amber-500 text-white shadow-md shadow-amber-500/20' : 'bg-white border border-base-200 text-base-content/60 hover:bg-base-200'); ?>">
            🏁 Drone Obstacle
        </button>
    </div>

    
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(app()->environment('local')): ?>
    <div class="bg-amber-50 border-2 border-amber-300 border-dashed rounded-2xl p-4">
        <div class="flex items-center gap-2 mb-3">
            <span class="bg-amber-400 text-amber-900 text-[10px] font-extrabold px-2 py-0.5 rounded uppercase tracking-widest">DEV ONLY</span>
            <p class="text-sm font-bold text-amber-800">Alat Pembangunan — Tidak akan muncul dalam Production</p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $this->categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $category): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
            <div class="bg-white rounded-xl border border-amber-200 px-4 py-3 flex items-center justify-between gap-3">
                <div>
                    <p class="font-bold text-sm text-base-content"><?php echo e($category->name); ?></p>
                    <p class="text-xs text-base-content/50"><?php echo e($category->checked_in_teams); ?> hadir / <?php echo e(\App\Models\Team::where('category_id', $category->id)->where('game_type', $activeTab)->count()); ?> jumlah</p>
                </div>
                <div class="flex gap-2">
                    <button wire:click="devBulkCheckin(<?php echo e($category->id); ?>)"
                            wire:loading.attr="disabled"
                            class="text-xs font-bold bg-emerald-100 text-emerald-700 hover:bg-emerald-200 px-3 py-1.5 rounded-lg transition-colors">
                        ✓ Hadir Semua
                    </button>
                    <button wire:click="devResetCheckin(<?php echo e($category->id); ?>)"
                            wire:loading.attr="disabled"
                            class="text-xs font-bold bg-red-100 text-red-600 hover:bg-red-200 px-3 py-1.5 rounded-lg transition-colors">
                        ↩ Reset
                    </button>
                </div>
            </div>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
        </div>
    </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    <?php
        $gameLabel = match($activeTab) {
            'isobot' => 'Isobot Soccer',
            'sky_soccer' => 'Drone Sky Soccer',
            'obstacle' => 'Drone Obstacle',
            default => 'Permainan'
        };
        $isObstacle = $activeTab === 'obstacle';
    ?>

    
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-extrabold text-base-content">Pengurusan Kumpulan — <?php echo e($gameLabel); ?></h2>
            <p class="text-sm text-base-content/60 mt-1">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($isObstacle): ?> Jana laluan (course) untuk pengiraan masa. <?php else: ?> Jana kumpulan bagi pusingan liga. <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>.
            </p>
        </div>
    </div>

    
    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $this->categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $category): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
        <div class="bg-white border <?php echo e($category->groups_count > 0 ? 'border-emerald-200' : 'border-base-200'); ?> rounded-2xl p-6 shadow-sm relative overflow-hidden transition-all hover:shadow-md">

            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($category->groups_count > 0): ?>
                <div class="absolute top-0 right-0 bg-emerald-500 text-white text-[10px] font-bold px-3 py-1 rounded-bl-xl uppercase tracking-widest">
                    Telah Dijana
                </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

            <div class="flex items-start justify-between mb-4">
                <div>
                    <h3 class="text-lg font-bold text-base-content"><?php echo e($category->name); ?></h3>
                    <p class="text-xs text-base-content/50 mt-0.5 uppercase tracking-widest font-semibold"><?php echo e($category->format_label); ?></p>
                </div>
                <div class="text-right">
                    <p class="text-3xl font-extrabold <?php echo e($category->checked_in_teams > 0 ? 'text-emerald-600' : 'text-base-content/30'); ?>">
                        <?php echo e($category->checked_in_teams); ?>

                    </p>
                    <p class="text-[10px] text-base-content/40 uppercase tracking-widest font-bold mt-0.5">Pasukan Hadir</p>
                </div>
            </div>

            <div class="bg-base-200/50 rounded-xl p-4 mb-5 flex justify-between items-center">
                <div>
                    <p class="text-xs text-base-content/50 font-medium"><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($isObstacle): ?> Laluan Dijana <?php else: ?> Saiz Kumpulan <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?></p>
                    <p class="text-sm font-bold text-base-content"><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($isObstacle): ?> 2 Laluan (Course) <?php else: ?> <?php echo e($category->teams_per_group); ?> pasukan / kump. <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?></p>
                </div>
                <div class="text-right">
                    <p class="text-xs text-base-content/50 font-medium"><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($isObstacle): ?> Status Janaan <?php else: ?> Kumpulan Dijana <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?></p>
                    <p class="text-sm font-bold <?php echo e($category->groups_count > 0 ? 'text-emerald-600' : 'text-base-content/40'); ?>">
                        <?php echo e($category->groups_count > 0 ? $category->groups_count . ($isObstacle ? ' Laluan' : ' Kumpulan') : '—'); ?>

                    </p>
                </div>
                <div class="text-right">
                    <p class="text-xs text-base-content/50 font-medium">Padang</p>
                    <p class="text-sm font-bold text-base-content"><?php echo e($category->fields_count); ?></p>
                </div>
            </div>

            <div class="flex gap-2">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($category->groups_count == 0): ?>
                    <button wire:click="generateGroups(<?php echo e($category->id); ?>)" wire:loading.attr="disabled"
                            wire:confirm="Jana <?php if($isObstacle): ?> laluan <?php else: ?> kumpulan <?php endif; ?> untuk <?php echo e($category->name); ?>? Hanya pasukan yang HADIR akan dimasukkan."
                            class="flex-1 bg-primary hover:bg-primary/90 text-white text-sm font-bold py-3 px-4 rounded-xl transition-colors flex justify-center items-center gap-2 disabled:opacity-50">
                        <span wire:loading.remove wire:target="generateGroups(<?php echo e($category->id); ?>)">
                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                            Jana <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($isObstacle): ?> Laluan <?php else: ?> Kumpulan <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </span>
                        <span wire:loading wire:target="generateGroups(<?php echo e($category->id); ?>)">Menjana...</span>
                    </button>
                <?php else: ?>
                    <a href="<?php echo e(route('admin.groups.show', ['game' => $activeTab, 'category' => $category->slug])); ?>"
                       class="flex-1 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 border border-emerald-200 text-sm font-bold py-3 px-4 rounded-xl transition-colors flex justify-center items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        Lihat & Urus <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($isObstacle): ?> Laluan <?php else: ?> Kumpulan <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </a>
                    <button wire:click="deleteGroups(<?php echo e($category->id); ?>)"
                            wire:confirm="AMARAN! Padam semua rekod untuk <?php echo e($category->name); ?>?"
                            class="bg-white border-2 border-red-200 text-red-500 hover:bg-red-50 text-sm font-bold p-3 rounded-xl transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>
        </div>
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
    </div>
</div><?php /**PATH C:\laragon\ElviraSdnBhd\Tournament-Management-System\storage\framework\views/livewire/views/8c94df9a.blade.php ENDPATH**/ ?>