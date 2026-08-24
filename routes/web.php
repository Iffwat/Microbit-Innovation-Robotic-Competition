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

// Public Route
Route::get('/semakan', function() {
    return view('pages.semakan');
})->name('semakan');

Route::get('/live-tv', function() {
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



