<?php

namespace Modules\Taxonomy\Filament\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Modules\Taxonomy\Actions\ManageTaxonomyTerm;
use Modules\Taxonomy\Models\TaxonomyTerm;

/**
 * Routes a taxonomy resource's Create/Edit pages through
 * {@see ManageTaxonomyTerm} instead of a plain Eloquent save, so the panel
 * writes the same audit-log trail as the REST endpoint (US-ADM-03,
 * BR-ADM-01) rather than a second, divergent write path.
 */
trait SavesThroughManageTaxonomyTerm
{
    protected function handleRecordCreation(array $data): Model
    {
        /** @var User $admin */
        $admin = auth()->user();

        return app(ManageTaxonomyTerm::class)->create(static::getModel(), $data, $admin);
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var User $admin */
        $admin = auth()->user();

        /** @var TaxonomyTerm $record */
        return app(ManageTaxonomyTerm::class)->update($record, $data, $admin);
    }
}
