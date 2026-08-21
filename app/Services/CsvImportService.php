<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Team;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CsvImportService
{
    // Mapping column index → field name (simplified format)
    // Row 0 = headers, data starts at Row 1
    private array $columnMap = [
        0 => 'school_name',
        1 => 'category_name',
        2 => 'team_name',
        3 => 'player_1',
        4 => 'player_2',
        5 => 'player_3',
        6 => 'mentor_name',
        7 => 'mentor_email',
    ];

    private array $categoryMap = [
        'U12'  => 'u12',
        'U15'  => 'u15',
        'U20'  => 'u20',
        'U21'  => 'u20',   // fallback
        'PPKI' => 'ppki',
    ];

    public function import(UploadedFile $file, string $gameType = 'isobot'): array
    {
        $results = ['imported' => 0, 'skipped' => 0, 'errors' => []];

        $handle = fopen($file->getRealPath(), 'r');
        if (!$handle) {
            $results['errors'][] = 'Tidak dapat membuka fail CSV.';
            return $results;
        }

        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        $rowNumber = 0;

        DB::beginTransaction();

        try {
            while (($row = fgetcsv($handle, 0, ',')) !== false) {
                $rowNumber++;

                if ($rowNumber === 1) continue; // skip headers
                if (empty(array_filter($row))) continue;

                $importResult = $this->importRow($row, $rowNumber, $gameType);

                if ($importResult === true) {
                    $results['imported']++;
                } elseif ($importResult === false) {
                    $results['skipped']++;
                } else {
                    $results['errors'][] = "Baris $rowNumber: $importResult";
                    $results['skipped']++;
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('CSV Import error: ' . $e->getMessage());
            $results['errors'][] = 'Ralat sistem: ' . $e->getMessage();
        } finally {
            fclose($handle);
        }

        return $results;
    }

    public function preview(UploadedFile $file, int $limit = 10): array
    {
        $rows   = [];
        $handle = fopen($file->getRealPath(), 'r');
        if (!$handle) return $rows;

        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") rewind($handle);

        $rowNumber = 0;
        while (($row = fgetcsv($handle, 0, ',')) !== false) {
            $rowNumber++;
            if ($rowNumber === 1) continue;
            if ($rowNumber > $limit + 1) break;
            if (empty(array_filter($row))) continue;

            $rows[] = $this->parseRow($row);
        }

        fclose($handle);
        return $rows;
    }

    private function importRow(array $row, int $rowNumber, string $gameType): bool|string
    {
        $data = $this->parseRow($row);

        if (empty($data['team_name'])) {
            return 'Nama pasukan kosong.';
        }

        if (empty($data['category_name'])) {
            return 'Kategori peserta kosong.';
        }

        $categoryRaw = strtoupper(trim($data['category_name']));
        $categorySlug = null;

        if ($gameType === 'isobot') {
            // Isobot Soccer Categories
            if (str_contains($categoryRaw, '[1]') || str_contains($categoryRaw, 'U12')) {
                $categorySlug = 'u12';
            } elseif (str_contains($categoryRaw, '[2]') || str_contains($categoryRaw, 'PPKI')) {
                $categorySlug = 'ppki';
            } elseif (str_contains($categoryRaw, '[3]') || str_contains($categoryRaw, 'U15')) {
                $categorySlug = 'u15';
            } elseif (str_contains($categoryRaw, '[4]') || str_contains($categoryRaw, 'U20') || str_contains($categoryRaw, 'U21')) {
                $categorySlug = 'u20';
            }
        } else {
            // Drone Categories (Sky Soccer / Obstacle)
            // U12 & PPKI = [1] or [2]
            // U15 & U20  = [3] or [4]
            if (str_contains($categoryRaw, '[1]') || str_contains($categoryRaw, '[2]') || str_contains($categoryRaw, 'U12') || str_contains($categoryRaw, 'PPKI')) {
                $categorySlug = 'u12_ppki';
            } elseif (str_contains($categoryRaw, '[3]') || str_contains($categoryRaw, '[4]') || str_contains($categoryRaw, 'U15') || str_contains($categoryRaw, 'U20') || str_contains($categoryRaw, 'U21')) {
                $categorySlug = 'u15_u20';
            }
        }

        if (!$categorySlug) {
            return "Kategori tidak dikenali: '{$data['category_name']}'.";
        }

        $category = Category::where('slug', $categorySlug)->first();
        if (!$category) {
            return "Kategori '$categorySlug' tidak wujud dalam sistem.";
        }

        $exists = Team::where('category_id', $category->id)
                      ->where('team_name', trim($data['team_name']))
                      ->where('game_type', $gameType)
                      ->exists();
        if ($exists) {
            return false;
        }

        Team::create([
            'category_id'  => $category->id,
            'game_type'    => $gameType,
            'team_name'    => trim($data['team_name']),
            'school_name'  => trim($data['school_name'] ?? ''),
            'player_1'     => trim($data['player_1'] ?? ''),
            'player_2'     => trim($data['player_2'] ?? ''),
            'player_3'     => $gameType === 'obstacle' ? '' : trim($data['player_3'] ?? ''),
            'mentor_name'  => trim($data['mentor_name'] ?? ''),
            'mentor_email' => trim($data['mentor_email'] ?? ''),
            'status'       => 'registered',
        ]);

        return true;
    }

    private function parseRow(array $row): array
    {
        $data = [];
        foreach ($this->columnMap as $index => $field) {
            $data[$field] = $row[$index] ?? '';
        }
        return $data;
    }

    public function countRows(UploadedFile $file): int
    {
        $count  = 0;
        $handle = fopen($file->getRealPath(), 'r');
        if (!$handle) return 0;

        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") rewind($handle);

        $rowNumber = 0;
        while (($row = fgetcsv($handle, 0, ',')) !== false) {
            $rowNumber++;
            if ($rowNumber === 1) continue;
            if (!empty(array_filter($row))) $count++;
        }

        fclose($handle);
        return $count;
    }
}
