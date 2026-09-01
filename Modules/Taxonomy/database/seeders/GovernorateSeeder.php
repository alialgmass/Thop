<?php

namespace Modules\Taxonomy\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Taxonomy\Models\Governorate;

/**
 * The 27 Egyptian governorates (BR-ACC-03 — governorate is a controlled
 * reference, not free text). Bilingual names; slug is the stable key.
 */
class GovernorateSeeder extends Seeder
{
    public function run(): void
    {
        $governorates = [
            ['cairo', 'القاهرة', 'Cairo'],
            ['giza', 'الجيزة', 'Giza'],
            ['alexandria', 'الإسكندرية', 'Alexandria'],
            ['dakahlia', 'الدقهلية', 'Dakahlia'],
            ['red-sea', 'البحر الأحمر', 'Red Sea'],
            ['beheira', 'البحيرة', 'Beheira'],
            ['fayoum', 'الفيوم', 'Fayoum'],
            ['gharbia', 'الغربية', 'Gharbia'],
            ['ismailia', 'الإسماعيلية', 'Ismailia'],
            ['menofia', 'المنوفية', 'Menofia'],
            ['minya', 'المنيا', 'Minya'],
            ['qalyubia', 'القليوبية', 'Qalyubia'],
            ['new-valley', 'الوادي الجديد', 'New Valley'],
            ['suez', 'السويس', 'Suez'],
            ['aswan', 'أسوان', 'Aswan'],
            ['assiut', 'أسيوط', 'Assiut'],
            ['beni-suef', 'بني سويف', 'Beni Suef'],
            ['port-said', 'بورسعيد', 'Port Said'],
            ['damietta', 'دمياط', 'Damietta'],
            ['sharqia', 'الشرقية', 'Sharqia'],
            ['south-sinai', 'جنوب سيناء', 'South Sinai'],
            ['kafr-el-sheikh', 'كفر الشيخ', 'Kafr El Sheikh'],
            ['matrouh', 'مطروح', 'Matrouh'],
            ['luxor', 'الأقصر', 'Luxor'],
            ['qena', 'قنا', 'Qena'],
            ['north-sinai', 'شمال سيناء', 'North Sinai'],
            ['sohag', 'سوهاج', 'Sohag'],
        ];

        foreach ($governorates as [$slug, $nameAr, $nameEn]) {
            Governorate::query()->updateOrCreate(
                ['slug' => $slug],
                ['name_ar' => $nameAr, 'name_en' => $nameEn],
            );
        }
    }
}
