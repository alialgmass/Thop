<?php

namespace Modules\Verification\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Verification\Models\DocumentType;

/**
 * The document types a business may submit. "Which documents are mandatory" is
 * an Open Decision (#5); `is_required` is a column so the answer can change
 * without a deploy (MNT-NFR-02). Both are marked required as the current
 * Implementation Assumption.
 */
class DocumentTypeSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['commercial_register', 'السجل التجاري', 'Commercial register', true],
            ['tax_card', 'البطاقة الضريبية', 'Tax card', true],
        ] as [$slug, $nameAr, $nameEn, $required]) {
            DocumentType::query()->updateOrCreate(
                ['slug' => $slug],
                ['name_ar' => $nameAr, 'name_en' => $nameEn, 'is_required' => $required],
            );
        }
    }
}
