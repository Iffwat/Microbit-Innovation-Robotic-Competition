<?php
use App\Models\Category;
use App\Models\Team;
use Livewire\Attributes\Computed;
use Livewire\Component;
?>

<div wire:poll.10s="refreshStats" class="space-y-6 animate-slide-up">
    
    
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

    <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
        <div>
            <?php
                $gameLabel = match($activeTab) {
                    'isobot' => 'Isobot Soccer',
                    'sky_soccer' => 'Drone Sky Soccer',
                    'obstacle' => 'Drone Obstacle',
                    default => 'Permainan'
                };
            ?>
            <h2 class="text-xl font-extrabold text-base-content">Papan Pemuka Kehadiran — <?php echo e($gameLabel); ?></h2>
            <p class="text-xs text-base-content/40 mt-0.5">Dikemaskini setiap 10 saat</p>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            <button wire:click="resetAttendance" 
                    wire:confirm="AMARAN: Anda pasti mahu RESET semua kehadiran bagi permainan ini semula kepada status Berdaftar?"
                    class="inline-flex items-center gap-1.5 bg-white border-2 border-red-200 text-red-500 hover:bg-red-50 text-sm font-bold px-4 py-2.5 rounded-xl transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                Reset Kehadiran
            </button>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!$isLocked): ?>
                <button wire:click="lockAttendance" wire:confirm="Anda pasti mahu KUNCI kehadiran?"
                        class="inline-flex items-center gap-2 bg-red-500 hover:bg-red-600 text-white text-sm font-bold px-5 py-2.5 rounded-xl transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                    Kunci Kehadiran
                </button>
            <?php else: ?>
                <div class="flex items-center gap-3">
                    <div class="flex items-center gap-2 bg-red-50 border border-red-200 text-red-700 text-sm font-bold px-4 py-2 rounded-xl">
                        🔒 Kehadiran Dikunci
                    </div>
                    <button wire:click="unlockAttendance" class="text-sm font-medium text-base-content/50 hover:text-base-content px-3 py-2 rounded-xl hover:bg-base-200 transition-colors">Buka Kunci</button>
                </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>
    </div>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl p-5 border border-base-200 shadow-sm">
            <div class="w-10 h-10 bg-primary/10 rounded-xl flex items-center justify-center mb-3">
                <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0"/></svg>
            </div>
            <p class="text-3xl font-extrabold text-base-content"><?php echo e($this->totals['total']); ?></p>
            <p class="text-xs text-base-content/50 mt-1 font-medium">Jumlah Daftar</p>
        </div>
        <div class="bg-white rounded-2xl p-5 border border-emerald-100 shadow-sm">
            <div class="w-10 h-10 bg-emerald-50 rounded-xl flex items-center justify-center mb-3">
                <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            </div>
            <p class="text-3xl font-extrabold text-emerald-600"><?php echo e($this->totals['checked_in']); ?></p>
            <p class="text-xs text-emerald-600/60 mt-1 font-medium">Hadir — <?php echo e($this->totals['total'] > 0 ? round(($this->totals['checked_in'] / $this->totals['total']) * 100) : 0); ?>%</p>
        </div>
        <div class="bg-white rounded-2xl p-5 border border-red-100 shadow-sm">
            <div class="w-10 h-10 bg-red-50 rounded-xl flex items-center justify-center mb-3">
                <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </div>
            <p class="text-3xl font-extrabold text-red-500"><?php echo e($this->totals['absent']); ?></p>
            <p class="text-xs text-red-500/60 mt-1 font-medium">Tidak Hadir — <?php echo e($this->totals['total'] > 0 ? round(($this->totals['absent'] / $this->totals['total']) * 100) : 0); ?>%</p>
        </div>
        <div class="bg-white rounded-2xl p-5 border border-amber-100 shadow-sm">
            <div class="w-10 h-10 bg-amber-50 rounded-xl flex items-center justify-center mb-3">
                <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <p class="text-3xl font-extrabold text-amber-500"><?php echo e($this->totals['pending']); ?></p>
            <p class="text-xs text-amber-500/60 mt-1 font-medium">Belum Ditanda</p>
        </div>
    </div>

    <div>
        <h3 class="text-xs font-bold text-base-content/40 uppercase tracking-widest mb-3">Mengikut Kategori</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $this->stats; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cat): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
            <?php $pct = $cat->teams_count > 0 ? round(($cat->checked_in_count / $cat->teams_count) * 100) : 0; ?>
            <div class="bg-white rounded-2xl border border-base-200 shadow-sm p-5">
                <div class="flex justify-between items-center mb-3">
                    <h4 class="font-bold text-base-content text-sm"><?php echo e($cat->name); ?></h4>
                    <span class="text-xs font-bold text-primary bg-primary/10 px-2 py-0.5 rounded-full"><?php echo e($cat->checked_in_count); ?>/<?php echo e($cat->teams_count); ?></span>
                </div>
                <div class="w-full bg-base-200 rounded-full h-3 mb-3 overflow-hidden">
                    <div class="h-3 rounded-full bg-gradient-to-r from-emerald-400 to-emerald-600 transition-all duration-700" style="width: <?php echo e($pct); ?>%"></div>
                </div>
                <div class="flex justify-between text-xs font-semibold">
                    <span class="text-emerald-600">✓ <?php echo e($cat->checked_in_count); ?> Hadir</span>
                    <span class="text-red-500">✕ <?php echo e($cat->absent_count); ?> Tidak</span>
                    <span class="text-amber-500">⏳ <?php echo e($cat->pending_count); ?> Belum</span>
                    <span class="text-primary font-bold"><?php echo e($pct); ?>%</span>
                </div>
            </div>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
        </div>
    </div>
</div><?php /**PATH C:\laragon\ElviraSdnBhd\Tournament-Management-System\storage\framework\views/livewire/views/3a353d6c.blade.php ENDPATH**/ ?>