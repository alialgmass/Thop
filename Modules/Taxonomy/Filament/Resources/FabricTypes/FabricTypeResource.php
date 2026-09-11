<?php

namespace Modules\Taxonomy\Filament\Resources\FabricTypes;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Modules\Taxonomy\Filament\Concerns\IsTaxonomyTermResource;
use Modules\Taxonomy\Filament\Resources\FabricTypes\Pages\CreateFabricType;
use Modules\Taxonomy\Filament\Resources\FabricTypes\Pages\EditFabricType;
use Modules\Taxonomy\Filament\Resources\FabricTypes\Pages\ListFabricTypes;
use Modules\Taxonomy\Models\FabricType;

class FabricTypeResource extends Resource
{
    use IsTaxonomyTermResource;

    protected static ?string $model = FabricType::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSwatch;

    protected static ?int $navigationSort = 30;

    protected static string $labelTranslationKey = 'taxonomy::panel.fabric_type';

    public static function getPages(): array
    {
        return [
            'index' => ListFabricTypes::route('/'),
            'create' => CreateFabricType::route('/create'),
            'edit' => EditFabricType::route('/{record}/edit'),
        ];
    }
}
