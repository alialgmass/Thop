<?php

namespace Modules\Taxonomy\Actions;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Admin\Enums\AuditAction;
use Modules\Admin\Models\AuditLog;
use Modules\Taxonomy\Models\Color;
use Modules\Taxonomy\Models\TaxonomyTerm;

/**
 * The single place an admin create/edit on a controlled reference list term
 * is applied (US-ADM-03). Shared by the REST endpoint and the four Filament
 * resources so the audit-log write stays in one place. No delete path exists
 * by design — {@see self::update()} is how a term is deactivated.
 */
class ManageTaxonomyTerm
{
    /**
     * @param  class-string<TaxonomyTerm>  $modelClass
     * @param  array{name_ar: string, name_en: string, hex?: string|null}  $data
     */
    public function create(string $modelClass, array $data, User $admin): TaxonomyTerm
    {
        /** @var TaxonomyTerm $term */
        $term = new $modelClass;

        $term->forceFill([
            'name_ar' => $data['name_ar'],
            'name_en' => $data['name_en'],
            'slug' => $this->uniqueSlug($modelClass, $data['name_en']),
            'is_active' => true,
            ...$this->colorFields($term, $data),
        ]);

        DB::transaction(function () use ($term, $admin): void {
            $term->save();

            AuditLog::record($admin, AuditAction::TaxonomyCreated, $term, [
                'name_ar' => $term->name_ar,
                'name_en' => $term->name_en,
            ]);
        });

        return $term;
    }

    /**
     * @param  array{name_ar?: string, name_en?: string, is_active?: bool, hex?: string|null}  $data
     */
    public function update(TaxonomyTerm $term, array $data, User $admin): TaxonomyTerm
    {
        // Captured before forceFill() below overwrites is_active — this is
        // what lets the audit action distinguish "deactivated" from a plain
        // "updated". Keep this read first if this method is ever reordered.
        $wasActive = $term->is_active;

        $term->forceFill([
            ...array_intersect_key($data, array_flip(['name_ar', 'name_en', 'is_active'])),
            ...$this->colorFields($term, $data),
        ]);

        $action = $wasActive && ($term->is_active === false)
            ? AuditAction::TaxonomyDeactivated
            : AuditAction::TaxonomyUpdated;

        DB::transaction(function () use ($term, $admin, $action): void {
            $term->save();

            AuditLog::record($admin, $action, $term, array_filter([
                'name_ar' => $term->name_ar,
                'name_en' => $term->name_en,
                'is_active' => $term->is_active,
            ], fn ($value): bool => $value !== null));
        });

        return $term;
    }

    /**
     * `hex` only exists on `colors`; other term types don't have the column,
     * so it must never be force-filled onto them.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function colorFields(TaxonomyTerm $term, array $data): array
    {
        if (! $term instanceof Color || ! array_key_exists('hex', $data)) {
            return [];
        }

        return ['hex' => $data['hex']];
    }

    /**
     * @param  class-string<TaxonomyTerm>  $modelClass
     */
    private function uniqueSlug(string $modelClass, string $nameEn): string
    {
        $base = Str::slug($nameEn);
        $slug = $base;
        $suffix = 2;

        while ($modelClass::query()->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
