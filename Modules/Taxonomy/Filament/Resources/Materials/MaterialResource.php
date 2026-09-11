<?php

namespace Modules\Taxonomy\Filament\Resources\Materials;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Modules\Taxonomy\Filament\Concerns\IsTaxonomyTermResource;
use Modules\Taxonomy\Filament\Resources\Materials\Pages\CreateMaterial;
use Modules\Taxonomy\Filament\Resources\Materials\Pages\EditMaterial;
use Modules\Taxonomy\Filament\Resources\Materials\Pages\ListMaterials;
use Modules\Taxonomy\Models\Material;

class MaterialResource extends Resource
{
    use IsTaxonomyTermResource;

    protected static ?string $model = Material::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCube;

    protected static ?int $navigationSort = 31;

    protected static string $labelTranslationKey = 'taxonomy::panel.material';

    public static function getPages(): array
    {
        return [
            'index' => ListMaterials::route('/'),
            'create' => CreateMaterial::route('/create'),
            'edit' => EditMaterial::route('/{record}/edit'),
        ];
    }
}
