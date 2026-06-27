<?php

namespace Database\Seeders;

use App\Enums\SourceType;
use App\Models\Source;
use Illuminate\Database\Seeder;

class SourceSeeder extends Seeder
{
    public function run(): void
    {
        $sources = [
            [
                'slug' => 'olx',
                'name' => 'OLX.uz',
                'type' => SourceType::Marketplace,
                'base_url' => 'https://www.olx.uz',
                'is_active' => true,
                // Parsed from the embedded window.__PRERENDERED_STATE__ JSON.
                'config' => [
                    'list_url' => 'https://www.olx.uz/nedvizhimost/kvartiry/arenda-dolgosrochnaya/tashkent/?search%5Border%5D=created_at:desc',
                    'uzs_per_usd' => 12950,
                ],
            ],
            [
                'slug' => 'birbir',
                'name' => 'Birbir.uz',
                'type' => SourceType::Marketplace,
                'base_url' => 'https://birbir.uz',
                'is_active' => true,
                // Via the open frontoffice API (auth/anonymous -> set region -> offer/feed).
                'config' => [
                    'api_base' => 'https://api.birbir.uz/api/frontoffice/1.3.5.0',
                    'region_id' => 1000009,                            // Toshkent shahri
                    'category_uri' => 'kochmas-mulk/ijara/kvartiralar', // Ko'chmas mulk > Ijara > Kvartiralar
                    'per_page' => 30,
                    'max_pages' => 4,
                    'enrich' => true,
                    'uzs_per_usd' => 12950,
                ],
            ],
            [
                'slug' => 'uybor',
                'name' => 'Uybor.uz',
                'type' => SourceType::Marketplace,
                'base_url' => 'https://uybor.uz',
                'is_active' => true,
                'config' => [
                    'search_path' => '/listings?category=arenda-kvartir&region=tashkent',
                    'item_selector' => '.listing-card',
                ],
            ],
            [
                'slug' => 'joymee',
                'name' => 'Joymee.uz',
                'type' => SourceType::Marketplace,
                'base_url' => 'https://joymee.uz',
                'is_active' => true,
                'config' => [
                    'search_path' => '/arenda/tashkent',
                    'item_selector' => '.card',
                ],
            ],
            [
                'slug' => 'tg-toshkent-ijara',
                'name' => '@toshkent_ijara',
                'type' => SourceType::TelegramChannel,
                'base_url' => 'https://t.me/toshkent_ijara',
                'is_active' => true,
                'config' => [
                    'handle' => '@toshkent_ijara',
                    'chat_hint' => 'toshkent_ijara',
                ],
            ],
            [
                'slug' => 'tg-toshkent-kvartira-ijara',
                'name' => 'Toshkent kvartira ijara',
                'type' => SourceType::TelegramGroup,
                'base_url' => 'https://t.me/joinchat/toshkent_kvartira_ijara',
                'is_active' => true,
                'config' => [
                    'handle' => 'Toshkent kvartira ijara',
                    'chat_hint' => '-1001234567890',
                ],
            ],
        ];

        foreach ($sources as $data) {
            Source::updateOrCreate(['slug' => $data['slug']], $data);
        }
    }
}
