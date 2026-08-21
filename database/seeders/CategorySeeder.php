<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name'           => 'U12',
                'slug'           => 'u12',
                'format'         => 'group_knockout',
                'teams_per_group' => 5,
                'fields_count'   => 8,
                'is_active'      => true,
                'sort_order'     => 1,
            ],
            [
                'name'           => 'U15',
                'slug'           => 'u15',
                'format'         => 'group_knockout',
                'teams_per_group' => 5,
                'fields_count'   => 4,
                'is_active'      => true,
                'sort_order'     => 2,
            ],
            [
                'name'           => 'U20',
                'slug'           => 'u20',
                'format'         => 'group_knockout',
                'teams_per_group' => 5,
                'fields_count'   => 4,
                'is_active'      => true,
                'sort_order'     => 3,
            ],
            [
                'name'           => 'PPKI',
                'slug'           => 'ppki',
                'format'         => 'round_robin_only',
                'teams_per_group' => 7,
                'fields_count'   => 1,
                'is_active'      => true,
                'sort_order'     => 4,
            ],
            [
                'name'           => 'U12 & PPKI',
                'slug'           => 'u12_ppki',
                'format'         => 'group_knockout',
                'teams_per_group' => 5,
                'fields_count'   => 2,
                'is_active'      => true,
                'sort_order'     => 5,
            ],
            [
                'name'           => 'U15 & U20',
                'slug'           => 'u15_u20',
                'format'         => 'group_knockout',
                'teams_per_group' => 5,
                'fields_count'   => 2,
                'is_active'      => true,
                'sort_order'     => 6,
            ],
        ];

        foreach ($categories as $category) {
            Category::updateOrCreate(
                ['slug' => $category['slug']],
                $category
            );
        }

        $this->command->info('✅ 4 kategori berjaya ditambah: U12, U15, U20, PPKI');
    }
}
