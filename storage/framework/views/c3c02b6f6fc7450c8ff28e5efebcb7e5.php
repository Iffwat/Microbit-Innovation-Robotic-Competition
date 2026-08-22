<?php
use App\Models\Category;
use App\Models\Team;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
?>

<div class="space-y-5 animate-slide-up">
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($message): ?>
    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)" x-transition:leave="transition ease-in duration-300" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
         class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium border animate-fade-in <?php echo e($messageType === 'success' ? 'bg-emerald-50 border-emerald-200 text-emerald-800' : ($messageType === 'error' ? 'bg-red-50 border-red-200 text-red-800' : 'bg-amber-50 border-amber-200 text-amber-800')); ?>">
        <span><?php echo e($message); ?></span>
    </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($isLocked): ?>
    <div class="flex items-center gap-3 bg-red-50 border border-red-200 rounded-2xl px-5 py-3">
        <p class="font-bold text-red-700 text-sm">🔒 Kehadiran Dikunci — Hubungi Master Admin untuk buka kunci.</p>
    </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    
    <div class="bg-white rounded-2xl border border-base-200 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-base-200 flex items-center justify-between">
            <h2 class="font-bold text-base-content">Cari & Tandakan Kehadiran</h2>
            <span class="text-xs text-base-content/50">Kaunter Pendaftaran & Semakan</span>
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
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $this->categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cat): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                    <option value="<?php echo e($cat->slug); ?>"><?php echo e($cat->name); ?></option>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
            </select>
        </div>
    </div>

    <div wire:loading.flex wire:target="search" class="items-center gap-2 text-primary text-sm">
        <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
        Mencari pasukan...
    </div>

    
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(strlen(trim($search)) >= 2): ?>
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($this->results->isEmpty()): ?>
            <div class="bg-blue-50 border border-blue-100 rounded-2xl px-5 py-4 text-sm text-blue-700">
                Tiada pasukan dijumpai untuk carian "<strong><?php echo e($search); ?></strong>"
            </div>
        <?php else: ?>
            <div class="space-y-3">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $this->results; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $team): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                <div <?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::$currentLoop['key'] = 'team-'.e($team->id).''; ?>wire:key="team-<?php echo e($team->id); ?>"
                     class="bg-white rounded-2xl border-2 shadow-sm transition-all duration-200
                     <?php echo e($team->status === 'checked_in' ? 'border-emerald-300 bg-emerald-50/30' : ($team->status === 'absent' ? 'border-red-200 bg-red-50/20' : 'border-base-200')); ?>

                     <?php echo e($highlightedId === $team->id ? 'ring-4 ring-emerald-400 ring-offset-2' : ''); ?>">
                    <div class="p-5 flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div class="flex-1">
                            <div class="flex items-center gap-2 flex-wrap">
                                <h3 class="font-bold text-base text-base-content"><?php echo e($team->team_name); ?></h3>
                                <span class="text-xs font-semibold bg-primary/10 text-primary px-2.5 py-0.5 rounded-full"><?php echo e($team->game_type_label); ?></span>
                                <span class="text-xs font-medium bg-base-200 text-base-content/60 px-2 py-0.5 rounded-full"><?php echo e($team->category->name); ?></span>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($team->status === 'checked_in'): ?> <span class="status-badge-present">● Hadir</span>
                                <?php elseif($team->status === 'absent'): ?> <span class="status-badge-absent">● Tidak Hadir</span>
                                <?php else: ?> <span class="status-badge-pending">● Berdaftar</span>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>
                            <p class="text-xs text-base-content/60 mt-1 font-medium">🏫 <?php echo e($team->school_name); ?></p>
                            
                            
                            <div class="flex items-center gap-3 mt-2 text-xs text-base-content/70 flex-wrap">
                                <span class="font-semibold text-base-content/40 uppercase tracking-wider text-[10px]">Pemain:</span>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($team->player_1): ?> <span><strong>1.</strong> <?php echo e($team->player_1); ?></span> <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($team->player_2): ?> <span><strong>2.</strong> <?php echo e($team->player_2); ?></span> <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($team->player_3): ?> <span class="text-base-content/50"><strong>3.</strong> <?php echo e($team->player_3); ?> (Rizab)</span> <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>

                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($team->checked_in_at): ?>
                                <p class="text-xs text-emerald-600 mt-2 font-medium">✓ Ditanda hadir pada <?php echo e($team->checked_in_at->format('h:i A')); ?></p>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </div>

                        
                        <div class="flex gap-2 flex-wrap items-center shrink-0">
                            
                            <button wire:click="openEdit(<?php echo e($team->id); ?>)" 
                                    title="Tukar nama pasukan atau nama pemain di kaunter"
                                    class="inline-flex items-center gap-1.5 border border-base-300 hover:border-primary hover:text-primary text-base-content/70 text-xs font-bold px-3.5 py-2 rounded-xl transition-all hover:bg-primary/5">
                                ✏️ Tukar Pemain
                            </button>

                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!$isLocked): ?>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($team->status !== 'checked_in'): ?>
                                    <button wire:click="checkIn(<?php echo e($team->id); ?>)" wire:loading.attr="disabled"
                                            class="inline-flex items-center gap-1.5 bg-emerald-500 hover:bg-emerald-600 text-white text-xs font-bold px-4 py-2 rounded-xl shadow-sm transition-colors">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        Tandakan Hadir
                                    </button>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($team->status !== 'absent'): ?>
                                    <button wire:click="markAbsent(<?php echo e($team->id); ?>)" wire:loading.attr="disabled"
                                            class="inline-flex items-center gap-1.5 border-2 border-red-200 text-red-500 hover:bg-red-50 text-xs font-bold px-3.5 py-2 rounded-xl transition-colors">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                        Tidak Hadir
                                    </button>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($team->status !== 'registered'): ?>
                                    <button wire:click="undoStatus(<?php echo e($team->id); ?>)" wire:confirm="Anda pasti mahu tukar semula status pasukan ini?"
                                            class="text-xs font-medium text-base-content/40 hover:text-base-content px-2.5 py-2 rounded-xl hover:bg-base-200 transition-colors">
                                        ↩ Undo
                                    </button>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
            </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    <?php else: ?>
        <div class="flex flex-col items-center gap-4 py-16 text-base-content/30">
            <div class="w-20 h-20 bg-base-200 rounded-3xl flex items-center justify-center">
                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>
            <p class="text-base font-medium">Taip sekurang-kurangnya 2 huruf untuk mencari pasukan</p>
        </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    
    
    
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($isEditing): ?>
        <div class="fixed inset-0 z-50 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-white rounded-3xl border border-base-200 shadow-2xl max-w-xl w-full overflow-hidden animate-scale-in">
                <div class="px-6 py-4 border-b border-base-200 flex items-center justify-between bg-base-100">
                    <div class="flex items-center gap-2">
                        <span class="text-xl">✏️</span>
                        <div>
                            <h3 class="font-bold text-base text-base-content">Kemaskini Pasukan & Tukar Pemain</h3>
                            <p class="text-xs text-base-content/50">Pertukaran maklumat rasmi pasukan semasa semak masuk</p>
                        </div>
                    </div>
                    <button wire:click="closeEdit" class="text-base-content/40 hover:text-base-content text-xl font-bold p-1">✕</button>
                </div>

                <form wire:submit="saveTeam" class="p-6 space-y-4 max-h-[80vh] overflow-y-auto">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-base-content mb-1">Nama Pasukan <span class="text-red-500">*</span></label>
                            <input wire:model="editTeamName" type="text" required
                                   class="w-full px-3.5 py-2 border-2 border-base-300 rounded-xl text-sm font-bold focus:border-primary focus:outline-none" />
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['editTeamName'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="text-xs text-red-500"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-base-content mb-1">Nama Sekolah <span class="text-red-500">*</span></label>
                            <input wire:model="editSchoolName" type="text" required
                                   class="w-full px-3.5 py-2 border-2 border-base-300 rounded-xl text-sm focus:border-primary focus:outline-none" />
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['editSchoolName'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="text-xs text-red-500"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </div>
                    </div>

                    
                    <div class="bg-base-200/50 rounded-2xl p-4 space-y-3">
                        <p class="text-[11px] font-black uppercase tracking-wider text-base-content/50">Senarai Nama Pemain</p>
                        <div>
                            <label class="block text-xs font-semibold text-base-content mb-1">Pemain 1 (Kapten)</label>
                            <input wire:model="editPlayer1" type="text" placeholder="Nama penuh pemain 1"
                                   class="w-full px-3.5 py-2 bg-white border border-base-300 rounded-xl text-sm focus:border-primary focus:outline-none" />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-base-content mb-1">Pemain 2</label>
                            <input wire:model="editPlayer2" type="text" placeholder="Nama penuh pemain 2"
                                   class="w-full px-3.5 py-2 bg-white border border-base-300 rounded-xl text-sm focus:border-primary focus:outline-none" />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-base-content mb-1">Pemain 3 (Rizab / Simpanan)</label>
                            <input wire:model="editPlayer3" type="text" placeholder="Nama penuh pemain 3"
                                   class="w-full px-3.5 py-2 bg-white border border-base-300 rounded-xl text-sm focus:border-primary focus:outline-none" />
                        </div>
                    </div>

                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-base-content mb-1">Nama Guru / Mentor</label>
                            <input wire:model="editMentorName" type="text" placeholder="Nama guru pengiring"
                                   class="w-full px-3.5 py-2 border border-base-300 rounded-xl text-sm focus:border-primary focus:outline-none" />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-base-content mb-1">No. Telefon / Emel</label>
                            <input wire:model="editMentorEmail" type="text" placeholder="012-3456789"
                                   class="w-full px-3.5 py-2 border border-base-300 rounded-xl text-sm focus:border-primary focus:outline-none" />
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-base-200">
                        <button type="button" wire:click="closeEdit" class="px-4 py-2 text-sm font-semibold text-base-content/60 hover:text-base-content rounded-xl hover:bg-base-200 transition-colors">
                            Batal
                        </button>
                        <button type="submit" wire:loading.attr="disabled"
                                class="inline-flex items-center gap-2 bg-primary hover:bg-primary/90 text-white text-sm font-bold px-5 py-2.5 rounded-xl shadow-lg transition-colors">
                            <svg wire:loading.remove wire:target="saveTeam" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            <svg wire:loading wire:target="saveTeam" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                            Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
</div><?php /**PATH C:\laragon\ElviraSdnBhd\Tournament-Management-System\storage\framework\views/livewire/views/0f168572.blade.php ENDPATH**/ ?>