<?php

namespace Modules\Taxonomy\Filament\Resources\Units;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Modules\Taxonomy\Filament\Concerns\IsTaxonomyTermResource;
use Modules\Taxonomy\Filament\Resources\Units\Pages\CreateUnit;
use Modules\Taxonomy\Filament\Resources\Units\Pages\EditUnit;
use Modules\Taxonomy\Filament\Resources\Units\Pages\ListUnits;
use Modules\Taxonomy\Models\Unit;

class UnitResource extends Resource
{
    use IsTaxonomyTermResource;

    protected static ?string $model = Unit::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedScale;

    protected static ?int $navigationSort = 33;

    protected static string $labelTranslationKey = 'taxonomy::panel.unit';

    public static function getPages(): array
    {
        return [
            'index' => ListUnits::route('/'),
            'create' => CreateUnit::route('/create'),
            'edit' => EditUnit::route('/{record}/edit'),
        ];
    }
}
