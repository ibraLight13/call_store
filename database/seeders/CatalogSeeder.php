<?php

namespace Database\Seeders;

use App\Domain\Catalog\Models\Color;
use App\Domain\Catalog\Models\Medium;
use App\Domain\Catalog\Models\Script;
use App\Domain\Catalog\Models\Tag;
use App\Domain\Catalog\Models\Theme;
use Illuminate\Database\Seeder;

final class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->scripts() as $i => [$slug, $ar, $en]) {
            Script::query()->updateOrCreate(['slug' => $slug], ['name_ar' => $ar, 'name_en' => $en, 'sort' => $i]);
        }

        foreach ($this->mediums() as $i => [$slug, $ar, $en]) {
            Medium::query()->updateOrCreate(['slug' => $slug], ['name_ar' => $ar, 'name_en' => $en, 'sort' => $i]);
        }

        foreach ($this->themes() as $i => [$slug, $ar, $en]) {
            Theme::query()->updateOrCreate(['slug' => $slug], ['name_ar' => $ar, 'name_en' => $en, 'sort' => $i]);
        }

        foreach ($this->colors() as $i => [$slug, $hex, $ar, $en]) {
            Color::query()->updateOrCreate(['slug' => $slug], ['hex' => $hex, 'name_ar' => $ar, 'name_en' => $en, 'sort' => $i]);
        }

        foreach ([['gift', 'هدية', 'Gift'], ['home', 'ديكور منزلي', 'Home decor'], ['quotes', 'عبارات', 'Quotes']] as [$slug, $ar, $en]) {
            Tag::query()->updateOrCreate(['slug' => $slug], ['name_ar' => $ar, 'name_en' => $en]);
        }
    }

    /** @return list<array{string, string, string}> */
    private function scripts(): array
    {
        return [
            ['thuluth', 'ثلث', 'Thuluth'],
            ['diwani', 'ديواني', 'Diwani'],
            ['kufic', 'كوفي', 'Kufic'],
            ['naskh', 'نسخ', 'Naskh'],
            ['ruqah', 'رقعة', 'Ruqʿah'],
            ['nastaliq', 'نستعليق', 'Nastaliq'],
        ];
    }

    /** @return list<array{string, string, string}> */
    private function mediums(): array
    {
        return [
            ['cotton-paper', 'ورق قطني', 'Cotton paper'],
            ['canvas', 'قماش (كانفاس)', 'Canvas'],
            ['wood', 'خشب', 'Wood'],
            ['paper-gold', 'ورق وذهب', 'Paper & gold'],
        ];
    }

    /** @return list<array{string, string, string}> */
    private function themes(): array
    {
        return [
            ['quranic', 'قرآني', 'Quranic'],
            ['names', 'أسماء', 'Names'],
            ['wedding', 'زفاف', 'Wedding'],
            ['decor', 'ديكور', 'Decor'],
            ['quotes', 'عبارات', 'Quotes'],
        ];
    }

    /** @return list<array{string, string, string, string}> */
    private function colors(): array
    {
        return [
            ['ink-black', '#1c1a17', 'أسود حبري', 'Ink black'],
            ['teal', '#0f7b6c', 'أخضر مزرقّ', 'Teal'],
            ['gold', '#b8893a', 'ذهبي', 'Gold'],
            ['maroon', '#8a2e22', 'خمري', 'Maroon'],
            ['navy', '#22406b', 'كحلي', 'Navy'],
            ['purple', '#46356e', 'بنفسجي', 'Purple'],
            ['white', '#ffffff', 'أبيض', 'White'],
            ['olive', '#33402a', 'زيتوني', 'Olive'],
        ];
    }
}
