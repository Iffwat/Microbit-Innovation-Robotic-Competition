<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ImportController;
use App\Http\Controllers\Admin\TeamController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\AuthController;

// Redirect root to admin dashboard or public semakan
Route::get('/', function () {
    return redirect()->route(session('auth_role') ? 'admin.dashboard' : 'semakan');
});

// Language Switcher Route
Route::get('/lang/{locale}', function ($locale) {
    if (in_array($locale, ['en', 'ms'])) {
        session(['locale' => $locale]);
    }
    return back();
})->name('lang.switch');

// Cache Clear Route for Shared Hosting without Terminal
Route::get('/clear-all-cache', function () {
    $extraMsg = "";

    try {
        \Illuminate\Support\Facades\Artisan::call('view:clear');
        \Illuminate\Support\Facades\Artisan::call('cache:clear');
        \Illuminate\Support\Facades\Artisan::call('config:clear');
        \Illuminate\Support\Facades\Artisan::call('route:clear');
    } catch (\Throwable $e) {
        // Continue
    }
    
    // 1. Remove old/corrupted unicode or zap files (⚡ or тЪб or ?) that block clean updates on Linux
    try {
        $targetDirs = [
            resource_path('views/components/admin/matches'),
            resource_path('views/components/admin/groups'),
            resource_path('views/components'),
            resource_path('views/livewire/admin/matches'),
            resource_path('views/livewire/admin/groups'),
            resource_path('views/livewire'),
        ];
        $removedLegacyFiles = [];
        foreach ($targetDirs as $d) {
            if (!is_dir($d)) continue;
            $scan = @scandir($d);
            if (!$scan) continue;
            foreach ($scan as $file) {
                if ($file === '.' || $file === '..') continue;
                // Match any file containing non-ASCII character (⚡, тЪб, etc.) or leading question mark
                if (preg_match('/[^\x20-\x7E]/', $file) || str_starts_with($file, '?')) {
                    $filePath = $d . '/' . $file;
                    if (is_file($filePath)) {
                        @unlink($filePath);
                        $removedLegacyFiles[] = $file;
                    }
                }
            }
        }
        $removedCount = count($removedLegacyFiles);
        if ($removedCount > 0) {
            $extraMsg .= "<p style='color:#047857; font-size:12px; margin-top:6px;'>Dibersihkan $removedCount fail legasi lama: " . htmlspecialchars(implode(', ', array_slice($removedLegacyFiles, 0, 5))) . "</p>";
        }
    } catch (\Throwable $e) {
        // Continue
    }

    // 2. Force delete all cached blade files in storage/framework/views (including subdirectories)
    try {
        $cleanDir = function($dir) use (&$cleanDir) {
            $files = glob($dir . '/*');
            if ($files) {
                foreach ($files as $f) {
                    if (is_file($f) && basename($f) !== '.gitignore') {
                        @unlink($f);
                    } elseif (is_dir($f)) {
                        $cleanDir($f);
                        @rmdir($f);
                    }
                }
            }
        };
        $cleanDir(storage_path('framework/views'));
    } catch (\Throwable $e) {
        // Continue
    }

    // 3. Recalculate all group standings from completed matches to eliminate redundant matches and phantom draws
    try {
        if (class_exists(\App\Models\Group::class) && method_exists(\App\Models\TournamentMatch::class, 'recalculateGroupStandings')) {
            $recalculatedGroupsCount = 0;
            $groups = \App\Models\Group::all();
            foreach ($groups as $grp) {
                \App\Models\TournamentMatch::recalculateGroupStandings($grp->id);
                $recalculatedGroupsCount++;
            }
            if ($recalculatedGroupsCount > 0) {
                $extraMsg .= "<p style='color:#1d4ed8; font-size:12px; margin-top:6px;'>Kedudukan $recalculatedGroupsCount kumpulan telah dikira semula secara tepat berdasarkan keputusan perlawanan sebenar.</p>";
            }
        }
    } catch (\Throwable $e) {
        $extraMsg .= "<p style='color:#b91c1c; font-size:12px; margin-top:6px;'>Nota pengiraan: " . htmlspecialchars($e->getMessage()) . "</p>";
    }

    // 4. Sync 5th place classification bracket for all categories with knockouts
    try {
        if (class_exists(\App\Models\TournamentMatch::class) && method_exists(\App\Models\TournamentMatch::class, 'syncFifthPlaceBracket')) {
            $knockoutCategories = \App\Models\Category::whereHas('matches', function($q) {
                $q->whereIn('stage', ['trophy_knockout', 'cup_knockout']);
            })->get();
            foreach ($knockoutCategories as $kCat) {
                \App\Models\TournamentMatch::syncFifthPlaceBracket($kCat->id);
            }
        }
    } catch (\Throwable $e) {
        // Continue
    }

    return "<div style='font-family:sans-serif; text-align:center; padding:50px; background:#f0fdf4; border:2px solid #86efac; border-radius:20px; max-width:600px; margin:50px auto;'>
        <h2 style='color:#15803d; margin-bottom:10px;'>✅ Semua Cache & Fail Lama Berjaya Dibersihkan!</h2>
        <p style='color:#166534; font-size:14px;'>View cache, route cache, config cache, dan fail bertindih telah dikosongkan.</p>
        $extraMsg
        <div style='margin-top:25px;'>
            <a href='".route('semakan')."' style='background:#15803d; color:white; padding:10px 20px; border-radius:10px; text-decoration:none; font-weight:bold; margin-right:10px;'>Portal Semakan</a>
            <a href='".route('admin.matches.index')."' style='background:#3b82f6; color:white; padding:10px 20px; border-radius:10px; text-decoration:none; font-weight:bold;'>Admin Perlawanan</a>
        </div>
    </div>";
});

