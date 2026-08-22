<?php
use App\Models\TournamentMatch;
use App\Models\Category;
use App\Models\Group;
use App\Models\GroupTeam;
use App\Models\Team;
use Livewire\Attributes\Computed;
use Livewire\Component;
?>

<div class="h-full grid grid-cols-[380px_1fr] gap-6" wire:poll.5s>

    
    
    
    <div class="flex flex-col gap-4 h-full min-h-0"
         x-data="{
            activeLeft: 0,
            totalLeft: <?php echo e($this->fieldChunks()->count()); ?>,
            init() {
                if (this.totalLeft > 1) {
                    setInterval(() => {
                        this.activeLeft = (this.activeLeft + 1) % this.totalLeft;
                    }, 10000);
                }
            }
         }">

        
        <div class="flex items-center justify-between shrink-0 bg-white/5 border border-white/10 px-4 py-2.5 rounded-2xl backdrop-blur-md">
            <div class="flex items-center gap-2.5">
                <div class="w-2.5 h-2.5 rounded-full bg-red-500 animate-ping"></div>
                <h2 class="text-xs font-black text-white tracking-[0.2em] uppercase">Status Padang</h2>
            </div>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($this->fieldChunks()->count() > 1): ?>
                <div class="flex items-center gap-1.5">
                    <template x-for="i in totalLeft" :key="i">
                        <div class="rounded-full h-1.5 transition-all duration-300"
                             :class="activeLeft === (i-1) ? 'w-5 bg-primary' : 'w-1.5 bg-white/20'"></div>
                    </template>
                </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>

        
        <div class="flex-1 min-h-0 relative">
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($this->fieldChunks()->isEmpty()): ?>
                <div class="h-full flex flex-col items-center justify-center gap-3 border-2 border-dashed border-white/10 rounded-2xl bg-white/[0.02]">
                    <div class="w-12 h-12 rounded-full bg-white/5 flex items-center justify-center">
                        <svg class="w-6 h-6 text-white/30" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    </div>
                    <p class="text-white/40 text-xs font-black tracking-widest uppercase">Tiada Perlawanan Aktif</p>
                </div>
            <?php else: ?>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $this->fieldChunks(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $idx => $chunk): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                    <div x-show="activeLeft === <?php echo e($idx); ?>"
                         x-transition:enter="transition ease-out duration-500"
                         x-transition:enter-start="opacity-0 translate-y-3"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         x-transition:leave="transition ease-in duration-300"
                         x-transition:leave-start="opacity-100 translate-y-0"
                         x-transition:leave-end="opacity-0 -translate-y-3"
                         class="absolute inset-0 flex flex-col gap-3 overflow-y-auto pr-1"
                         style="display:none; scrollbar-width:none;">

                        <?php $active = $chunk['active']; $upcoming = $chunk['upcoming']; ?>

                        
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($active->isNotEmpty()): ?>
                            <div class="flex items-center gap-2 px-1">
                                <span class="w-2 h-2 rounded-full bg-red-500 animate-pulse"></span>
                                <p class="text-[10px] font-black text-red-400 tracking-[0.2em] uppercase">Sedang Berlangsung</p>
                                <div class="flex-1 h-px bg-red-500/20"></div>
                            </div>

                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $active; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $match): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                <div class="relative rounded-2xl overflow-hidden border-2 border-primary/60 bg-gradient-to-br from-primary/20 via-black/80 to-black/90 shadow-lg shadow-primary/10">
                                    <div class="absolute inset-0 rounded-2xl border-2 border-primary/40 animate-pulse pointer-events-none"></div>
                                    
                                    
                                    <div class="flex items-center justify-between px-3.5 py-2 bg-primary/30 border-b border-primary/30">
                                        <div class="flex items-center gap-2">
                                            <span class="text-[10px] font-black bg-red-600 text-white px-2 py-0.5 rounded uppercase tracking-wider animate-pulse">LIVE</span>
                                            <span class="text-xs font-black text-yellow-300 uppercase tracking-wide">
                                                <?php echo e(is_numeric($match->field_number) ? 'PADANG '.$match->field_number : $match->field_number); ?>

                                            </span>
                                        </div>
                                        <span class="text-[10px] font-bold text-white/70 uppercase tracking-widest bg-black/40 px-2 py-0.5 rounded border border-white/10">
                                            <?php echo e(optional(optional($match->group)->category)->name ?? optional($match->category)->name ?? ''); ?>

                                        </span>
                                    </div>

                                    
                                    <div class="p-3.5">
                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($match->group && $match->group->game_type === 'obstacle'): ?>
                                            <div class="flex items-center justify-between gap-3">
                                                <div class="overflow-hidden">
                                                    <p class="font-black text-white text-base leading-tight truncate"><?php echo e($match->homeTeam->team_name ?? 'BYE'); ?></p>
                                                    <p class="text-[10px] text-white/50 mt-0.5 truncate uppercase font-bold"><?php echo e($match->homeTeam->school_name ?? ''); ?></p>
                                                </div>
                                                <span class="text-[10px] font-black text-emerald-400 bg-emerald-500/20 px-2.5 py-1 rounded-lg border border-emerald-500/30 shrink-0 uppercase tracking-wider">
                                                    Berlari
                                                </span>
                                            </div>
                                        <?php else: ?>
                                            <div class="grid grid-cols-[1fr_auto_1fr] gap-2.5 items-center">
                                                
                                                <div class="text-right overflow-hidden">
                                                    <div class="flex items-center justify-end gap-1.5">
                                                        <p class="font-black text-rose-200 text-xs leading-tight truncate"><?php echo e($match->homeTeam->team_name ?? 'BYE'); ?></p>
                                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500 shrink-0 shadow-sm shadow-rose-500/50"></span>
                                                    </div>
                                                    <p class="text-[9px] text-white/50 mt-0.5 truncate font-bold uppercase"><?php echo e($match->homeTeam->school_name ?? ''); ?></p>
                                                </div>

                                                
                                                <div class="flex items-center gap-1.5 bg-black/90 px-2.5 py-1 rounded-xl border border-white/20 shadow-inner">
                                                    <span class="text-lg font-black text-rose-400 tabular-nums"><?php echo e($match->home_score ?? 0); ?></span>
                                                    <span class="text-white/30 font-black text-xs">-</span>
                                                    <span class="text-lg font-black text-sky-400 tabular-nums"><?php echo e($match->away_score ?? 0); ?></span>
                                                </div>

                                                
                                                <div class="text-left overflow-hidden">
                                                    <div class="flex items-center justify-start gap-1.5">
                                                        <span class="w-1.5 h-1.5 rounded-full bg-sky-400 shrink-0 shadow-sm shadow-sky-400/50"></span>
                                                        <p class="font-black text-sky-200 text-xs leading-tight truncate"><?php echo e($match->awayTeam->team_name ?? 'BYE'); ?></p>
                                                    </div>
                                                    <p class="text-[9px] text-white/50 mt-0.5 truncate font-bold uppercase"><?php echo e($match->awayTeam->school_name ?? ''); ?></p>
                                                </div>
                                            </div>
                                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                    </div>
                                </div>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                        
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($upcoming->isNotEmpty()): ?>
                            <div class="flex items-center gap-2 px-1 mt-1">
                                <span class="w-1.5 h-1.5 rounded-full bg-white/40"></span>
                                <p class="text-[10px] font-black text-white/40 tracking-[0.2em] uppercase">Seterusnya</p>
                                <div class="flex-1 h-px bg-white/10"></div>
                            </div>

                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $upcoming; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $match): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                <div class="rounded-2xl overflow-hidden border border-white/10 bg-white/[0.03] backdrop-blur-sm">
                                    <div class="flex items-center justify-between px-3 py-1.5 bg-white/5 border-b border-white/5">
                                        <span class="text-[10px] font-black text-yellow-400 uppercase tracking-wider">
                                            <?php echo e(is_numeric($match->field_number) ? 'PADANG '.$match->field_number : $match->field_number); ?>

                                        </span>
                                        <span class="text-[9px] font-bold text-white/50 uppercase tracking-widest">
                                            <?php echo e(optional(optional($match->group)->category)->name ?? optional($match->category)->name ?? ''); ?>

                                        </span>
                                    </div>
                                    <div class="p-3">
                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($match->group && $match->group->game_type === 'obstacle'): ?>
                                            <div class="flex items-center justify-between">
                                                <p class="font-extrabold text-white text-xs truncate"><?php echo e($match->homeTeam->team_name ?? 'BYE'); ?></p>
                                                <span class="text-[9px] font-bold text-white/40 uppercase tracking-wider">Menunggu</span>
                                            </div>
                                        <?php else: ?>
                                            <div class="grid grid-cols-[1fr_auto_1fr] gap-2 items-center">
                                                <div class="flex items-center justify-end gap-1.5 overflow-hidden">
                                                    <p class="font-extrabold text-rose-200/90 text-xs truncate text-right"><?php echo e($match->homeTeam->team_name ?? 'BYE'); ?></p>
                                                    <span class="w-1.5 h-1.5 rounded-full bg-rose-500/80 shrink-0"></span>
                                                </div>
                                                <span class="text-[9px] font-black text-white/40 bg-black/40 px-1.5 py-0.5 rounded border border-white/10">VS</span>
                                                <div class="flex items-center justify-start gap-1.5 overflow-hidden">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-sky-400/80 shrink-0"></span>
                                                    <p class="font-extrabold text-sky-200/90 text-xs truncate"><?php echo e($match->awayTeam->team_name ?? 'BYE'); ?></p>
                                                </div>
                                            </div>
                                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                    </div>
                                </div>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>
    </div>

    
    
    
    <?php $slides = $this->slides(); $slideCount = count($slides); ?>
    <div class="h-full min-h-0 relative rounded-3xl overflow-hidden bg-black/40 border border-white/10 shadow-2xl backdrop-blur-md"
         x-data="{
            activeSlide: 0,
            totalSlides: <?php echo e($slideCount); ?>,
            init() {
                if (this.totalSlides > 1) {
                    setInterval(() => {
                        this.activeSlide = (this.activeSlide + 1) % this.totalSlides;
                    }, 12000);
                }
            }
         }">

        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($slideCount === 0): ?>
            <div class="h-full flex flex-col items-center justify-center gap-3">
                <div class="w-14 h-14 rounded-full bg-white/5 flex items-center justify-center border border-white/10">
                    <svg class="w-7 h-7 text-white/30" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                </div>
                <p class="text-white/40 text-xs font-black tracking-widest uppercase">Tiada Data Kejohanan</p>
            </div>
        <?php else: ?>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $slides; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $idx => $slide): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                <?php
                    if ($slide['type'] === 'obstacle') {
                        $gradTop = 'from-emerald-900/60 via-emerald-950/30'; $borderHdr = 'border-emerald-500/30'; $accentBar = 'bg-emerald-400'; $subtitleColor = 'text-emerald-300';
                    } elseif ($slide['type'] === 'soccer') {
                        if ($slide['game'] === 'isobot') {
                            $gradTop = 'from-blue-900/60 via-blue-950/30'; $borderHdr = 'border-blue-500/30'; $accentBar = 'bg-blue-400'; $subtitleColor = 'text-blue-300';
                        } else {
                            $gradTop = 'from-purple-900/60 via-purple-950/30'; $borderHdr = 'border-purple-500/30'; $accentBar = 'bg-purple-400'; $subtitleColor = 'text-purple-300';
                        }
                    } else { // knockout and knockout_split
                        if ($slide['game'] === 'isobot') {
                            $gradTop = $slide['stage'] === 'trophy_knockout' ? 'from-blue-900/60 via-amber-950/30' : 'from-blue-900/60 via-slate-900/40';
                        } else {
                            $gradTop = $slide['stage'] === 'trophy_knockout' ? 'from-purple-900/60 via-amber-950/30' : 'from-purple-900/60 via-slate-900/40';
                        }
                        $borderHdr = $slide['stage'] === 'trophy_knockout' ? 'border-amber-500/30' : 'border-slate-400/30';
                        $accentBar = $slide['stage'] === 'trophy_knockout' ? 'bg-amber-400' : 'bg-slate-300';
                        $subtitleColor = $slide['stage'] === 'trophy_knockout' ? 'text-amber-300' : 'text-slate-300';
                    }
                ?>
                <div x-show="activeSlide === <?php echo e($idx); ?>"
                     x-transition:enter="transition ease-out duration-700"
                     x-transition:enter-start="opacity-0 scale-[1.008]"
                     x-transition:enter-end="opacity-100 scale-100"
                     x-transition:leave="transition ease-in duration-500"
                     x-transition:leave-start="opacity-100 scale-100"
                     x-transition:leave-end="opacity-0 scale-[0.992]"
                     class="absolute inset-0 flex flex-col"
                     style="display:none;">

                    
                    <div class="shrink-0 px-8 py-5 bg-gradient-to-r <?php echo e($gradTop); ?> to-transparent border-b <?php echo e($borderHdr); ?>">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-4">
                                <div class="<?php echo e($accentBar); ?> w-1.5 h-10 rounded-full shrink-0 shadow-lg"></div>
                                <div>
                                    <h2 class="text-3xl font-black text-white leading-none tracking-wider uppercase drop-shadow-md"><?php echo e($slide['title']); ?></h2>
                                    <p class="text-xs font-black <?php echo e($subtitleColor); ?> tracking-widest uppercase mt-2 flex items-center gap-2 flex-wrap">
                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($slide['type'] === 'obstacle'): ?>
                                            <span class="bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 px-2 py-0.5 rounded">TOP 10 TERPANTAS</span>
                                            <span class="text-white/30">•</span>
                                            <span class="text-white/90">KATEGORI <?php echo e($slide['category']); ?></span>
                                        <?php elseif($slide['type'] === 'soccer'): ?>
                                            <span class="bg-white/10 text-white/90 border border-white/20 px-2 py-0.5 rounded">KEDUDUKAN KUMPULAN</span>
                                        <?php elseif($slide['type'] === 'knockout_split'): ?>
                                            <span class="bg-white/15 text-white border border-white/25 px-2.5 py-0.5 rounded-md font-black tracking-wider shadow-sm">
                                                KATEGORI <?php echo e($slide['category']); ?>

                                            </span>
                                            <span class="text-white/30">•</span>
                                            <span class="bg-primary/30 text-yellow-300 border border-primary/40 px-2 py-0.5 rounded font-black">
                                                <?php echo e($slide['branchTitle']); ?>

                                            </span>
                                            <span class="text-white/30">•</span>
                                            <span class="text-yellow-300 font-black">
                                                <?php echo e($slide['stage'] === 'trophy_knockout' ? '🏆 PUSINGAN TROFI' : '🥈 PUSINGAN PIALA'); ?>

                                            </span>
                                        <?php else: ?>
                                            <span class="bg-white/15 text-white border border-white/25 px-2.5 py-0.5 rounded-md font-black tracking-wider shadow-sm">
                                                KATEGORI <?php echo e($slide['category']); ?>

                                            </span>
                                            <span class="text-white/30">•</span>
                                            <span class="text-yellow-300 font-black">
                                                <?php echo e($slide['stage'] === 'trophy_knockout' ? '🏆 PUSINGAN TROFI' : '🥈 PUSINGAN PIALA'); ?>

                                            </span>
                                            <span class="text-white/30">•</span>
                                            <span class="text-white/70">CARTA KALAH MATI</span>
                                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                    </p>
                                </div>
                            </div>

                            
                            <div class="flex flex-col items-end gap-2">
                                <div class="flex gap-1.5">
                                    <template x-for="i in totalSlides" :key="i">
                                        <div class="rounded-full h-1.5 transition-all duration-500"
                                             :class="activeSlide === (i-1) ? 'w-6 bg-white' : 'w-2 bg-white/20'"></div>
                                    </template>
                                </div>
                                <span class="text-[10px] text-white/40 font-black tracking-wider uppercase">
                                    <span x-text="activeSlide + 1" class="text-white"></span> / <?php echo e($slideCount); ?>

                                </span>
                            </div>
                        </div>
                    </div>

                    
                    <div class="flex-1 min-h-0 overflow-hidden p-6">

                        
                        
                        
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($slide['type'] === 'obstacle'): ?>
                            <div class="h-full overflow-hidden rounded-2xl border border-white/10 bg-black/40">
                                <table class="w-full">
                                    <thead>
                                        <tr class="border-b border-white/10 bg-white/5">
                                            <th class="py-3 px-6 text-left text-[11px] font-black text-white/50 tracking-widest uppercase w-16">KED</th>
                                            <th class="py-3 px-6 text-left text-[11px] font-black text-white/50 tracking-widest uppercase">PASUKAN</th>
                                            <th class="py-3 px-6 text-left text-[11px] font-black text-white/50 tracking-widest uppercase hidden xl:table-cell">SEKOLAH</th>
                                            <th class="py-3 px-6 text-right text-[11px] font-black text-white/50 tracking-widest uppercase">MASA RASMI</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $slide['teams']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $gt): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                            <tr class="border-b border-white/5 last:border-0 <?php echo e($i < 3 ? 'bg-emerald-500/10' : ''); ?>">
                                                <td class="py-3.5 px-6">
                                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($i === 0): ?>
                                                        <span class="text-lg">🥇</span>
                                                    <?php elseif($i === 1): ?>
                                                        <span class="text-lg">🥈</span>
                                                    <?php elseif($i === 2): ?>
                                                        <span class="text-lg">🥉</span>
                                                    <?php else: ?>
                                                        <span class="text-sm font-black text-white/40 pl-1"><?php echo e($i + 1); ?></span>
                                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                                </td>
                                                <td class="py-3.5 px-6">
                                                    <span class="font-extrabold text-base text-white <?php echo e($i < 3 ? 'text-emerald-300' : ''); ?>"><?php echo e($gt->team->team_name); ?></span>
                                                </td>
                                                <td class="py-3.5 px-6 text-white/60 text-sm font-medium hidden xl:table-cell"><?php echo e($gt->team->school_name); ?></td>
                                                <td class="py-3.5 px-6 text-right">
                                                    <?php
                                                        $tf = $gt->goals_for;
                                                        if ($tf > 0) {
                                                            $tM = floor($tf/60000); $tS = floor(($tf%60000)/1000); $tMs = $tf%1000;
                                                            $timeStr = sprintf('%02d:%02d.%03d', $tM, $tS, $tMs);
                                                        } else { $timeStr = null; }
                                                    ?>
                                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($timeStr): ?>
                                                        <span class="font-mono font-black text-xl text-emerald-400 tabular-nums"><?php echo e($timeStr); ?></span>
                                                    <?php else: ?>
                                                        <span class="text-xs font-black text-white/20 uppercase tracking-widest">BELUM BERLARI</span>
                                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                                    </tbody>
                                </table>
                            </div>

                        
                        
                        
                        <?php elseif($slide['type'] === 'soccer'): ?>
                            <div class="grid grid-cols-2 gap-6 h-full">
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $slide['groups']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $g): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                    <div class="rounded-2xl border border-white/10 bg-black/40 overflow-hidden flex flex-col min-h-0 shadow-lg">
                                        <div class="flex items-center justify-between px-5 py-3.5 bg-white/5 border-b border-white/10 shrink-0">
                                            <div class="flex items-center gap-2.5">
                                                <div class="w-7 h-7 rounded-lg bg-primary text-white flex items-center justify-center font-black text-sm">
                                                    <?php echo e($g->group_letter); ?>

                                                </div>
                                                <h3 class="font-black text-base text-white">Kumpulan <?php echo e($g->group_letter); ?></h3>
                                            </div>
                                            <span class="text-[10px] font-extrabold text-white/50 bg-white/10 px-2.5 py-1 rounded-md uppercase tracking-wider"><?php echo e($g->category->name); ?></span>
                                        </div>
                                        <div class="flex-1 min-h-0 overflow-hidden">
                                            <table class="w-full">
                                                <thead>
                                                    <tr class="border-b border-white/10 bg-white/[0.02]">
                                                        <th class="py-2.5 px-4 text-left text-[10px] font-black text-white/40 tracking-widest">PASUKAN</th>
                                                        <th class="py-2.5 px-2 text-center text-[10px] font-black text-white/40 tracking-widest">P</th>
                                                        <th class="py-2.5 px-2 text-center text-[10px] font-black text-white/40 tracking-widest">M</th>
                                                        <th class="py-2.5 px-2 text-center text-[10px] font-black text-white/40 tracking-widest">S</th>
                                                        <th class="py-2.5 px-2 text-center text-[10px] font-black text-white/40 tracking-widest">K</th>
                                                        <th class="py-2.5 px-2 text-center text-[10px] font-black text-white/40 tracking-widest">+/-</th>
                                                        <th class="py-2.5 px-4 text-center text-[10px] font-black text-primary tracking-widest">MATA</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $g->getStandings(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $gt): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                                        <tr class="border-b border-white/5 last:border-0 <?php echo e($i < 2 ? 'bg-primary/10' : ''); ?>">
                                                            <td class="py-3 px-4">
                                                                <div class="flex items-center gap-2">
                                                                    <div class="w-1 h-4 rounded-full <?php echo e($i < 2 ? 'bg-primary' : 'bg-white/10'); ?> shrink-0"></div>
                                                                    <span class="font-extrabold text-sm <?php echo e($i < 2 ? 'text-white' : 'text-white/60'); ?> truncate"><?php echo e($gt->team->team_name); ?></span>
                                                                </div>
                                                            </td>
                                                            <td class="py-3 px-2 text-center text-xs text-white/60 font-bold"><?php echo e($gt->played); ?></td>
                                                            <td class="py-3 px-2 text-center text-xs text-emerald-400 font-bold"><?php echo e($gt->won); ?></td>
                                                            <td class="py-3 px-2 text-center text-xs text-white/40 font-bold"><?php echo e($gt->drawn); ?></td>
                                                            <td class="py-3 px-2 text-center text-xs text-red-400 font-bold"><?php echo e($gt->lost); ?></td>
                                                            <td class="py-3 px-2 text-center text-xs font-black <?php echo e($gt->goal_difference > 0 ? 'text-emerald-400' : ($gt->goal_difference < 0 ? 'text-red-400' : 'text-white/40')); ?>">
                                                                <?php echo e($gt->goal_difference > 0 ? '+'.$gt->goal_difference : $gt->goal_difference); ?>

                                                            </td>
                                                            <td class="py-3 px-4 text-center font-black text-base <?php echo e($i < 2 ? 'text-primary' : 'text-white/50'); ?>"><?php echo e($gt->points); ?></td>
                                                        </tr>
                                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                            </div>

                        
                        
                        
                        <?php elseif($slide['type'] === 'knockout_split'): ?>
                            <?php 
                                $isTrophy = $slide['stage'] === 'trophy_knockout';
                                $feederRounds = $slide['feederRounds'];
                                $isLeft = $slide['branch'] === 'left';
                            ?>

                            <div class="h-full flex items-stretch gap-4 min-h-0 overflow-x-auto pb-1" style="scrollbar-width:none;">
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $feederRounds; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $roundName => $matches): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                    <?php 
                                        $half = ceil($matches->count() / 2);
                                        $branchMatches = $isLeft ? $matches->take($half) : $matches->slice($half);
                                        $isSemi = in_array($roundName, ['Separuh Akhir', 'Semi Final']);
                                    ?>
                                    <div class="flex-1 min-w-[220px] flex flex-col min-h-0 bg-white/[0.02] border border-white/10 rounded-2xl p-3 backdrop-blur-sm <?php echo e($isSemi ? 'border-amber-500/30 bg-amber-500/5' : ''); ?>">
                                        
                                        
                                        <div class="flex items-center justify-between pb-2.5 mb-2 border-b border-white/10 shrink-0">
                                            <div class="flex items-center gap-2">
                                                <div class="w-1.5 h-3.5 rounded-full <?php echo e($isSemi ? 'bg-amber-400' : ($isTrophy ? 'bg-primary' : 'bg-slate-400')); ?>"></div>
                                                <h3 class="text-xs font-black tracking-wider uppercase <?php echo e($isSemi ? 'text-yellow-300 font-black' : 'text-white'); ?>">
                                                    <?php echo e($roundName); ?>

                                                </h3>
                                            </div>
                                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($isSemi): ?>
                                                <span class="text-[9px] font-black px-2 py-0.5 rounded bg-yellow-400/20 text-yellow-300 border border-yellow-400/30 uppercase">
                                                    KE FINAL 🏆
                                                </span>
                                            <?php else: ?>
                                                <span class="text-[9px] font-black px-1.5 py-0.5 rounded bg-white/10 text-white/60">
                                                    <?php echo e(count($branchMatches)); ?> Match
                                                </span>
                                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                        </div>

                                        
                                        <div class="flex-1 flex flex-col justify-around gap-2.5 min-h-0">
                                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $branchMatches; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $match): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                                <?php
                                                    $done = $match->status === 'completed' || $match->winner_team_id;
                                                    $homeW = $match->winner_team_id && $match->winner_team_id == $match->home_team_id;
                                                    $awayW = $match->winner_team_id && $match->winner_team_id == $match->away_team_id;
                                                ?>
                                                <div class="rounded-xl overflow-hidden border <?php echo e($isSemi ? 'border-amber-500/40 bg-black/70 shadow-lg shadow-amber-500/10' : ($done ? 'border-white/20 bg-black/60' : 'border-white/10 bg-black/40')); ?>">
                                                    
                                                    <div class="flex items-center justify-between px-3 py-1 bg-white/5 border-b border-white/5">
                                                        <span class="text-[9px] font-black <?php echo e($match->field_number ? 'text-yellow-300' : 'text-white/40'); ?> uppercase tracking-wider">
                                                            <?php echo e($match->field_number ? (is_numeric($match->field_number) ? 'PADANG '.$match->field_number : $match->field_number) : 'PADANG TBD'); ?>

                                                        </span>
                                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($done): ?>
                                                            <span class="text-[8px] font-black text-emerald-400 bg-emerald-500/20 px-1.5 py-0.2 rounded">SELESAI</span>
                                                        <?php elseif($match->status === 'in_progress'): ?>
                                                            <span class="text-[8px] font-black text-red-400 bg-red-500/20 px-1.5 py-0.2 rounded animate-pulse">LIVE</span>
                                                        <?php else: ?>
                                                            <span class="text-[8px] font-extrabold text-white/30">M#<?php echo e($match->bracket_position ?? $match->id); ?></span>
                                                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                                    </div>

                                                    
                                                    <div class="divide-y divide-white/5">
                                                        
                                                        <div class="flex items-center justify-between px-3 py-2 <?php echo e($homeW ? 'bg-emerald-500/20' : 'bg-rose-500/[0.03]'); ?>">
                                                            <div class="flex items-center gap-1.5 flex-1 min-w-0 pr-1.5">
                                                                <span class="w-1 h-3 rounded-full <?php echo e($homeW ? 'bg-emerald-400' : 'bg-rose-500'); ?> shrink-0"></span>
                                                                <span class="font-extrabold text-xs truncate <?php echo e($homeW ? 'text-emerald-300 font-black' : ($match->homeTeam ? 'text-white' : 'text-white/30 italic')); ?>">
                                                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($homeW): ?> <span class="text-emerald-400 font-black mr-0.5">✓</span> <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                                                    <?php echo e($match->homeTeam ? $match->homeTeam->team_name : 'Menunggu'); ?>

                                                                </span>
                                                            </div>
                                                            <span class="font-black text-xs tabular-nums px-2 py-0.5 rounded bg-black/60 border border-white/10 <?php echo e($homeW ? 'text-emerald-400 border-emerald-500/40' : ($match->home_score !== null ? 'text-rose-300' : 'text-white/20')); ?>">
                                                                <?php echo e($match->home_score !== null ? $match->home_score : '-'); ?>

                                                            </span>
                                                        </div>

                                                        
                                                        <div class="flex items-center justify-between px-3 py-2 <?php echo e($awayW ? 'bg-emerald-500/20' : 'bg-sky-500/[0.03]'); ?>">
                                                            <div class="flex items-center gap-1.5 flex-1 min-w-0 pr-1.5">
                                                                <span class="w-1 h-3 rounded-full <?php echo e($awayW ? 'bg-emerald-400' : 'bg-sky-400'); ?> shrink-0"></span>
                                                                <span class="font-extrabold text-xs truncate <?php echo e($awayW ? 'text-emerald-300 font-black' : ($match->awayTeam ? 'text-white' : 'text-white/30 italic')); ?>">
                                                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($awayW): ?> <span class="text-emerald-400 font-black mr-0.5">✓</span> <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                                                    <?php echo e($match->awayTeam ? $match->awayTeam->team_name : 'Menunggu'); ?>

                                                                </span>
                                                            </div>
                                                            <span class="font-black text-xs tabular-nums px-2 py-0.5 rounded bg-black/60 border border-white/10 <?php echo e($awayW ? 'text-emerald-400 border-emerald-500/40' : ($match->away_score !== null ? 'text-sky-300' : 'text-white/20')); ?>">
                                                                <?php echo e($match->away_score !== null ? $match->away_score : '-'); ?>

                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                                        </div>
                                    </div>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                            </div>

                        
                        
                        
                        <?php elseif($slide['type'] === 'knockout'): ?>
                            <?php 
                                $isTrophy = $slide['stage'] === 'trophy_knockout';
                                $feederRounds = $slide['feederRounds'];
                                $finalMatch = $slide['finalMatch'];
                                $thirdMatch = $slide['thirdMatch'];
                                $rankings = $slide['rankings'];
                            ?>

                            <div class="h-full flex items-stretch gap-3 min-h-0 overflow-x-auto pb-1" style="scrollbar-width:none;">
                                
                                
                                
                                
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $feederRounds; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $roundName => $matches): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                    <?php 
                                        $half = ceil($matches->count() / 2);
                                        $leftMatches = $matches->take($half);
                                    ?>
                                    <div class="flex-1 min-w-[170px] flex flex-col min-h-0 bg-white/[0.02] border border-white/10 rounded-2xl p-2.5 backdrop-blur-sm">
                                        
                                        <div class="flex items-center justify-between pb-2 mb-1.5 border-b border-white/10 shrink-0">
                                            <div class="flex items-center gap-1.5">
                                                <div class="w-1.5 h-3.5 rounded-full <?php echo e($isTrophy ? 'bg-amber-400' : 'bg-slate-300'); ?>"></div>
                                                <h3 class="text-[11px] font-black tracking-wider uppercase <?php echo e($isTrophy ? 'text-amber-200' : 'text-slate-200'); ?>">
                                                    <?php echo e($roundName); ?>

                                                </h3>
                                            </div>
                                            <span class="text-[9px] font-black px-1.5 py-0.5 rounded bg-white/10 text-white/70">KIRI</span>
                                        </div>

                                        
                                        <div class="flex-1 flex flex-col justify-around gap-2 min-h-0">
                                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $leftMatches; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $match): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                                <?php
                                                    $done = $match->status === 'completed' || $match->winner_team_id;
                                                    $homeW = $match->winner_team_id && $match->winner_team_id == $match->home_team_id;
                                                    $awayW = $match->winner_team_id && $match->winner_team_id == $match->away_team_id;
                                                ?>
                                                <div class="rounded-xl overflow-hidden border <?php echo e($done ? 'border-white/20 bg-black/60' : 'border-white/10 bg-black/40'); ?> shadow-md">
                                                    
                                                    <div class="flex items-center justify-between px-2.5 py-1 bg-white/5 border-b border-white/5">
                                                        <span class="text-[9px] font-black <?php echo e($match->field_number ? 'text-yellow-300' : 'text-white/40'); ?> uppercase tracking-wider">
                                                            <?php echo e($match->field_number ? (is_numeric($match->field_number) ? 'P.'.$match->field_number : $match->field_number) : 'PADANG TBD'); ?>

                                                        </span>
                                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($done): ?>
                                                            <span class="text-[8px] font-black text-emerald-400 bg-emerald-500/20 px-1 py-0.2 rounded">SELESAI</span>
                                                        <?php elseif($match->status === 'in_progress'): ?>
                                                            <span class="text-[8px] font-black text-red-400 bg-red-500/20 px-1 py-0.2 rounded animate-pulse">LIVE</span>
                                                        <?php else: ?>
                                                            <span class="text-[8px] font-extrabold text-white/30">M#<?php echo e($match->bracket_position ?? $match->id); ?></span>
                                                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                                    </div>

                                                    
                                                    <div class="divide-y divide-white/5">
                                                        <div class="flex items-center justify-between px-2.5 py-1.5 <?php echo e($homeW ? 'bg-emerald-500/20' : 'bg-rose-500/[0.03]'); ?>">
                                                            <div class="flex items-center gap-1.5 flex-1 min-w-0 pr-1">
                                                                <span class="w-1 h-3 rounded-full <?php echo e($homeW ? 'bg-emerald-400' : 'bg-rose-500'); ?> shrink-0"></span>
                                                                <span class="font-extrabold text-[11px] truncate <?php echo e($homeW ? 'text-emerald-300 font-black' : ($match->homeTeam ? 'text-white' : 'text-white/30 italic')); ?>">
                                                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($homeW): ?> <span class="text-emerald-400 font-black mr-0.5">✓</span> <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                                                    <?php echo e($match->homeTeam ? $match->homeTeam->team_name : 'Menunggu'); ?>

                                                                </span>
                                                            </div>
                                                            <span class="font-black text-xs tabular-nums px-1.5 py-0.5 rounded bg-black/60 border border-white/10 <?php echo e($homeW ? 'text-emerald-400 border-emerald-500/40' : ($match->home_score !== null ? 'text-rose-300' : 'text-white/20')); ?>">
                                                                <?php echo e($match->home_score !== null ? $match->home_score : '-'); ?>

                                                            </span>
                                                        </div>
                                                        <div class="flex items-center justify-between px-2.5 py-1.5 <?php echo e($awayW ? 'bg-emerald-500/20' : 'bg-sky-500/[0.03]'); ?>">
                                                            <div class="flex items-center gap-1.5 flex-1 min-w-0 pr-1">
                                                                <span class="w-1 h-3 rounded-full <?php echo e($awayW ? 'bg-emerald-400' : 'bg-sky-400'); ?> shrink-0"></span>
                                                                <span class="font-extrabold text-[11px] truncate <?php echo e($awayW ? 'text-emerald-300 font-black' : ($match->awayTeam ? 'text-white' : 'text-white/30 italic')); ?>">
                                                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($awayW): ?> <span class="text-emerald-400 font-black mr-0.5">✓</span> <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                                                    <?php echo e($match->awayTeam ? $match->awayTeam->team_name : 'Menunggu'); ?>

                                                                </span>
                                                            </div>
                                                            <span class="font-black text-xs tabular-nums px-1.5 py-0.5 rounded bg-black/60 border border-white/10 <?php echo e($awayW ? 'text-emerald-400 border-emerald-500/40' : ($match->away_score !== null ? 'text-sky-300' : 'text-white/20')); ?>">
                                                                <?php echo e($match->away_score !== null ? $match->away_score : '-'); ?>

                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                                        </div>
                                    </div>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>

                                
                                
                                
                                <div class="flex-1 min-w-[210px] flex flex-col justify-center gap-3.5 min-h-0 bg-gradient-to-b from-amber-500/10 via-black/40 to-black/60 border-2 border-amber-500/40 rounded-3xl p-3 shadow-2xl shadow-amber-500/10">
                                    
                                    
                                    <div class="flex flex-col">
                                        <div class="flex items-center justify-center gap-2 pb-2 mb-1.5 border-b border-amber-500/30">
                                            <span class="text-xl animate-bounce">🏆</span>
                                            <h3 class="text-xs font-black text-yellow-300 tracking-widest uppercase drop-shadow">
                                                Pentas Akhir
                                            </h3>
                                        </div>

                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($finalMatch): ?>
                                            <?php
                                                $fDone = $finalMatch->status === 'completed' || $finalMatch->winner_team_id;
                                                $fHomeW = $finalMatch->winner_team_id && $finalMatch->winner_team_id == $finalMatch->home_team_id;
                                                $fAwayW = $finalMatch->winner_team_id && $finalMatch->winner_team_id == $finalMatch->away_team_id;
                                            ?>
                                            <div class="rounded-2xl overflow-hidden border-2 border-amber-500/50 bg-black/80 shadow-xl shadow-amber-500/15">
                                                <div class="flex items-center justify-between px-3 py-1 bg-amber-500/20 border-b border-amber-500/20">
                                                    <span class="text-[9px] font-black text-yellow-300 uppercase tracking-wider">
                                                        <?php echo e($finalMatch->field_number ? (is_numeric($finalMatch->field_number) ? 'PADANG '.$finalMatch->field_number : $finalMatch->field_number) : 'PADANG AKHIR'); ?>

                                                    </span>
                                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($fDone): ?>
                                                        <span class="text-[8px] font-black text-emerald-400 bg-emerald-500/20 px-1.5 py-0.5 rounded">JUARA DITENTUKAN</span>
                                                    <?php elseif($finalMatch->status === 'in_progress'): ?>
                                                        <span class="text-[8px] font-black text-red-400 bg-red-500/20 px-1.5 py-0.5 rounded animate-pulse">LIVE FINAL</span>
                                                    <?php else: ?>
                                                        <span class="text-[8px] font-black text-yellow-400/80">PENENTUAN JUARA</span>
                                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                                </div>
                                                <div class="divide-y divide-white/10">
                                                    <div class="flex items-center justify-between px-2.5 py-2 <?php echo e($fHomeW ? 'bg-emerald-500/20' : 'bg-rose-500/[0.04]'); ?>">
                                                        <div class="flex items-center gap-1.5 flex-1 min-w-0 pr-1">
                                                            <span class="w-1.5 h-3.5 rounded-full <?php echo e($fHomeW ? 'bg-emerald-400' : 'bg-rose-500'); ?> shrink-0"></span>
                                                            <span class="font-black text-[11px] truncate <?php echo e($fHomeW ? 'text-emerald-300 text-xs' : ($finalMatch->homeTeam ? 'text-white' : 'text-white/30 italic')); ?>">
                                                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($fHomeW): ?> 👑 <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                                                <?php echo e($finalMatch->homeTeam ? $finalMatch->homeTeam->team_name : 'Pemenang SF 1'); ?>

                                                            </span>
                                                        </div>
                                                        <span class="font-black text-sm tabular-nums px-2 py-0.5 rounded-lg bg-black border border-white/20 <?php echo e($fHomeW ? 'text-emerald-400 border-emerald-400 font-black' : ($finalMatch->home_score !== null ? 'text-rose-300' : 'text-white/30')); ?>">
                                                            <?php echo e($finalMatch->home_score !== null ? $finalMatch->home_score : '-'); ?>

                                                        </span>
                                                    </div>
                                                    <div class="flex items-center justify-between px-2.5 py-2 <?php echo e($fAwayW ? 'bg-emerald-500/20' : 'bg-sky-500/[0.04]'); ?>">
                                                        <div class="flex items-center gap-1.5 flex-1 min-w-0 pr-1">
                                                            <span class="w-1.5 h-3.5 rounded-full <?php echo e($fAwayW ? 'bg-emerald-400' : 'bg-sky-400'); ?> shrink-0"></span>
                                                            <span class="font-black text-[11px] truncate <?php echo e($fAwayW ? 'text-emerald-300 text-xs' : ($finalMatch->awayTeam ? 'text-white' : 'text-white/30 italic')); ?>">
                                                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($fAwayW): ?> 👑 <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                                                <?php echo e($finalMatch->awayTeam ? $finalMatch->awayTeam->team_name : 'Pemenang SF 2'); ?>

                                                            </span>
                                                        </div>
                                                        <span class="font-black text-sm tabular-nums px-2 py-0.5 rounded-lg bg-black border border-white/20 <?php echo e($fAwayW ? 'text-emerald-400 border-emerald-400 font-black' : ($finalMatch->away_score !== null ? 'text-sky-300' : 'text-white/30')); ?>">
                                                            <?php echo e($finalMatch->away_score !== null ? $finalMatch->away_score : '-'); ?>

                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                    </div>

                                    
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($thirdMatch): ?>
                                        <?php
                                            $tDone = $thirdMatch->status === 'completed' || $thirdMatch->winner_team_id;
                                            $tHomeW = $thirdMatch->winner_team_id && $thirdMatch->winner_team_id == $thirdMatch->home_team_id;
                                            $tAwayW = $thirdMatch->winner_team_id && $thirdMatch->winner_team_id == $thirdMatch->away_team_id;
                                        ?>
                                        <div class="flex flex-col pt-1.5 border-t border-white/10">
                                            <div class="flex items-center justify-center gap-1 pb-1 mb-1">
                                                <span class="text-xs">🥉</span>
                                                <h4 class="text-[9px] font-black text-slate-300 tracking-wider uppercase">
                                                    Tempat Ke-3 & 4
                                                </h4>
                                            </div>
                                            <div class="rounded-xl overflow-hidden border border-white/10 bg-black/60 shadow">
                                                <div class="divide-y divide-white/5">
                                                    <div class="flex items-center justify-between px-2 py-1 <?php echo e($tHomeW ? 'bg-emerald-500/20' : 'bg-rose-500/[0.03]'); ?>">
                                                        <div class="flex items-center gap-1 flex-1 min-w-0 pr-1">
                                                            <span class="w-1 h-2.5 rounded-full <?php echo e($tHomeW ? 'bg-emerald-400' : 'bg-rose-500'); ?> shrink-0"></span>
                                                            <span class="font-bold text-[10px] truncate <?php echo e($tHomeW ? 'text-emerald-300 font-black' : ($thirdMatch->homeTeam ? 'text-white' : 'text-white/30 italic')); ?>">
                                                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($tHomeW): ?> ✓ <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                                                <?php echo e($thirdMatch->homeTeam ? $thirdMatch->homeTeam->team_name : 'Kalah SF 1'); ?>

                                                            </span>
                                                        </div>
                                                        <span class="font-black text-[10px] tabular-nums px-1.5 py-0.5 rounded bg-black/60 border border-white/10 <?php echo e($tHomeW ? 'text-emerald-400' : ($thirdMatch->home_score !== null ? 'text-rose-300' : 'text-white/40')); ?>">
                                                            <?php echo e($thirdMatch->home_score !== null ? $thirdMatch->home_score : '-'); ?>

                                                        </span>
                                                    </div>
                                                    <div class="flex items-center justify-between px-2 py-1 <?php echo e($tAwayW ? 'bg-emerald-500/20' : 'bg-sky-500/[0.03]'); ?>">
                                                        <div class="flex items-center gap-1 flex-1 min-w-0 pr-1">
                                                            <span class="w-1 h-2.5 rounded-full <?php echo e($tAwayW ? 'bg-emerald-400' : 'bg-sky-400'); ?> shrink-0"></span>
                                                            <span class="font-bold text-[10px] truncate <?php echo e($tAwayW ? 'text-emerald-300 font-black' : ($thirdMatch->awayTeam ? 'text-white' : 'text-white/30 italic')); ?>">
                                                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($tAwayW): ?> ✓ <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                                                <?php echo e($thirdMatch->awayTeam ? $thirdMatch->awayTeam->team_name : 'Kalah SF 2'); ?>

                                                            </span>
                                                        </div>
                                                        <span class="font-black text-[10px] tabular-nums px-1.5 py-0.5 rounded bg-black/60 border border-white/10 <?php echo e($tAwayW ? 'text-emerald-400' : ($thirdMatch->away_score !== null ? 'text-sky-300' : 'text-white/40')); ?>">
                                                            <?php echo e($thirdMatch->away_score !== null ? $thirdMatch->away_score : '-'); ?>

                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                                </div>

                                
                                
                                
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $feederRounds->reverse(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $roundName => $matches): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                    <?php 
                                        $half = ceil($matches->count() / 2);
                                        $rightMatches = $matches->slice($half);
                                    ?>
                                    <div class="flex-1 min-w-[170px] flex flex-col min-h-0 bg-white/[0.02] border border-white/10 rounded-2xl p-2.5 backdrop-blur-sm">
                                        
                                        <div class="flex items-center justify-between pb-2 mb-1.5 border-b border-white/10 shrink-0">
                                            <span class="text-[9px] font-black px-1.5 py-0.5 rounded bg-white/10 text-white/70">KANAN</span>
                                            <div class="flex items-center gap-1.5">
                                                <h3 class="text-[11px] font-black tracking-wider uppercase <?php echo e($isTrophy ? 'text-amber-200' : 'text-slate-200'); ?>">
                                                    <?php echo e($roundName); ?>

                                                </h3>
                                                <div class="w-1.5 h-3.5 rounded-full <?php echo e($isTrophy ? 'bg-amber-400' : 'bg-slate-300'); ?>"></div>
                                            </div>
                                        </div>

                                        
                                        <div class="flex-1 flex flex-col justify-around gap-2 min-h-0">
                                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $rightMatches; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $match): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                                <?php
                                                    $done = $match->status === 'completed' || $match->winner_team_id;
                                                    $homeW = $match->winner_team_id && $match->winner_team_id == $match->home_team_id;
                                                    $awayW = $match->winner_team_id && $match->winner_team_id == $match->away_team_id;
                                                ?>
                                                <div class="rounded-xl overflow-hidden border <?php echo e($done ? 'border-white/20 bg-black/60' : 'border-white/10 bg-black/40'); ?> shadow-md">
                                                    
                                                    <div class="flex items-center justify-between px-2.5 py-1 bg-white/5 border-b border-white/5">
                                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($done): ?>
                                                            <span class="text-[8px] font-black text-emerald-400 bg-emerald-500/20 px-1 py-0.2 rounded">SELESAI</span>
                                                        <?php elseif($match->status === 'in_progress'): ?>
                                                            <span class="text-[8px] font-black text-red-400 bg-red-500/20 px-1 py-0.2 rounded animate-pulse">LIVE</span>
                                                        <?php else: ?>
                                                            <span class="text-[8px] font-extrabold text-white/30">M#<?php echo e($match->bracket_position ?? $match->id); ?></span>
                                                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                                        <span class="text-[9px] font-black <?php echo e($match->field_number ? 'text-yellow-300' : 'text-white/40'); ?> uppercase tracking-wider">
                                                            <?php echo e($match->field_number ? (is_numeric($match->field_number) ? 'P.'.$match->field_number : $match->field_number) : 'PADANG TBD'); ?>

                                                        </span>
                                                    </div>

                                                    
                                                    <div class="divide-y divide-white/5">
                                                        <div class="flex items-center justify-between px-2.5 py-1.5 <?php echo e($homeW ? 'bg-emerald-500/20' : 'bg-rose-500/[0.03]'); ?>">
                                                            <div class="flex items-center gap-1.5 flex-1 min-w-0 pr-1">
                                                                <span class="w-1 h-3 rounded-full <?php echo e($homeW ? 'bg-emerald-400' : 'bg-rose-500'); ?> shrink-0"></span>
                                                                <span class="font-extrabold text-[11px] truncate <?php echo e($homeW ? 'text-emerald-300 font-black' : ($match->homeTeam ? 'text-white' : 'text-white/30 italic')); ?>">
                                                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($homeW): ?> <span class="text-emerald-400 font-black mr-0.5">✓</span> <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                                                    <?php echo e($match->homeTeam ? $match->homeTeam->team_name : 'Menunggu'); ?>

                                                                </span>
                                                            </div>
                                                            <span class="font-black text-xs tabular-nums px-1.5 py-0.5 rounded bg-black/60 border border-white/10 <?php echo e($homeW ? 'text-emerald-400 border-emerald-500/40' : ($match->home_score !== null ? 'text-rose-300' : 'text-white/20')); ?>">
                                                                <?php echo e($match->home_score !== null ? $match->home_score : '-'); ?>

                                                            </span>
                                                        </div>
                                                        <div class="flex items-center justify-between px-2.5 py-1.5 <?php echo e($awayW ? 'bg-emerald-500/20' : 'bg-sky-500/[0.03]'); ?>">
                                                            <div class="flex items-center gap-1.5 flex-1 min-w-0 pr-1">
                                                                <span class="w-1 h-3 rounded-full <?php echo e($awayW ? 'bg-emerald-400' : 'bg-sky-400'); ?> shrink-0"></span>
                                                                <span class="font-extrabold text-[11px] truncate <?php echo e($awayW ? 'text-emerald-300 font-black' : ($match->awayTeam ? 'text-white' : 'text-white/30 italic')); ?>">
                                                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($awayW): ?> <span class="text-emerald-400 font-black mr-0.5">✓</span> <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                                                    <?php echo e($match->awayTeam ? $match->awayTeam->team_name : 'Menunggu'); ?>

                                                                </span>
                                                            </div>
                                                            <span class="font-black text-xs tabular-nums px-1.5 py-0.5 rounded bg-black/60 border border-white/10 <?php echo e($awayW ? 'text-emerald-400 border-emerald-500/40' : ($match->away_score !== null ? 'text-sky-300' : 'text-white/20')); ?>">
                                                                <?php echo e($match->away_score !== null ? $match->away_score : '-'); ?>

                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                                        </div>
                                    </div>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>

                                
                                
                                
                                <div class="flex-1 min-w-[210px] flex flex-col min-h-0 bg-gradient-to-b from-amber-500/10 via-black/50 to-black/70 border-2 border-amber-500/40 rounded-3xl p-3 shadow-2xl backdrop-blur-md">
                                    
                                    
                                    <div class="flex items-center justify-between pb-2 mb-2 border-b border-amber-500/30 shrink-0">
                                        <div class="flex items-center gap-1.5">
                                            <span class="text-base">🏆</span>
                                            <h3 class="text-xs font-black tracking-wider uppercase text-yellow-300 drop-shadow">
                                                Kedudukan Rasmi
                                            </h3>
                                        </div>
                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($rankings['isFinished']): ?>
                                            <span class="text-[8px] font-black px-2 py-0.5 rounded bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 uppercase animate-pulse">
                                                TAMAT
                                            </span>
                                        <?php else: ?>
                                            <span class="text-[8px] font-black px-1.5 py-0.5 rounded bg-white/10 text-white/50 uppercase">
                                                TOP 5
                                            </span>
                                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                    </div>

                                    
                                    <div class="flex-1 flex flex-col justify-between gap-1.5 min-h-0">
                                        
                                        
                                        <div class="rounded-xl overflow-hidden border border-yellow-500/50 bg-gradient-to-r from-yellow-500/20 via-black/80 to-black/90 p-2 shadow-md">
                                            <div class="flex items-center gap-2 overflow-hidden">
                                                <span class="text-lg shrink-0">🥇</span>
                                                <div class="overflow-hidden">
                                                    <span class="text-[8px] font-black text-yellow-400 uppercase tracking-widest block leading-tight">JUARA</span>
                                                    <p class="font-black text-xs text-white truncate leading-tight mt-0.5">
                                                        <?php echo e($rankings['first']->team_name ?? 'Menunggu Final'); ?>

                                                    </p>
                                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($rankings['first']): ?>
                                                        <p class="text-[8px] text-white/40 truncate font-bold uppercase"><?php echo e($rankings['first']->school_name); ?></p>
                                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                                </div>
                                            </div>
                                        </div>

                                        
                                        <div class="rounded-xl overflow-hidden border border-slate-300/40 bg-gradient-to-r from-slate-400/15 via-black/80 to-black/90 p-2 shadow-sm">
                                            <div class="flex items-center gap-2 overflow-hidden">
                                                <span class="text-lg shrink-0">🥈</span>
                                                <div class="overflow-hidden">
                                                    <span class="text-[8px] font-black text-slate-300 uppercase tracking-widest block leading-tight">NAIB JUARA</span>
                                                    <p class="font-black text-xs text-white truncate leading-tight mt-0.5">
                                                        <?php echo e($rankings['second']->team_name ?? 'Menunggu Final'); ?>

                                                    </p>
                                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($rankings['second']): ?>
                                                        <p class="text-[8px] text-white/40 truncate font-bold uppercase"><?php echo e($rankings['second']->school_name); ?></p>
                                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                                </div>
                                            </div>
                                        </div>

                                        
                                        <div class="rounded-xl overflow-hidden border border-amber-600/40 bg-gradient-to-r from-amber-700/15 via-black/80 to-black/90 p-2 shadow-sm">
                                            <div class="flex items-center gap-2 overflow-hidden">
                                                <span class="text-lg shrink-0">🥉</span>
                                                <div class="overflow-hidden">
                                                    <span class="text-[8px] font-black text-amber-400 uppercase tracking-widest block leading-tight">TEMPAT KE-3</span>
                                                    <p class="font-black text-xs text-white truncate leading-tight mt-0.5">
                                                        <?php echo e($rankings['third']->team_name ?? 'Menunggu Penentuan'); ?>

                                                    </p>
                                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($rankings['third']): ?>
                                                        <p class="text-[8px] text-white/40 truncate font-bold uppercase"><?php echo e($rankings['third']->school_name); ?></p>
                                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                                </div>
                                            </div>
                                        </div>

                                        
                                        <div class="rounded-xl overflow-hidden border border-white/10 bg-black/60 p-2">
                                            <div class="flex items-center gap-2 overflow-hidden">
                                                <span class="text-sm shrink-0 opacity-80">🏅</span>
                                                <div class="overflow-hidden">
                                                    <span class="text-[8px] font-black text-white/50 uppercase tracking-widest block leading-tight">TEMPAT KE-4</span>
                                                    <p class="font-extrabold text-xs text-white/90 truncate leading-tight mt-0.5">
                                                        <?php echo e($rankings['fourth']->team_name ?? 'Menunggu Penentuan'); ?>

                                                    </p>
                                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($rankings['fourth']): ?>
                                                        <p class="text-[8px] text-white/40 truncate font-bold uppercase"><?php echo e($rankings['fourth']->school_name); ?></p>
                                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                                </div>
                                            </div>
                                        </div>

                                        
                                        <div class="rounded-xl overflow-hidden border border-emerald-500/30 bg-gradient-to-r from-emerald-500/10 via-black/60 to-black/80 p-2">
                                            <div class="flex items-center gap-2 overflow-hidden">
                                                <span class="text-sm shrink-0">🎖️</span>
                                                <div class="overflow-hidden">
                                                    <div class="flex items-center gap-1.5 flex-wrap">
                                                        <span class="text-[8px] font-black text-emerald-400 uppercase tracking-widest leading-tight">TEMPAT KE-5</span>
                                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($rankings['fifth_goals'] !== null): ?>
                                                            <span class="text-[7px] font-black text-emerald-300 bg-emerald-500/20 px-1 py-0.2 rounded border border-emerald-500/30">
                                                                <?php echo e($rankings['fifth_goals']); ?> GOL SUKU
                                                            </span>
                                                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                                    </div>
                                                    <p class="font-extrabold text-xs text-white truncate leading-tight mt-0.5">
                                                        <?php echo e($rankings['fifth']->team_name ?? 'Menunggu Suku Akhir'); ?>

                                                    </p>
                                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($rankings['fifth']): ?>
                                                        <p class="text-[8px] text-white/40 truncate font-bold uppercase"><?php echo e($rankings['fifth']->school_name); ?></p>
                                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                                </div>
                                            </div>
                                        </div>

                                    </div>
                                </div>

                            </div>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>
                </div>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    </div>

    
    
    
    <?php $callingMatches = $this->calledMatches(); ?>
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(count($callingMatches) > 0): ?>
        <div class="fixed inset-0 z-50 bg-black/90 backdrop-blur-2xl flex items-center justify-center p-10">
            <div class="w-full max-w-5xl flex flex-col items-center gap-8">
                <div class="flex items-center gap-5">
                    <svg class="w-12 h-12 text-yellow-400 animate-bounce" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                    <div class="text-center">
                        <p class="text-xs font-black text-yellow-400 tracking-[0.3em] uppercase mb-1.5">Panggilan Pasukan</p>
                        <h1 class="text-5xl font-black text-white tracking-widest uppercase drop-shadow-lg">SILA LAPOR DIRI</h1>
                    </div>
                    <svg class="w-12 h-12 text-yellow-400 animate-bounce" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                </div>
                <div class="grid <?php echo e(count($callingMatches) > 1 ? 'grid-cols-2' : 'grid-cols-1 max-w-xl'); ?> gap-6 w-full">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $callingMatches; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $match): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                        <div class="rounded-3xl border-2 border-red-500/50 bg-gradient-to-br from-red-950/90 via-black/90 to-black p-8 shadow-2xl shadow-red-500/20">
                            <div class="text-center mb-6">
                                <div class="inline-flex items-center gap-3 bg-red-500/20 border border-red-500/40 rounded-2xl px-6 py-2.5 shadow-inner">
                                    <div class="w-2 h-2 rounded-full bg-red-400 animate-ping"></div>
                                    <span class="text-2xl font-black text-yellow-300 tracking-widest uppercase">
                                        <?php echo e(is_numeric($match->field_number) ? 'PADANG '.$match->field_number : strtoupper($match->field_number ?? '?')); ?>

                                    </span>
                                </div>
                                <p class="text-sm font-black text-white/50 uppercase tracking-widest mt-2.5">
                                    <?php echo e(optional(optional($match->group)->category)->name ?? ''); ?>

                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($match->round_name): ?> &nbsp;·&nbsp; <?php echo e($match->round_name); ?> <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </p>
                            </div>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($match->group && $match->group->game_type === 'obstacle'): ?>
                                <div class="text-center">
                                    <h3 class="text-3xl font-black text-white"><?php echo e($match->homeTeam->team_name ?? 'BYE'); ?></h3>
                                    <p class="text-base text-white/50 font-bold uppercase mt-1.5"><?php echo e($match->homeTeam->school_name ?? ''); ?></p>
                                </div>
                            <?php else: ?>
                                <div class="grid grid-cols-[1fr_auto_1fr] gap-4 items-center">
                                    
                                    <div class="text-right">
                                        <span class="text-[10px] font-black text-rose-400 uppercase tracking-widest block mb-1">Sudut Merah</span>
                                        <h3 class="text-2xl font-black text-white leading-tight"><?php echo e($match->homeTeam->team_name ?? 'BYE'); ?></h3>
                                        <p class="text-xs text-white/50 font-bold uppercase mt-1"><?php echo e($match->homeTeam->school_name ?? ''); ?></p>
                                    </div>
                                    <div class="w-14 h-14 rounded-full bg-white/5 border border-white/10 flex items-center justify-center shadow-inner">
                                        <span class="text-base font-black text-white/30 italic">VS</span>
                                    </div>
                                    
                                    <div class="text-left">
                                        <span class="text-[10px] font-black text-sky-400 uppercase tracking-widest block mb-1">Sudut Biru</span>
                                        <h3 class="text-2xl font-black text-white leading-tight"><?php echo e($match->awayTeam->team_name ?? 'BYE'); ?></h3>
                                        <p class="text-xs text-white/50 font-bold uppercase mt-1"><?php echo e($match->awayTeam->school_name ?? ''); ?></p>
                                    </div>
                                </div>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </div>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                </div>
                <p class="text-xs font-black text-yellow-400/80 tracking-[0.25em] uppercase animate-pulse">Sila segera lapor diri ke padang yang ditetapkan</p>
            </div>
        </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
</div><?php /**PATH C:\laragon\ElviraSdnBhd\Tournament-Management-System\storage\framework\views/livewire/views/fce08652.blade.php ENDPATH**/ ?>