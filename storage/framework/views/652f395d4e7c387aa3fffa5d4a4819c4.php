<!DOCTYPE html>
<html lang="ms" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Live TV - MIRC</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>
    <?php echo \Livewire\Mechanisms\FrontendAssets\FrontendAssets::styles(); ?>

    <style>
        body { background-color: #050510; color: #fff; overflow: hidden; font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="h-screen w-screen flex flex-col">
    <!-- Header -->
    <header class="h-20 bg-black/40 backdrop-blur-md border-b border-white/10 flex items-center justify-between px-8 shrink-0">
        <div class="flex items-center gap-4">
            <div class="flex gap-2">
                <div class="w-12 h-12 rounded-xl bg-primary/20 flex items-center justify-center p-2">
                    <img src="<?php echo e(asset('images/company-logo.png')); ?>" class="w-full h-full object-contain" alt="">
                </div>
                <div class="w-12 h-12 rounded-xl bg-secondary/20 flex items-center justify-center p-2">
                    <img src="<?php echo e(asset('images/competition-logo.png')); ?>" class="w-full h-full object-contain" alt="">
                </div>
            </div>
            <div>
                <h1 class="text-2xl font-black text-white tracking-wider">LIVE TV <span class="text-primary">•</span> MIRC</h1>
                <p class="text-white/50 text-sm font-bold tracking-widest uppercase mt-0.5">Pertandingan Robotik & Dron</p>
            </div>
        </div>
        <div class="flex items-center gap-6">
            <!-- Pulsing LIVE badge -->
            <div class="flex items-center gap-2 bg-red-500/20 border border-red-500/50 px-4 py-2 rounded-full">
                <div class="w-2.5 h-2.5 rounded-full bg-red-500 animate-pulse"></div>
                <span class="text-red-400 text-sm font-black tracking-widest uppercase">Secara Langsung</span>
            </div>
            <div class="text-right">
                <p class="text-2xl font-black text-white" x-data="{ time: new Date().toLocaleTimeString('en-US', {hour: '2-digit', minute:'2-digit'}) }" x-init="setInterval(() => time = new Date().toLocaleTimeString('en-US', {hour: '2-digit', minute:'2-digit'}), 1000)" x-text="time"></p>
                <p class="text-white/50 text-xs font-bold uppercase tracking-widest" x-data="{ date: new Date().toLocaleDateString('ms-MY', {weekday: 'long', day: 'numeric', month: 'long'}) }" x-text="date"></p>
            </div>
        </div>
    </header>

    <!-- Main Livewire Component -->
    <main class="flex-1 overflow-hidden p-6">
        <?php
$__split = function ($name, $params = []) {
    return [$name, $params];
};
[$__name, $__params] = $__split('live-tv', []);

$__keyOuter = $__key ?? null;

$__key = null;
$__componentSlots = [];

$__key ??= \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::generateKey('lw-2177191547-0', $__key);

$__html = app('livewire')->mount($__name, $__params, $__key, $__componentSlots);

echo $__html;

unset($__html);
unset($__key);
$__key = $__keyOuter;
unset($__keyOuter);
unset($__name);
unset($__params);
unset($__componentSlots);
unset($__split);
?>
    </main>

    <?php echo \Livewire\Mechanisms\FrontendAssets\FrontendAssets::scripts(); ?>

</body>
</html>
<?php /**PATH C:\laragon\ElviraSdnBhd\Tournament-Management-System\resources\views/pages/live-tv.blade.php ENDPATH**/ ?>