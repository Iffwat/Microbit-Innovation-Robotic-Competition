<!DOCTYPE html>
<html lang="<?php echo e(app()->getLocale()); ?>" data-theme="mric">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Sistem Pengurusan Pertandingan Microbit Innovation Robotic Competition">
    <title><?php echo $__env->yieldContent('title', 'Papan Pemuka'); ?> � MIRC TMS</title>

    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>
    <?php echo \Livewire\Mechanisms\FrontendAssets\FrontendAssets::styles(); ?>

</head>
<body class="bg-base-200 min-h-screen" x-data>

<div class="drawer lg:drawer-open">
    <input id="sidebar-drawer" type="checkbox" class="drawer-toggle" />

    
    <div class="drawer-content flex flex-col min-h-screen">

        
        <header class="navbar bg-white border-b border-base-300 sticky top-0 z-30 px-4 gap-3">
            <div class="navbar-start gap-2">
                <label for="sidebar-drawer" class="btn btn-ghost btn-sm lg:hidden p-2">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="w-5 h-5 stroke-current">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </label>
                <span class="text-base font-bold text-primary lg:hidden">MIRC TMS</span>
            </div>

            <div class="navbar-center hidden lg:flex">
                <h1 class="text-base font-semibold text-base-content/60 tracking-wide"><?php echo $__env->yieldContent('page-title', 'Papan Pemuka'); ?></h1>
            </div>

            <div class="navbar-end gap-3">
                
                <div class="join border border-base-300 rounded-lg overflow-hidden">
                    <a href="<?php echo e(route('lang.switch', 'ms')); ?>"
                       class="join-item px-3 py-1.5 text-xs font-semibold transition-colors <?php echo e(app()->getLocale() === 'ms' ? 'bg-primary text-white' : 'bg-white text-base-content/60 hover:bg-base-200'); ?>">MS</a>
                    <a href="<?php echo e(route('lang.switch', 'en')); ?>"
                       class="join-item px-3 py-1.5 text-xs font-semibold transition-colors <?php echo e(app()->getLocale() === 'en' ? 'bg-primary text-white' : 'bg-white text-base-content/60 hover:bg-base-200'); ?>">EN</a>
                </div>
                
                <div class="hidden md:flex items-center gap-2">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(session('auth_role') === 'master'): ?>
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-primary/10 text-primary">
                            <span class="w-1.5 h-1.5 rounded-full bg-primary"></span>
                            <?php echo e(__('Master Admin')); ?>

                        </span>
                    <?php else: ?>
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-secondary/10 text-secondary">
                            <span class="w-1.5 h-1.5 rounded-full bg-secondary"></span>
                            <?php echo e(__('Sukarelawan')); ?>

                        </span>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>

                
                <div class="dropdown dropdown-end">
                    <div tabindex="0" role="button" class="btn btn-ghost btn-sm btn-circle">
                        <div class="w-8 h-8 rounded-full <?php echo e(session('auth_role') === 'master' ? 'bg-gradient-to-br from-primary to-secondary' : 'bg-gradient-to-br from-secondary to-accent'); ?> flex items-center justify-center text-white text-xs font-bold">
                            <?php echo e(session('auth_role') === 'master' ? 'M' : 'S'); ?>

                        </div>
                    </div>
                    <ul tabindex="0" class="menu menu-sm dropdown-content mt-2 z-[1] p-2 shadow-xl bg-white rounded-2xl w-44 border border-base-200">
                        <li class="menu-title text-xs px-2 pb-1">
                            <?php echo e(session('auth_role') === 'master' ? __('Master Admin') : __('Sukarelawan')); ?>

                        </li>
                        <li>
                            <form method="POST" action="<?php echo e(route('admin.logout')); ?>">
                                <?php echo csrf_field(); ?>
                                <button type="submit" class="text-error font-medium w-full text-left flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                                    <?php echo e(__('Log Keluar')); ?>

                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </header>

        
        <main class="flex-1 p-4 md:p-6 animate-fade-in">
            
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(session('success')): ?>
                <div class="mb-4 flex items-center gap-3 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl px-4 py-3 text-sm font-medium animate-slide-up">
                    <svg class="w-5 h-5 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <?php echo e(session('success')); ?>

                </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(session('error')): ?>
                <div class="mb-4 flex items-center gap-3 bg-red-50 border border-red-200 text-red-800 rounded-xl px-4 py-3 text-sm font-medium animate-slide-up">
                    <svg class="w-5 h-5 text-red-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <?php echo e(session('error')); ?>

                </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

            <?php echo $__env->yieldContent('content'); ?>
        </main>

        <footer class="py-3 px-6 text-center text-xs text-base-content/30 border-t border-base-200 bg-white">
            © <?php echo e(date('Y')); ?> Microbit Innovation Robotic Competition • MIRC TMS v1.0
        </footer>
    </div>

    
    <aside class="drawer-side z-40">
        <label for="sidebar-drawer" aria-label="close sidebar" class="drawer-overlay"></label>
        <nav class="w-64 min-h-full bg-white border-r border-base-200 flex flex-col">

            
            <div class="px-5 py-5 border-b border-base-200">
                <div class="flex items-center gap-3 mb-3">
                    <div class="flex gap-1.5">
                        <div class="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center overflow-hidden">
                            <img src="<?php echo e(asset('images/company-logo.png')); ?>" class="w-full h-full object-contain" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';" alt="">
                            <span class="text-primary text-lg hidden">⚡</span>
                        </div>
                        <div class="w-10 h-10 rounded-xl bg-secondary/10 flex items-center justify-center overflow-hidden">
                            <img src="<?php echo e(asset('images/competition-logo.png')); ?>" class="w-full h-full object-contain" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';" alt="">
                            <span class="text-secondary text-lg hidden">🤖</span>
                        </div>
                    </div>
                </div>
                <p class="font-extrabold text-sm text-primary leading-tight">MICROBIT INNOVATION</p>
                <p class="font-bold text-xs text-secondary leading-tight">ROBOTIC COMPETITION</p>
                <p class="text-xs text-base-content/40 mt-0.5"><?php echo e(__('Sistem Pengurusan Pertandingan')); ?></p>
            </div>

            
            <div class="flex-1 px-3 py-4 space-y-0.5 overflow-y-auto">

                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(session('auth_role') === 'master'): ?>
                
                <p class="px-4 pt-2 pb-1 text-[10px] font-bold uppercase tracking-widest text-base-content/30"><?php echo e(__('Utama')); ?></p>
                <a href="<?php echo e(route('admin.dashboard')); ?>"
                   class="nav-item <?php echo e(request()->routeIs('admin.dashboard') ? 'active' : ''); ?>">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                    <?php echo e(__('Papan Pemuka')); ?>

                </a>

                <p class="px-4 pt-4 pb-1 text-[10px] font-bold uppercase tracking-widest text-base-content/30"><?php echo e(__('Data')); ?></p>
                <a href="<?php echo e(route('admin.import.index')); ?>"
                   class="nav-item <?php echo e(request()->routeIs('admin.import.*') ? 'active' : ''); ?>">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                    <?php echo e(__('Import CSV')); ?>

                </a>
                <a href="<?php echo e(route('admin.teams.index')); ?>"
                   class="nav-item <?php echo e(request()->routeIs('admin.teams.*') ? 'active' : ''); ?>">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0"/></svg>
                    <?php echo e(__('Senarai Pasukan')); ?>

                </a>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                <p class="px-4 pt-4 pb-1 text-[10px] font-bold uppercase tracking-widest text-base-content/30"><?php echo e(__('Hari Pertandingan')); ?></p>
                <a href="<?php echo e(route('admin.checkin.index')); ?>"
                   class="nav-item <?php echo e(request()->routeIs('admin.checkin.index') ? 'active' : ''); ?>">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <?php echo e(__('Semak Masuk')); ?>

                </a>
                <a href="<?php echo e(route('admin.checkin.dashboard')); ?>"
                   class="nav-item <?php echo e(request()->routeIs('admin.checkin.dashboard') ? 'active' : ''); ?>">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                    <?php echo e(__('Papan Kehadiran')); ?>

                </a>

                <p class="px-4 pt-4 pb-1 text-[10px] font-bold uppercase tracking-widest text-base-content/30"><?php echo e(__('Pertandingan')); ?></p>

                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(session('auth_role') === 'master'): ?>
                <a href="<?php echo e(route('admin.groups.index')); ?>"
                   class="nav-item <?php echo e(request()->routeIs('admin.groups.*') ? 'active' : ''); ?>">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                    <?php echo e(__('Jana Kumpulan')); ?>

                </a>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                <a href="<?php echo e(route('admin.matches.index')); ?>"
                   class="nav-item <?php echo e(request()->routeIs('admin.matches.*') ? 'active' : ''); ?>">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    <?php echo e(__('Perlawanan')); ?>

                </a>
                <a href="<?php echo e(route('admin.knockout.index')); ?>"
                   class="nav-item <?php echo e(request()->routeIs('admin.knockout.*') ? 'active' : ''); ?>">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3l14 9-14 9V3z"/></svg>
                    <?php echo e(__('Bracket Knockout')); ?>

                </a>
            </div>

            
            <div class="px-3 py-3 border-t border-base-200">
                <form method="POST" action="<?php echo e(route('admin.logout')); ?>">
                    <?php echo csrf_field(); ?>
                    <button type="submit" class="nav-item w-full text-error hover:bg-red-50 hover:text-red-600 justify-start">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                        <?php echo e(__('Log Keluar')); ?>

                    </button>
                </form>
            </div>
        </nav>
    </aside>
</div>

<?php echo \Livewire\Mechanisms\FrontendAssets\FrontendAssets::scripts(); ?>

</body>
</html>
<?php /**PATH C:\laragon\ElviraSdnBhd\Tournament-Management-System\resources\views/layouts/admin.blade.php ENDPATH**/ ?>