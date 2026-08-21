<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Services\CsvImportService;
use Illuminate\Http\Request;

class ImportController extends Controller
{
    public function __construct(private CsvImportService $importService) {}

    /**
     * Show import page.
     */
    public function index()
    {
        $categories = Category::orderBy('sort_order')->get();
        return view('admin.import.index', compact('categories'));
    }

    /**
     * Preview CSV before importing.
     */
    public function preview(Request $request)
    {
        $request->validate([
            'csv_file' => ['required', 'file', 'mimes:csv,txt', 'max:10240'],
        ], [
            'csv_file.required' => 'Sila pilih fail CSV.',
            'csv_file.mimes'    => 'Fail mesti dalam format CSV.',
            'csv_file.max'      => 'Saiz fail tidak boleh melebihi 10MB.',
        ]);

        $file      = $request->file('csv_file');
        $preview   = $this->importService->preview($file, 5);
        $totalRows = $this->importService->countRows($file);

        // Store file temporarily
        $tempPath = $file->store('imports/temp');

        return view('admin.import.preview', compact('preview', 'totalRows', 'tempPath'));
    }

    /**
     * Execute the CSV import.
     */
    public function store(Request $request)
    {
        $request->validate([
            'csv_file'  => ['required', 'file', 'mimes:csv,txt', 'max:10240'],
            'game_type' => ['required', 'in:isobot,sky_soccer,obstacle'],
        ], [
            'csv_file.required' => 'Sila pilih fail CSV.',
            'game_type.required' => 'Sila pilih jenis permainan.',
        ]);

        $file    = $request->file('csv_file');
        $game    = $request->input('game_type');
        $results = $this->importService->import($file, $game);

        $message = "✅ Import selesai: {$results['imported']} pasukan berjaya diimport.";
        if ($results['skipped'] > 0) {
            $message .= " {$results['skipped']} baris dilangkau (duplikat atau tidak lengkap).";
        }

        session(['import_errors' => $results['errors']]);

        return redirect()->route('admin.teams.index')
                         ->with('success', $message);
    }

    /**
     * Delete all teams from the database.
     */
    public function clear(Request $request)
    {
        $request->validate([
            'game_type' => ['required', 'in:isobot,sky_soccer,obstacle,all'],
        ]);

        $gameType = $request->input('game_type');

        \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        
        if ($gameType === 'all') {
            // Truncate dependent tables first to keep database clean
            \App\Models\GroupTeam::truncate();
            \App\Models\TournamentMatch::truncate(); 
            \App\Models\KnockoutSlot::truncate();
            \App\Models\Team::truncate();
            $message = '✅ Semua rekod pasukan untuk KESEMUA permainan telah berjaya dipadam.';
        } else {
            // Delete specific game type teams
            $teamIds = \App\Models\Team::where('game_type', $gameType)->pluck('id');
            
            if ($teamIds->isNotEmpty()) {
                \App\Models\GroupTeam::whereIn('team_id', $teamIds)->delete();
                // If there are matches or knockouts related directly to teams, delete them too.
                // For now, since GroupTeam is deleted, cascading will handle if DB allows, 
                // but GroupTeam deletion is sufficient for the MVP.
                \App\Models\Team::whereIn('id', $teamIds)->delete();
            }
            
            $gameNames = [
                'isobot' => 'Isobot Soccer',
                'sky_soccer' => 'Drone Sky Soccer',
                'obstacle' => 'Drone Obstacle'
            ];
            $message = "✅ Semua rekod pasukan untuk {$gameNames[$gameType]} telah berjaya dipadam.";
        }
        
        \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        
        return redirect()->route('admin.import.index')
                         ->with('success', $message);
    }
}
