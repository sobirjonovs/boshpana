<?php

namespace Database\Seeders;

use App\Models\District;
use App\Models\Region;
use Illuminate\Database\Seeder;

class RegionSeeder extends Seeder
{
    public function run(): void
    {
        $regions = [
            ['slug' => 'toshkent-shahri', 'name_uz' => 'Toshkent shahri', 'name_ru' => 'город Ташкент', 'name_en' => 'Tashkent city'],
            ['slug' => 'toshkent-viloyati', 'name_uz' => 'Toshkent viloyati', 'name_ru' => 'Ташкентская область', 'name_en' => 'Tashkent region'],
            ['slug' => 'andijon', 'name_uz' => 'Andijon', 'name_ru' => 'Андижан', 'name_en' => 'Andijan'],
            ['slug' => 'buxoro', 'name_uz' => 'Buxoro', 'name_ru' => 'Бухара', 'name_en' => 'Bukhara'],
            ['slug' => 'fargona', 'name_uz' => "Farg'ona", 'name_ru' => 'Фергана', 'name_en' => 'Fergana'],
            ['slug' => 'jizzax', 'name_uz' => 'Jizzax', 'name_ru' => 'Джизак', 'name_en' => 'Jizzakh'],
            ['slug' => 'xorazm', 'name_uz' => 'Xorazm', 'name_ru' => 'Хорезм', 'name_en' => 'Khorezm'],
            ['slug' => 'namangan', 'name_uz' => 'Namangan', 'name_ru' => 'Наманган', 'name_en' => 'Namangan'],
            ['slug' => 'navoiy', 'name_uz' => 'Navoiy', 'name_ru' => 'Навои', 'name_en' => 'Navoiy'],
            ['slug' => 'qashqadaryo', 'name_uz' => 'Qashqadaryo', 'name_ru' => 'Кашкадарья', 'name_en' => 'Kashkadarya'],
            ['slug' => 'qoraqalpogiston', 'name_uz' => "Qoraqalpog'iston", 'name_ru' => 'Каракалпакстан', 'name_en' => 'Karakalpakstan'],
            ['slug' => 'samarqand', 'name_uz' => 'Samarqand', 'name_ru' => 'Самарканд', 'name_en' => 'Samarkand'],
            ['slug' => 'sirdaryo', 'name_uz' => 'Sirdaryo', 'name_ru' => 'Сырдарья', 'name_en' => 'Syrdarya'],
            ['slug' => 'surxondaryo', 'name_uz' => 'Surxondaryo', 'name_ru' => 'Сурхандарья', 'name_en' => 'Surkhandarya'],
        ];

        $sort = 0;
        foreach ($regions as $data) {
            Region::updateOrCreate(
                ['slug' => $data['slug']],
                $data + ['sort' => $sort++],
            );
        }

        $this->seedTashkentCityDistricts();
        $this->seedTashkentRegionDistricts();
    }

    private function seedTashkentCityDistricts(): void
    {
        $region = Region::where('slug', 'toshkent-shahri')->firstOrFail();

        // has_metro: Chilonzor, Mirobod, Mirzo Ulug'bek, Olmazor, Shayxontohur,
        // Yakkasaroy, Yashnobod, Yunusobod, Sergeli
        $districts = [
            ['slug' => 'bektemir', 'name_uz' => 'Bektemir', 'name_ru' => 'Бектемир', 'name_en' => 'Bektemir', 'has_metro' => false],
            ['slug' => 'chilonzor', 'name_uz' => 'Chilonzor', 'name_ru' => 'Чиланзар', 'name_en' => 'Chilonzor', 'has_metro' => true],
            ['slug' => 'mirobod', 'name_uz' => 'Mirobod', 'name_ru' => 'Мирабад', 'name_en' => 'Mirobod', 'has_metro' => true],
            ['slug' => 'mirzo-ulugbek', 'name_uz' => "Mirzo Ulug'bek", 'name_ru' => 'Мирзо-Улугбек', 'name_en' => 'Mirzo Ulugbek', 'has_metro' => true],
            ['slug' => 'sergeli', 'name_uz' => 'Sergeli', 'name_ru' => 'Сергели', 'name_en' => 'Sergeli', 'has_metro' => true],
            ['slug' => 'olmazor', 'name_uz' => 'Olmazor', 'name_ru' => 'Алмазар', 'name_en' => 'Olmazor', 'has_metro' => true],
            ['slug' => 'uchtepa', 'name_uz' => 'Uchtepa', 'name_ru' => 'Учтепа', 'name_en' => 'Uchtepa', 'has_metro' => false],
            ['slug' => 'shayxontohur', 'name_uz' => 'Shayxontohur', 'name_ru' => 'Шайхантахур', 'name_en' => 'Shaykhontohur', 'has_metro' => true],
            ['slug' => 'yakkasaroy', 'name_uz' => 'Yakkasaroy', 'name_ru' => 'Яккасарай', 'name_en' => 'Yakkasaroy', 'has_metro' => true],
            ['slug' => 'yashnobod', 'name_uz' => 'Yashnobod', 'name_ru' => 'Яшнабад', 'name_en' => 'Yashnobod', 'has_metro' => true],
            ['slug' => 'yunusobod', 'name_uz' => 'Yunusobod', 'name_ru' => 'Юнусабад', 'name_en' => 'Yunusobod', 'has_metro' => true],
            ['slug' => 'yangihayot', 'name_uz' => 'Yangihayot', 'name_ru' => 'Янгихаёт', 'name_en' => 'Yangihayot', 'has_metro' => false],
        ];

        $this->insertDistricts($region, $districts);
    }

    private function seedTashkentRegionDistricts(): void
    {
        $region = Region::where('slug', 'toshkent-viloyati')->firstOrFail();

        $districts = [
            ['slug' => 'chirchiq', 'name_uz' => 'Chirchiq', 'name_ru' => 'Чирчик', 'name_en' => 'Chirchik', 'has_metro' => false],
            ['slug' => 'angren', 'name_uz' => 'Angren', 'name_ru' => 'Ангрен', 'name_en' => 'Angren', 'has_metro' => false],
            ['slug' => 'bekobod', 'name_uz' => 'Bekobod', 'name_ru' => 'Бекабад', 'name_en' => 'Bekobod', 'has_metro' => false],
            ['slug' => 'yangiyol', 'name_uz' => "Yangiyo'l", 'name_ru' => 'Янгиюль', 'name_en' => 'Yangiyul', 'has_metro' => false],
            ['slug' => 'nurafshon', 'name_uz' => 'Nurafshon', 'name_ru' => 'Нурафшан', 'name_en' => 'Nurafshon', 'has_metro' => false],
        ];

        $this->insertDistricts($region, $districts);
    }

    /**
     * @param  array<int, array<string, mixed>>  $districts
     */
    private function insertDistricts(Region $region, array $districts): void
    {
        $sort = 0;
        foreach ($districts as $data) {
            District::updateOrCreate(
                ['region_id' => $region->id, 'slug' => $data['slug']],
                $data + ['region_id' => $region->id, 'sort' => $sort++],
            );
        }
    }
}
