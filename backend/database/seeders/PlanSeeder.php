<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        // Single paid tariff: 12 000 so'm for 3 days. There is no free plan.
        $plan = [
            'slug' => 'standart',
            'name_uz' => '3 kunlik tarif',
            'name_ru' => 'Тариф на 3 дня',
            'name_en' => '3-day plan',
            'price' => 12000,
            'currency' => 'UZS',
            'period_days' => 3,
            'searches_limit' => null, // unlimited during the 3 days
            'features' => [
                '3 kun davomida cheksiz qidiruv',
                'Barcha filtrlar',
                'AI muzokara',
                'Yangi e\'lonlar haqida bildirishnoma',
            ],
            'is_active' => true,
            'sort' => 0,
        ];

        // Drop any previous tariffs (free / premium / pro …) so only this one remains.
        Plan::where('slug', '!=', $plan['slug'])->delete();

        Plan::updateOrCreate(['slug' => $plan['slug']], $plan);
    }
}
