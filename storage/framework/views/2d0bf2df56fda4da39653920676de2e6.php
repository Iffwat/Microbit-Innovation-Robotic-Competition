<?php
use Livewire\Component;
use App\Models\Category;
use App\Models\Group;
use App\Models\GroupTeam;
use App\Models\TournamentMatch;
use Illuminate\Support\Facades\DB;
?>

<div class="space-y-6 animate-slide-up">
    <!-- Header -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-white border border-base-200 rounded-3xl p-6 shadow-sm">
        <div>
            <h1 class="text-2xl font-black text-base-content">🏆 Pengurusan Kalah Mati</h1>
            <p class="text-sm text-base-content/60 mt-1">Jana dan pantau carta pusingan kalah mati (Knockout Brackets) selepas tamat peringkat kumpulan.</p>
        </div>
    </div>

    <!-- Notifications -->
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(session('success')): ?>
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-5 py-4 rounded-2xl flex items-center gap-3">
            <svg class="w-5 h-5 text-emerald-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            <span class="font-bold text-sm"><?php echo e(session('success')); ?></span>
        </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(session('error')): ?>
        <div class="bg-red-50 border border-red-200 text-red-800 px-5 py-4 rounded-2xl flex items-center gap-3">
            <svg class="w-5 h-5 text-red-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            <span class="font-bold text-sm"><?php echo e(session('error')); ?></span>
        </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    <!-- Category Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $category): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
            <?php
                $hasKnockouts = \App\Models\TournamentMatch::where('category_id', $category->id)->whereIn('stage', ['trophy_knockout', 'cup_knockout'])->exists();
                $groups = \App\Models\Group::where('category_id', $category->id)->where('game_type', '!=', 'obstacle')->get();
                $hasGroups = $groups->isNotEmpty();
            ?>
            <div class="bg-white rounded-3xl border border-base-200 shadow-sm overflow-hidden flex flex-col justify-between hover:border-primary/50 transition-all">
                <div class="p-6">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-2xl bg-primary/10 text-primary flex items-center justify-center font-black">
                            <?php echo e(substr($category->name, 0, 1)); ?>

                        </div>
                        <h3 class="font-bold text-lg text-base-content"><?php echo e($category->name); ?></h3>
                    </div>
                    
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($hasGroups): ?>
                        <div class="space-y-2 mt-4">
                            <div class="flex justify-between items-center bg-base-50 px-4 py-2 rounded-xl text-sm">
                                <span class="text-base-content/60 font-medium">Jumlah Kumpulan:</span>
                                <span class="font-bold text-base-content"><?php echo e($groups->count()); ?> (<?php echo e($groups->pluck('group_letter')->join(', ')); ?>)</span>
                            </div>
                            <div class="flex justify-between items-center bg-base-50 px-4 py-2 rounded-xl text-sm">
                                <span class="text-base-content/60 font-medium">Status Kalah Mati:</span>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($hasKnockouts): ?>
                                    <span class="font-bold text-primary">Telah Dijana</span>
                                <?php else: ?>
                                    <span class="font-bold text-amber-500">Belum Dijana</span>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <p class="text-sm text-base-content/50 mt-4 italic">Kategori ini tidak mempunyai peringkat liga/kumpulan.</p>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>

                <div class="p-4 bg-base-50 border-t border-base-200">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!$hasGroups): ?>
                        <button class="w-full bg-base-200 text-base-content/40 font-bold py-2.5 rounded-xl cursor-not-allowed text-sm">Tiada Sokongan</button>
                    <?php elseif($hasKnockouts): ?>
                        <div class="flex gap-2">
                            <a href="<?php echo e(route('admin.knockout.show', $category->id)); ?>" class="flex-1 text-center bg-white border-2 border-primary text-primary hover:bg-primary hover:text-white font-bold py-2.5 rounded-xl transition-colors text-sm">
                                Lihat Carta
                            </a>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(session('auth_role') === 'master'): ?>
                            <button wire:click="selectCategory(<?php echo e($category->id); ?>)" class="px-3 bg-base-200 hover:bg-primary/10 hover:text-primary text-base-content/60 font-bold rounded-xl transition-colors text-sm" title="Jana Semula">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            </button>
                            <button wire:click="deleteKnockout(<?php echo e($category->id); ?>)" 
                                    wire:confirm="AMARAN: Anda pasti mahu MEMADAM seluruh carta kalah mati untuk <?php echo e($category->name); ?>?"
                                    class="px-3 bg-base-200 hover:bg-red-100 hover:text-red-600 text-base-content/60 font-bold rounded-xl transition-colors text-sm" title="Padam Kalah Mati">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </div>
                      <?php else: ?>
                          <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(session('auth_role') === 'master'): ?>
                          <button wire:click="selectCategory(<?php echo e($category->id); ?>)" class="w-full bg-primary hover:bg-primary/90 text-white font-bold py-2.5 rounded-xl shadow-lg shadow-primary/20 transition-colors text-sm">
                              Jana Kalah Mati
                          </button>
                          <?php else: ?>
                          <button disabled class="w-full bg-base-200 text-base-content/40 font-bold py-2.5 rounded-xl cursor-not-allowed text-sm">
                              Menunggu Admin
                          </button>
                          <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                      <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>
            </div>
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
    </div>

    <!-- Generator Modal -->
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($selectedCategory): ?>
        <?php
            $cat = \App\Models\Category::find($selectedCategory);
            $hasKnockouts = \App\Models\TournamentMatch::where('category_id', $cat->id)->whereIn('stage', ['trophy_knockout', 'cup_knockout'])->exists();
            $groupCount = \App\Models\Group::where('category_id', $cat->id)->where('game_type', '!=', 'obstacle')->count();
        ?>
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm">
            <div class="bg-white rounded-3xl shadow-2xl w-full max-w-lg overflow-hidden animate-slide-up">
                <div class="p-6 border-b border-base-200 bg-base-50">
                    <h3 class="font-black text-xl text-base-content">Jana Perlawanan: <?php echo e($cat->name); ?></h3>
                </div>
                
                <div class="p-6 space-y-6">
                    <div class="bg-blue-50 border border-blue-200 text-blue-800 p-4 rounded-2xl text-sm leading-relaxed">
                        Kategori ini mempunyai <strong><?php echo e($groupCount); ?> Kumpulan</strong>. Sistem akan menyusun perlawanan secara silang (Piawaian FIFA) berdasarkan kedudukan terkini peringkat kumpulan.
                    </div>

                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($hasKnockouts): ?>
                        <div class="bg-red-50 border border-red-200 text-red-700 p-4 rounded-2xl text-sm font-medium flex items-start gap-3">
                            <svg class="w-5 h-5 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                            AMARAN: Data kalah mati (beserta skor) yang sedia ada untuk kategori ini akan dipadam dan dijana semula secara automatik jika anda meneruskan.
                        </div>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                    <div class="space-y-3">
                        <button wire:click="generateKnockout(<?php echo e($cat->id); ?>, false)" class="w-full bg-primary hover:bg-primary/90 text-white font-bold py-4 rounded-2xl shadow-lg shadow-primary/20 transition-all flex flex-col items-center justify-center gap-1">
                            <span>Jana Pusingan Trofi Sahaja</span>
                            <span class="text-xs font-medium text-white/70">(Juara & Naib Juara Kumpulan sahaja)</span>
                        </button>
                        
                        <button wire:click="generateKnockout(<?php echo e($cat->id); ?>, true)" class="w-full bg-secondary hover:bg-secondary/90 text-white font-bold py-4 rounded-2xl shadow-lg shadow-secondary/20 transition-all flex flex-col items-center justify-center gap-1">
                            <span>Jana Trofi & Piala (Khas U12)</span>
                            <span class="text-xs font-medium text-white/70">(Trofi: Top 2 | Piala: Tempat 3 & 4)</span>
                        </button>

                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($hasKnockouts): ?>
                        <button wire:click="deleteKnockout(<?php echo e($cat->id); ?>)" 
                                wire:confirm="AMARAN: Anda pasti mahu MEMADAM seluruh carta kalah mati untuk <?php echo e($cat->name); ?>?"
                                class="w-full bg-white border-2 border-red-200 text-red-600 hover:bg-red-50 font-bold py-3 rounded-2xl transition-all flex items-center justify-center gap-2 text-sm">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            <span>Padam Carta Kalah Mati Sedia Ada</span>
                        </button>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>
                </div>

                <div class="p-4 border-t border-base-200 bg-base-50 flex justify-end">
                    <button wire:click="$set('selectedCategory', null)" class="px-6 py-2.5 bg-base-200 hover:bg-base-300 text-base-content font-bold rounded-xl transition-colors text-sm">
                        Batal
                    </button>
                </div>
            </div>
        </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
</div><?php /**PATH C:\laragon\ElviraSdnBhd\Tournament-Management-System\storage\framework\views/livewire/views/649125ac.blade.php ENDPATH**/ ?>