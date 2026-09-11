<?php

namespace Modules\Taxonomy\Filament\Concerns;

use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Modules\Core\Filament\Concerns\HasLocalizedLabels;
use Modules\Taxonomy\Filament\Support\TaxonomyTermForm;
use Modules\Taxonomy\Filament\Support\TaxonomyTermTable;

/**
 * The behaviour the four `TaxonomyTerm` resources share (US-ADM-03): the same
 * form/table shape, no delete (deactivate via edit instead — {@see ManageTaxonomyTerm}),
 * and the System nav group. Override {@see self::withHex()} for `colors`.
 */
trait IsTaxonomyTermResource
{
    use HasLocalizedLabels;

    protected static string $navigationGroupTranslationKey = 'panel.nav.system';

    public static function form(Schema $schema): Schema
    {
        return TaxonomyTermForm::configure($schema, static::withHex());
    }

    public static function table(Table $table): Table
    {
        return TaxonomyTermTable::configure($table, static::withHex());
    }

    public static function withHex(): bool
    {
        return false;
    }

    public static function canDelete(mixed $record): bool
    {
        return false;
    }
}