// Public Route
Route::get('/semakan', function() {
    return view('pages.semakan');
})->name('semakan');

Route::get('/live-tv', function() {
    if (config('app.event_concluded', env('EVENT_CONCLUDED', true))) {
        return redirect()->route('semakan');
    }
    return view('pages.live-tv');
})->name('live.tv');

// PIN Login Routes
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'authenticate'])->name('login.post');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});

// ─── Admin Routes (protected by PIN) ─────────────────────────────────────────
Route::middleware('pin.auth')->prefix('admin')->name('admin.')->group(function () {
    
    // Check-In (Accessible by both Master and Volunteer)
    Route::get('/semak-masuk', function () {
        return view('admin.checkin.index');
    })->name('checkin.index');

    Route::get('/semak-masuk/papan-pemuka', function () {
        return view('admin.checkin.dashboard');
    })->name('checkin.dashboard');

    // Matches (Phase 3 — Accessible by both)
    Route::get('/perlawanan', function () {
        return view('admin.matches.index');
    })->name('matches.index');

    // Knockout (Phase 4 — Accessible by both)
    Route::get('/knockout', function () {
        return view('admin.knockout.index');
    })->name('knockout.index');

    Route::get('/knockout/{category}', function (App\Models\Category $category) {
        return view('admin.knockout.show', compact('category'));
    })->name('knockout.show');

    // -- MASTER ONLY ROUTES --
    Route::middleware('pin.auth:master')->group(function () {
        // Dashboard
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // Data Import
        Route::get('/import', [ImportController::class, 'index'])->name('import.index');
        Route::post('/import', [ImportController::class, 'store'])->name('import.store');
        Route::post('/import/clear', [ImportController::class, 'clear'])->name('import.clear');

        // Teams CRUD
        Route::get('/pasukan', [TeamController::class, 'index'])->name('teams.index');
        Route::get('/pasukan/tambah', [TeamController::class, 'create'])->name('teams.create');
        Route::post('/pasukan', [TeamController::class, 'store'])->name('teams.store');
        Route::get('/pasukan/{team}', [TeamController::class, 'show'])->name('teams.show');
        Route::get('/pasukan/{team}/kemaskini', [TeamController::class, 'edit'])->name('teams.edit');
        Route::put('/pasukan/{team}', [TeamController::class, 'update'])->name('teams.update');
        Route::delete('/pasukan/{team}', [TeamController::class, 'destroy'])->name('teams.destroy');
        Route::delete('/pasukan-padam-semua', [TeamController::class, 'destroyAll'])->name('teams.destroy_all');

        // Groups
        Route::get('/kumpulan', function () {
            return view('admin.groups.index');
        })->name('groups.index');
        
        Route::get('/kumpulan/{game}/{category}', function ($game, $category) {
            return view('admin.groups.show', compact('game', 'category'));
        })->name('groups.show');
    });
});



