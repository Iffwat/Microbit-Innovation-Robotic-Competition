<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Group;
use App\Models\Team;
use App\Models\TournamentMatch;
use Illuminate\Http\Request;

class EditionController extends Controller
{
    /**
     * Display the Tournament Edition selection hub (Grid / Cards).
     */
    public function index()
    {
        // Gather live statistics for 2026 with graceful fallback
        try {
            $stats2026 = [
                'teams_count'      => Team::count(),
                'categories_count' => Category::count(),
                'matches_count'    => TournamentMatch::count(),
                'groups_count'     => Group::count(),
                'status'           => 'completed', // Tournament concluded
            ];
        } catch (\Throwable $e) {
            $stats2026 = [
                'teams_count'      => 154,
                'categories_count' => 3,
                'matches_count'    => 150,
                'groups_count'     => 13,
                'status'           => 'completed',
            ];
        }

        // Define available editions
        $editions = [
            [
                'year'        => '2026',
                'title'       => 'mIRC 2026',
                'edition'     => 'Edisi Ke-3',
                'status'      => 'concluded',
                'status_label'=> 'Selesai & Arkib Penuh',
                'status_color'=> 'emerald',
                'date'        => 'September 2026',
                'location'    => 'Shah Alam, Selangor',
                'description' => 'Kejohanan rasmi tahun 2026 dengan rekod perlawanan lengkap, kedudukan kumpulan, dan bagan kalah mati.',
                'stats'       => $stats2026,
                'is_current'  => true,
            ],
            [
                'year'        => '2027',
                'title'       => 'mIRC 2027',
                'edition'     => 'Edisi Ke-4',
                'status'      => 'upcoming',
                'status_label'=> 'Akan Datang (Persediaan)',
                'status_color'=> 'purple',
                'date'        => 'Tahun 2027',
                'location'    => 'Akan Diumumkan',
                'description' => 'Persediaan format pertandingan baharu, pendaftaran pasukan sesi hadapan, dan penyediaan jadual.',
                'stats'       => [
                    'teams_count'      => 0,
                    'categories_count' => 3,
                    'matches_count'    => 0,
                    'groups_count'     => 0,
                    'status'           => 'planning',
                ],
                'is_current'  => false,
            ],
            [
                'year'        => '2025',
                'title'       => 'mIRC 2025',
                'edition'     => 'Edisi Ke-2',
                'status'      => 'archive',
                'status_label'=> 'Arkib Sejarah',
                'status_color'=> 'slate',
                'date'        => 'Tahun 2025',
                'location'    => 'Rekod Luar Talian',
                'description' => 'Arkib sejarah kejohanan dan statistik penganjuran edisi terdahulu.',
                'stats'       => null,
                'is_current'  => false,
            ],
        ];

        $currentSelectedYear = session('active_edition', '2026');

        return view('admin.editions.index', compact('editions', 'currentSelectedYear'));
    }

    /**
     * Select an edition and store in session.
     */
    public function select(Request $request)
    {
        $year = $request->input('year', '2026');

        session(['active_edition' => $year]);

        if ($year === '2027') {
            return redirect()->route('admin.editions.prepare', ['year' => '2027'])
                ->with('info', __('Anda kini berada dalam mod persediaan untuk mIRC 2027.'));
        }

        // Default or 2026: redirect to dashboard or checkin based on role
        if (session('auth_role') === 'volunteer') {
            return redirect()->route('admin.checkin.index')
                ->with('success', __('Edisi mIRC :year dipilih. Selamat bertugas!', ['year' => $year]));
        }

        return redirect()->route('admin.dashboard')
            ->with('success', __('Edisi mIRC :year dipilih untuk pengurusan.', ['year' => $year]));
    }

    /**
     * Display the preparation page for future or planned editions (e.g. 2027).
     */
    public function prepare($year)
    {
        session(['active_edition' => $year]);

        return view('admin.editions.prepare', compact('year'));
    }
}
