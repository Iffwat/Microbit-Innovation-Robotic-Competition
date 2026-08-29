<?php
use App\Models\Team;
use App\Models\Group;
use App\Models\Category;
use App\Models\TournamentMatch;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
?>

<div class="min-h-screen bg-base-200 pb-24">
    <!-- Top Nav / Header -->
    <div class="bg-primary text-primary-content sticky top-0 z-40 shadow-md">
        <div class="max-w-3xl mx-auto px-4 py-3 flex items-center justify-between">
            <div>
                <h1 class="text-xl font-black italic tracking-wider uppercase flex items-center gap-2">
                    <svg class="w-6 h-6 text-yellow-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                    <?php echo e(__('Hab Peserta')); ?>

                </h1>
                <p class="text-[10px] font-bold text-primary-content/70 tracking-widest uppercase"><?php echo e(__('Portal Rasmi Kejohanan')); ?></p>
            </div>
            
            <div class="flex items-center gap-2">
                
                <div class="join border border-white/20 rounded-lg overflow-hidden bg-black/20 text-white">
                    <a href="<?php echo e(route('lang.switch', 'ms')); ?>"
                       class="join-item px-2.5 py-1 text-xs font-bold transition-colors <?php echo e(app()->getLocale() === 'ms' ? 'bg-white text-primary' : 'text-white/70 hover:text-white'); ?>">MS</a>
                    <a href="<?php echo e(route('lang.switch', 'en')); ?>"
                       class="join-item px-2.5 py-1 text-xs font-bold transition-colors <?php echo e(app()->getLocale() === 'en' ? 'bg-white text-primary' : 'text-white/70 hover:text-white'); ?>">EN</a>
                </div>
                <a href="/" class="btn btn-sm btn-ghost btn-circle text-white">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" /></svg>
                </a>
            </div>
        </div>
    </div>

    <!-- Main Content Area -->
    <div class="max-w-3xl mx-auto p-4 space-y-6">
        
        
        
        
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($activeTab === 'search'): ?>
            
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!$viewTeamId): ?>
                <div class="text-center space-y-3 mt-6 mb-8 animate-fade-in">
                    <h2 class="text-3xl font-black text-base-content leading-tight"><?php echo e(__('Semak Status & Jadual Pasukan Anda.')); ?></h2>
                    <p class="text-sm text-base-content/60"><?php echo e(__('Taip nama pasukan atau nama sekolah untuk melihat maklumat terperinci pendaftaran dan jadual penuh.')); ?></p>
                </div>

                <div class="bg-white p-2 rounded-2xl shadow-lg border border-base-200 flex items-center gap-2 sticky top-20 z-30 animate-slide-up">
                    <div class="pl-3 text-base-content/40">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                    </div>
                    <input wire:model.live.debounce.500ms="search" type="text" placeholder="<?php echo e(__('Contoh: SK Gombak...')); ?>" class="flex-1 bg-transparent py-3 text-base font-medium focus:outline-none w-full" autofocus>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(strlen($search) > 0): ?>
                        <button wire:click="$set('search', '')" class="p-2 text-base-content/40 hover:text-base-content rounded-xl transition-colors">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>

                <div class="mt-8 space-y-4">
                    <div wire:loading class="w-full text-center py-8">
                        <span class="loading loading-spinner text-primary"></span>
                        <p class="text-sm text-base-content/50 mt-2 font-medium"><?php echo e(__('Mencari rekod...')); ?></p>
                    </div>

                    <div wire:loading.remove>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(strlen(trim($search)) >= 3): ?>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($this->searchResults->isEmpty()): ?>
                                <div class="text-center py-12 bg-white rounded-3xl border border-base-200 border-dashed">
                                    <p class="text-lg font-bold text-base-content/40"><?php echo e(__('Tiada pasukan dijumpai.')); ?></p>
                                </div>
                            <?php else: ?>
                                <p class="text-xs font-bold text-base-content/50 uppercase tracking-widest px-2 mb-3"><?php echo e(__('Hasil Carian')); ?> (<?php echo e($this->searchResults->count()); ?>)</p>
                                <div class="grid grid-cols-1 gap-3">
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $this->searchResults; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $team): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                        <button wire:click="viewTeam(<?php echo e($team->id); ?>)" class="w-full text-left bg-white border-2 border-base-200 hover:border-primary p-4 rounded-2xl shadow-sm transition-all flex items-center justify-between group">
                                            <div>
                                                <h3 class="font-bold text-base-content text-lg group-hover:text-primary transition-colors"><?php echo e($team->team_name); ?></h3>
                                                <p class="text-xs text-base-content/60 font-medium">🏫 <?php echo e($team->school_name); ?></p>
                                            </div>
                                            <div class="shrink-0 flex flex-col items-end gap-2">
                                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($team->status === 'checked_in'): ?>
                                                    <span class="bg-emerald-100 text-emerald-700 text-[10px] font-black px-2 py-1 rounded-md uppercase tracking-wider"><?php echo e(__('Hadir')); ?></span>
                                                <?php else: ?>
                                                    <span class="bg-base-200 text-base-content/50 text-[10px] font-black px-2 py-1 rounded-md uppercase tracking-wider"><?php echo e(__('Berdaftar')); ?></span>
                                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                                <svg class="w-5 h-5 text-base-content/20 group-hover:text-primary group-hover:translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                            </div>
                                        </button>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                                </div>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>
                </div>
            <?php else: ?>
                
                <?php $details = $this->myTeamDetails; ?>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($details): ?>
                    <div class="animate-fade-in">
                        <button wire:click="clearViewTeam" class="inline-flex items-center gap-2 text-sm font-bold text-base-content/60 hover:text-primary mb-4 transition-colors">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                            <?php echo e(__('Kembali ke carian')); ?>

                        </button>

                        <!-- ID Card -->
                        <div class="bg-gradient-to-br from-primary to-primary-focus p-6 rounded-3xl shadow-xl text-white relative overflow-hidden">
                            <div class="absolute -right-4 -bottom-4 opacity-10">
                                <svg class="w-48 h-48" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2L2 22h20L12 2z"/></svg>
                            </div>
                            <div class="relative z-10">
                                <div class="inline-block bg-white/20 px-3 py-1 rounded-full text-xs font-bold tracking-widest uppercase mb-3 backdrop-blur-sm border border-white/20">
                                    <?php echo e($details['team']->category->name ?? 'Kategori Umum'); ?>

                                </div>
                                <h2 class="text-3xl font-black leading-tight"><?php echo e($details['team']->team_name); ?></h2>
                                <p class="text-white/80 font-medium mt-1">🏫 <?php echo e($details['team']->school_name); ?></p>
                                
                                <div class="mt-6 flex flex-wrap gap-3">
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($details['team']->status === 'checked_in'): ?>
                                        <div class="bg-emerald-400 text-emerald-950 text-xs font-black px-3 py-1.5 rounded-lg flex items-center gap-1.5 shadow-lg shadow-emerald-500/20">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                            <?php echo e(__('DISAHKAN HADIR')); ?>

                                        </div>
                                    <?php else: ?>
                                        <div class="bg-white/20 text-white border border-white/30 text-xs font-black px-3 py-1.5 rounded-lg flex items-center gap-1.5 backdrop-blur-md">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            <?php echo e(__('BELUM CHECK-IN')); ?>

                                        </div>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Schedule List -->
                        <div class="mt-8">
                            <h3 class="text-sm font-black text-base-content/60 uppercase tracking-widest mb-4 flex items-center gap-2">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                <?php echo e(__('Jadual Perlawanan Pasukan')); ?>

                            </h3>
                            
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($details['matches']->isEmpty()): ?>
                                <div class="bg-white border-2 border-base-200 border-dashed rounded-3xl p-8 text-center">
                                    <p class="text-base-content/40 font-bold"><?php echo e(__('Jadual perlawanan belum dikeluarkan.')); ?></p>
                                </div>
                            <?php else: ?>
                                <div class="space-y-4">
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $details['matches']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $match): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                        <?php
                                            $isHome = $match->home_team_id === $this->viewTeamId;
                                            $opponent = $isHome ? $match->awayTeam : $match->homeTeam;
                                            $myScore = $isHome ? $match->home_score : $match->away_score;
                                            $oppScore = $isHome ? $match->away_score : $match->home_score;
                                            $isWin = $match->winner_team_id === $this->viewTeamId;
                                            $isLoss = $match->winner_team_id && $match->winner_team_id !== $this->viewTeamId;
                                        ?>
                                        
                                        <div class="bg-white border-2 <?php echo e($match->status === 'in_progress' ? 'border-primary shadow-lg shadow-primary/10' : 'border-base-200'); ?> rounded-2xl p-5 relative overflow-hidden">
                                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($match->status === 'in_progress'): ?>
                                                <div class="absolute top-0 right-0 bg-primary text-white text-[9px] font-black uppercase tracking-widest px-3 py-1 rounded-bl-xl animate-pulse">
                                                    <?php echo e(__('SEDANG BERLANGSUNG')); ?>

                                                </div>
                                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                            
                                            <div class="flex justify-between items-start mb-3">
                                                <div class="inline-block bg-base-200 text-base-content/60 text-[10px] font-bold px-2 py-0.5 rounded uppercase tracking-wider">
                                                    <?php echo e($match->stage === 'group' ? ($match->group->group_name ?? __('Kumpulan')) : $match->round_name); ?>

                                                </div>
                                                
                                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($match->field_number): ?>
                                                    <div class="text-xs font-bold text-secondary">
                                                        <?php echo e(__('Padang')); ?> <?php echo e($match->field_number); ?>

                                                    </div>
                                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                            </div>

                                            <div class="flex items-center gap-4">
                                                <!-- Opponent Info -->
                                                <div class="flex-1">
                                                    <p class="text-[10px] font-bold text-base-content/40 uppercase"><?php echo e(__('Lawan')); ?></p>
                                                    <h4 class="font-bold text-base-content text-lg leading-tight"><?php echo e($opponent->team_name ?? 'TBD'); ?></h4>
                                                </div>
                                                
                                                <!-- Score/Status -->
                                                <div class="shrink-0 text-center">
                                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($match->status === 'completed'): ?>
                                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($match->group && $match->group->game_type === 'obstacle'): ?>
                                                            <!-- Obstacle Time Result -->
                                                            <div class="bg-base-200 px-3 py-1 rounded-lg">
                                                                <span class="text-xs font-bold text-base-content/50 block"><?php echo e(__('Masa Direkod')); ?></span>
                                                                <span class="text-lg font-black text-emerald-600"><?php echo e($match->formatted_obstacle_time); ?></span>
                                                            </div>
                                                        <?php else: ?>
                                                            <div class="bg-base-100 border border-base-200 px-4 py-2 rounded-xl flex items-center justify-center gap-3">
                                                                <span class="text-xl font-black <?php echo e($isWin ? 'text-emerald-600' : ($isLoss ? 'text-red-500' : 'text-base-content')); ?>"><?php echo e($myScore ?? 0); ?></span>
                                                                <span class="text-xs font-bold text-base-content/30">-</span>
                                                                <span class="text-xl font-black <?php echo e($isLoss ? 'text-emerald-600' : ($isWin ? 'text-red-500' : 'text-base-content')); ?>"><?php echo e($oppScore ?? 0); ?></span>
                                                            </div>
                                                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($isWin): ?> <p class="text-[10px] font-bold text-emerald-600 uppercase mt-1"><?php echo e(__('MENANG')); ?></p>
                                                            <?php elseif($isLoss): ?> <p class="text-[10px] font-bold text-red-500 uppercase mt-1"><?php echo e(__('KALAH')); ?></p>
                                                            <?php else: ?> <p class="text-[10px] font-bold text-base-content/50 uppercase mt-1"><?php echo e(__('SERI')); ?></p>
                                                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                                    <?php else: ?>
                                                        <span class="text-xs font-bold text-base-content/40 uppercase bg-base-200 px-3 py-1.5 rounded-lg block"><?php echo e(__('Belum Mula')); ?></span>
                                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                                </div>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

        
        
        
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($activeTab === 'standings'): ?>
            <div class="animate-fade-in space-y-6">
                <div>
                    <h2 class="text-2xl font-black text-base-content"><?php echo e(__('Kedudukan Awam')); ?></h2>
                    <p class="text-sm text-base-content/60"><?php echo e(__('Pilih kategori untuk melihat carta kedudukan terkini peringkat kumpulan/masa.')); ?></p>
                </div>

                <!-- Category Selector -->
                <div class="overflow-x-auto pb-2 -mx-4 px-4 hide-scrollbar">
                    <div class="flex gap-2 w-max">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $this->standingsFilters; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $filter): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                            <button wire:click="$set('selectedFilterId', '<?php echo e($filter['id']); ?>')" 
                                class="px-4 py-2 rounded-xl text-sm font-bold border-2 transition-all whitespace-nowrap <?php echo e($selectedFilterId === $filter['id'] ? 'bg-primary border-primary text-white shadow-md shadow-primary/20' : 'bg-white border-base-200 text-base-content/70 hover:border-primary/50'); ?>">
                                <?php echo e($filter['label']); ?>

                            </button>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                    </div>
                </div>

                <!-- Groups List -->
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($this->publicGroups->isEmpty()): ?>
                    <div class="bg-white border-2 border-base-200 border-dashed rounded-3xl p-10 text-center">
                        <svg class="w-12 h-12 text-base-content/20 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                        <p class="text-base-content/50 font-bold"><?php echo e(__('Tiada data liga untuk kategori ini.')); ?></p>
                    </div>
                <?php else: ?>
                    <div class="space-y-6">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $this->publicGroups; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $group): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                            <div class="bg-white rounded-3xl border border-base-200 shadow-sm overflow-hidden">
                                <div class="bg-base-100 px-5 py-3 border-b border-base-200 flex justify-between items-center">
                                    <h3 class="font-black text-base-content"><?php echo e($group->group_name); ?></h3>
                                    <span class="text-[10px] font-bold uppercase text-base-content/40 tracking-wider">
                                        <?php echo e($group->game_type === 'obstacle' ? __('Senarai Masa Terbaik') : __('Carta Liga')); ?>

                                    </span>
                                </div>
                                
                                <div class="divide-y divide-base-100">
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $group->standings; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $gt): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                        <div class="p-3 flex items-center gap-3 hover:bg-base-50 transition-colors">
                                            <div class="w-6 text-center font-black text-sm <?php echo e($index < 2 && $group->game_type !== 'obstacle' ? 'text-primary' : 'text-base-content/30'); ?>">
                                                <?php echo e($index + 1); ?>

                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <h4 class="font-bold text-sm text-base-content truncate"><?php echo e($gt->team->team_name); ?></h4>
                                                <p class="text-[10px] text-base-content/50 truncate"><?php echo e($gt->team->school_name); ?></p>
                                            </div>
                                            
                                            <div class="shrink-0 text-right">
                                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($group->game_type === 'obstacle'): ?>
                                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($gt->goals_for > 0): ?>
                                                        <?php
                                                            $ms = $gt->goals_for;
                                                            $m = floor($ms / 60000);
                                                            $s = floor(($ms % 60000) / 1000);
                                                            $ms_remain = $ms % 1000;
                                                        ?>
                                                        <span class="font-black text-emerald-600 text-sm bg-emerald-50 px-2 py-1 rounded-md"><?php echo e(sprintf('%02d:%02d.%03d', $m, $s, $ms_remain)); ?></span>
                                                    <?php else: ?>
                                                        <span class="font-bold text-base-content/30 text-xs">-</span>
                                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                                <?php else: ?>
                                                    <div class="grid grid-cols-4 gap-1 min-w-[155px] text-center items-center">
                                                        <div class="text-center">
                                                            <span class="block text-[8px] font-bold text-base-content/40 uppercase whitespace-nowrap"><?php echo e(__('Main')); ?></span>
                                                            <span class="text-xs font-bold text-base-content/80"><?php echo e($gt->played); ?></span>
                                                        </div>
                                                        <div class="text-center">
                                                            <span class="block text-[8px] font-bold text-base-content/40 uppercase whitespace-nowrap"><?php echo e(__('Gol')); ?></span>
                                                            <span class="text-xs font-black text-amber-600"><?php echo e($gt->goals_for); ?></span>
                                                        </div>
                                                        <div class="text-center">
                                                            <span class="block text-[8px] font-bold text-base-content/40 uppercase whitespace-nowrap"><?php echo e(__('Menang')); ?></span>
                                                            <span class="text-xs font-bold text-emerald-600"><?php echo e($gt->won); ?></span>
                                                        </div>
                                                        <div class="text-center bg-base-100 rounded py-0.5 px-1">
                                                            <span class="block text-[8px] font-bold text-base-content/40 uppercase whitespace-nowrap"><?php echo e(__('Mata')); ?></span>
                                                            <span class="text-sm font-black text-primary"><?php echo e($gt->points); ?></span>
                                                        </div>
                                                    </div>
                                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                            </div>
                                        </div>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                                </div>
                            </div>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                    </div>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

        
        
        
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($activeTab === 'knockout'): ?>
            <div class="animate-fade-in space-y-6">
                <div>
                    <h2 class="text-2xl font-black text-base-content"><?php echo e(__('Carta Kalah Mati')); ?></h2>
                    <p class="text-sm text-base-content/60"><?php echo e(__('Carta pusingan akhir (Knockout Bracket).')); ?></p>
                </div>

                <!-- Category Selector -->
                <div class="overflow-x-auto pb-2 -mx-4 px-4 hide-scrollbar">
                    <div class="flex gap-2 w-max">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $this->standingsFilters->where('type', '!=', 'obs'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $filter): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                            <button wire:click="$set('selectedKnockoutCategoryId', <?php echo e($filter['category_id']); ?>)" 
                                class="px-4 py-2 rounded-xl text-sm font-bold border-2 transition-all whitespace-nowrap <?php echo e($selectedKnockoutCategoryId === $filter['category_id'] ? 'bg-primary border-primary text-white shadow-md shadow-primary/20' : 'bg-white border-base-200 text-base-content/70 hover:border-primary/50'); ?>">
                                <?php echo e($filter['label']); ?>

                            </button>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                    </div>
                </div>

                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($this->knockoutBrackets->isEmpty()): ?>
                    <div class="bg-white border-2 border-base-200 border-dashed rounded-3xl p-10 text-center">
                        <svg class="w-12 h-12 text-base-content/20 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                        <p class="text-base-content/50 font-bold"><?php echo e(__('Carta kalah mati belum dijana untuk kategori ini.')); ?></p>
                    </div>
                <?php else: ?>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $this->knockoutBrackets; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $stage => $rounds): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                        <div class="bg-white rounded-3xl border-2 border-base-200 shadow-sm overflow-hidden mb-6">
                            <div class="bg-gradient-to-r <?php echo e($stage === 'trophy_knockout' ? 'from-amber-400 to-yellow-500' : 'from-slate-300 to-slate-400'); ?> px-5 py-4">
                                <h3 class="font-black text-white text-lg">
                                    <?php echo e($stage === 'trophy_knockout' ? __('🏆 PUSINGAN TROFI') : __('🥈 PUSINGAN PIALA')); ?>

                                </h3>
                            </div>
                            
                            <div class="p-4 space-y-8">
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $rounds; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $roundName => $matches): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                    <div>
                                        <h4 class="text-sm font-black text-base-content/50 uppercase tracking-widest mb-3 border-b border-base-200 pb-2"><?php echo e($roundName); ?></h4>
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $matches; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $match): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                                <div class="bg-base-50 rounded-xl border border-base-200 p-3 relative flex flex-col justify-center">
                                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($match->winner_team_id): ?>
                                                        <div class="absolute -right-2 -top-2 bg-emerald-500 text-white rounded-full p-1 shadow-sm">
                                                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                                        </div>
                                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                                    
                                                    <!-- Home -->
                                                    <div class="flex justify-between items-center mb-2 <?php echo e($match->winner_team_id === $match->home_team_id ? 'text-primary font-bold' : ''); ?>">
                                                        <span class="text-sm truncate pr-2 <?php echo e(!$match->home_team_id ? 'text-base-content/30 italic' : ''); ?>"><?php echo e($match->homeTeam->team_name ?? __('Menunggu...')); ?></span>
                                                        <span class="font-black"><?php echo e($match->home_score ?? '-'); ?></span>
                                                    </div>
                                                    <!-- Away -->
                                                    <div class="flex justify-between items-center <?php echo e($match->winner_team_id === $match->away_team_id ? 'text-primary font-bold' : ''); ?>">
                                                        <span class="text-sm truncate pr-2 <?php echo e(!$match->away_team_id ? 'text-base-content/30 italic' : ''); ?>"><?php echo e($match->awayTeam->team_name ?? __('Menunggu...')); ?></span>
                                                        <span class="font-black"><?php echo e($match->away_score ?? '-'); ?></span>
                                                    </div>
                                                </div>
                                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                                        </div>
                                    </div>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                            </div>
                        </div>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    </div>

    <!-- Bottom Mobile Navigation Bar -->
    <div class="fixed bottom-0 w-full bg-white border-t border-base-200 pb-safe z-50 shadow-[0_-4px_20px_-10px_rgba(0,0,0,0.1)]">
        <div class="flex justify-around items-center max-w-3xl mx-auto h-16">
            <button wire:click="setTab('search')" class="flex-1 flex flex-col items-center justify-center gap-1 h-full transition-colors <?php echo e($activeTab === 'search' ? 'text-primary' : 'text-base-content/40 hover:text-base-content'); ?>">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                <span class="text-[10px] font-bold tracking-wider uppercase"><?php echo e(__('Jadual')); ?></span>
            </button>
            
            <button wire:click="setTab('standings')" class="flex-1 flex flex-col items-center justify-center gap-1 h-full transition-colors <?php echo e($activeTab === 'standings' ? 'text-primary' : 'text-base-content/40 hover:text-base-content'); ?>">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
                <span class="text-[10px] font-bold tracking-wider uppercase"><?php echo e(__('Kedudukan')); ?></span>
            </button>

            <button wire:click="setTab('knockout')" class="flex-1 flex flex-col items-center justify-center gap-1 h-full transition-colors <?php echo e($activeTab === 'knockout' ? 'text-primary' : 'text-base-content/40 hover:text-base-content'); ?>">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" /></svg>
                <span class="text-[10px] font-bold tracking-wider uppercase"><?php echo e(__('Kalah Mati')); ?></span>
            </button>
        </div>
    </div>
</div><?php /**PATH C:\laragon\ElviraSdnBhd\Tournament-Management-System\storage\framework\views/livewire/views/d0c4c184.blade.php ENDPATH**/ ?>