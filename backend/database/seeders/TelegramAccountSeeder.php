<?php

namespace Database\Seeders;

use App\Models\TelegramAccount;
use Illuminate\Database\Seeder;

class TelegramAccountSeeder extends Seeder
{
    public function run(): void
    {
        TelegramAccount::updateOrCreate(
            ['label' => 'Simulyatsiya akkaunti'],
            [
                'phone' => null,
                'username' => '@boshpana_sim',
                'session' => null,
                'is_active' => true,
                'is_simulation' => true,
                'daily_limit' => 50,
                'sent_today' => 0,
            ],
        );
    }
}
