<?php

namespace Modules\Taxonomy\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Modules\Taxonomy\Models\Color;
use Modules\Taxonomy\Models\FabricType;
use Modules\Taxonomy\Models\Material;
use Modules\Taxonomy\Models\TaxonomyTerm;
use Modules\Taxonomy\Models\Unit;

class TaxonomyDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(GovernorateSeeder::class);

        // Minimal starter lists — the canonical fabric taxonomy and its
        // governance are an Open Decision (#7); these are Implementation
        // Assumptions and become admin-editable in Phase 9.
        $this->seedTerms(FabricType::class, [
            ['قطن', 'Cotton'],
            ['بوليستر', 'Polyester'],
            ['حرير', 'Silk'],
            ['كتان', 'Linen'],
            ['صوف', 'Wool'],
            ['فسكوز', 'Viscose'],
        ]);

        $this->seedTerms(Material::class, [
            ['طبيعي', 'Natural'],
            ['صناعي', 'Synthetic'],
            ['مخلوط', 'Blended'],
        ]);

        $this->seedTerms(Unit::class, [
            ['متر', 'Meter'],
            ['ياردة', 'Yard'],
            ['كيلوجرام', 'Kilogram'],
            ['طاقة', 'Roll'],
        ]);

        foreach ([
            ['أبيض', 'White', '#FFFFFF'],
            ['أسود', 'Black', '#000000'],
            ['أحمر', 'Red', '#E53935'],
            ['أزرق', 'Blue', '#1E88E5'],
            ['أخضر', 'Green', '#43A047'],
            ['أصفر', 'Yellow', '#FDD835'],
            ['بيج', 'Beige', '#D7CCC8'],
        ] as [$nameAr, $nameEn, $hex]) {
            Color::query()->updateOrCreate(
                ['slug' => Str::slug($nameEn)],
                ['name_ar' => $nameAr, 'name_en' => $nameEn, 'hex' => $hex],
            );
        }
    }

    /**
     * @param  class-string<TaxonomyTerm>  $model
     * @param  list<array{0: string, 1: string}>  $terms
     */
    private function seedTerms(string $model, array $terms): void
    {
        foreach ($terms as [$nameAr, $nameEn]) {
            $model::query()->updateOrCreate(
                ['slug' => Str::slug($nameEn)],
                ['name_ar' => $nameAr, 'name_en' => $nameEn],
            );
        }
    }
}
