<?php

namespace Database\Seeders;

use App\Enums\Condition;
use App\Enums\Gender;
use App\Enums\ListingStatus;
use App\Enums\MaritalStatus;
use App\Enums\SearchMode;
use App\Models\District;
use App\Models\Listing;
use App\Models\ListingOwner;
use App\Models\Region;
use App\Models\Source;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class DemoListingSeeder extends Seeder
{
    public function run(): void
    {
        $region = Region::where('slug', 'toshkent-shahri')->firstOrFail();

        /** @var array<string, District> $districts */
        $districts = District::where('region_id', $region->id)->get()->keyBy('slug');

        /** @var array<string, Source> $sources */
        $sources = Source::all()->keyBy('slug');

        $sourceSlugs = ['olx', 'birbir', 'uybor', 'joymee', 'tg-toshkent-ijara', 'tg-toshkent-kvartira-ijara'];
        $districtSlugs = $districts->keys()->all();

        $ownerNames = [
            'Akmal aka', 'Dilnoza opa', 'Sardor Karimov', 'Gulnora Yusupova', 'Bobur aka',
            'Nilufar opa', 'Jasur Toshmatov', 'Madina Rahimova', 'Otabek aka', 'Shahnoza opa',
            'Aziz Qodirov', 'Feruza opa',
        ];

        $metroStations = [
            'chilonzor' => 'Chilonzor', 'mirobod' => 'Oybek', 'mirzo-ulugbek' => 'Buyuk Ipak Yo\'li',
            'olmazor' => 'Olmazor', 'shayxontohur' => 'G\'afur G\'ulom', 'yakkasaroy' => 'Kosmonavtlar',
            'yashnobod' => 'Mashinasozlar', 'yunusobod' => 'Yunus Rajabiy', 'sergeli' => 'Sergeli',
        ];

        $conditions = [Condition::Average, Condition::Excellent];
        $genders = [Gender::Any, Gender::Any, Gender::Female, Gender::Male];
        $maritals = [MaritalStatus::Any, MaritalStatus::Single, MaritalStatus::Single, MaritalStatus::Married];

        // Single prices and range-friendly (round) prices mixed in.
        $prices = [150, 200, 250, 280, 300, 350, 400, 450, 500, 550, 600, 700, 750, 800, 900, 220, 330, 480];

        $roomWordUz = [1 => 'bir', 2 => 'ikki', 3 => 'uch', 4 => "to'rt", 5 => 'besh'];

        for ($i = 0; $i < 36; $i++) {
            $sourceSlug = $sourceSlugs[$i % count($sourceSlugs)];
            $source = $sources[$sourceSlug];

            $districtSlug = $districtSlugs[$i % count($districtSlugs)];
            $district = $districts[$districtSlug];

            $rooms = ($i % 5) + 1;
            $price = $prices[$i % count($prices)];
            $area = 30 + (($i * 7) % 91); // 30..120
            $condition = $conditions[$i % 2];
            $hasFurniture = ($i % 3) !== 0;
            $hasCommission = ($i % 4) === 0;
            $genderPref = $genders[$i % count($genders)];
            $maritalPref = $maritals[$i % count($maritals)];

            $isPartnership = ($i % 4) === 1;
            $mode = $isPartnership ? SearchMode::Partnership : SearchMode::Solo;
            $partnersNeeded = $isPartnership ? (2 + ($i % 3)) : null;

            $nearMetro = $district->has_metro && (($i % 2) === 0);
            $metroStation = $nearMetro ? ($metroStations[$districtSlug] ?? null) : null;

            $amenities = ['internet'];
            if ($hasFurniture) {
                $amenities[] = 'mebel';
                $amenities[] = 'konditsioner';
            }
            if (($i % 2) === 0) {
                $amenities[] = 'kir mashina';
            }
            if (($i % 3) === 0) {
                $amenities[] = 'muzlatgich';
            }

            $ownerName = $ownerNames[$i % count($ownerNames)];
            $ownerUsername = '@owner_'.($i + 1);
            $ownerPhone = sprintf('+99890%07d', 1000000 + $i * 37);

            $owner = ListingOwner::firstOrCreate(
                ['telegram_username' => $ownerUsername],
                [
                    'name' => $ownerName,
                    'phone' => $ownerPhone,
                    'is_realtor' => $hasCommission,
                ],
            );

            $roomsWord = $roomWordUz[$rooms];
            $genderNote = match ($genderPref) {
                Gender::Female => ' Faqat qizlarga.',
                Gender::Male => ' Faqat yigitlarga.',
                default => '',
            };
            $maritalNote = $maritalPref === MaritalStatus::Married ? ' Oilaga beriladi.' : '';
            $partnerNote = $isPartnership ? " Sherikchilik asosida ({$partnersNeeded} kishi)." : '';
            $metroNote = $nearMetro ? " {$metroStation} metrosiga yaqin." : '';
            $commissionNote = $hasCommission ? ' Vositachilik haqi bor.' : ' Vositachisiz, to\'g\'ridan-to\'g\'ri uy egasidan.';
            $furnitureNote = $hasFurniture ? ' Mebel va konditsioner bilan.' : ' Mebelsiz.';
            $condNote = $condition === Condition::Excellent ? "Yevro ta'mirli, zo'r holatda." : "O'rtacha holatda, toza.";

            $title = "{$district->name_uz}da {$rooms} xonali kvartira ijaraga";
            $description = "{$district->name_uz} tumanida {$roomsWord} xonali, {$area} m² kvartira uzoq muddatga ijaraga beriladi. "
                ."{$condNote}{$furnitureNote}{$metroNote}{$commissionNote}{$genderNote}{$maritalNote}{$partnerNote} "
                .'Narxi oyiga $'.$price.'. Murojaat uchun qo\'ng\'iroq qiling.';

            $images = [
                "https://picsum.photos/seed/boshpana{$i}a/800/600",
                "https://picsum.photos/seed/boshpana{$i}b/800/600",
            ];

            $postedAt = Carbon::now()->subMinutes((($i * 37) % 1380) + 5); // within last ~24h

            $aiAttributes = [
                'region' => $region->slug,
                'district' => $district->slug,
                'price' => $price,
                'currency' => 'USD',
                'rooms' => $rooms,
                'area' => $area,
                'condition' => $condition->value,
                'has_furniture' => $hasFurniture,
                'has_commission' => $hasCommission,
                'near_metro' => $nearMetro,
                'metro_station' => $metroStation,
                'gender_pref' => $genderPref->value,
                'marital_pref' => $maritalPref->value,
                'mode' => $mode->value,
                'partners_needed' => $partnersNeeded,
                'amenities' => $amenities,
            ];

            $aiSummary = "{$district->name_uz}, {$rooms} xona, {$area} m², \${$price}/oy. "
                .($condition === Condition::Excellent ? 'Yevro ta\'mir. ' : 'O\'rtacha holat. ')
                .($hasFurniture ? 'Mebelli. ' : 'Mebelsiz. ')
                .($nearMetro ? 'Metroga yaqin.' : '');

            Listing::firstOrCreate(
                ['source_id' => $source->id, 'external_id' => $sourceSlug.'-demo-'.($i + 1)],
                [
                    'listing_owner_id' => $owner->id,
                    'url' => rtrim((string) $source->base_url, '/').'/listing/'.($i + 1),
                    'source_ref' => $source->type->value === 'marketplace' ? null : ($source->config['handle'] ?? null),
                    'title' => $title,
                    'description' => $description,
                    'images' => $images,
                    'price' => $price,
                    'currency' => 'USD',
                    'region_id' => $region->id,
                    'district_id' => $district->id,
                    'address' => "{$district->name_uz} tumani, Toshkent",
                    'near_metro' => $nearMetro,
                    'metro_station' => $metroStation,
                    'rooms' => $rooms,
                    'area' => $area,
                    'floor' => (($i * 3) % 9) + 1,
                    'total_floors' => 9,
                    'condition' => $condition,
                    'has_furniture' => $hasFurniture,
                    'has_commission' => $hasCommission,
                    'amenities' => $amenities,
                    'gender_pref' => $genderPref,
                    'marital_pref' => $maritalPref,
                    'mode' => $mode,
                    'partners_needed' => $partnersNeeded,
                    'contact' => ['telegram' => $ownerUsername, 'phone' => $ownerPhone],
                    'posted_at' => $postedAt,
                    'status' => ListingStatus::Active,
                    'ai_analyzed' => true,
                    'ai_summary' => $aiSummary,
                    'ai_attributes' => $aiAttributes,
                    'ai_confidence' => 0.8,
                    'analyzed_at' => $postedAt,
                ],
            );
        }
    }
}
