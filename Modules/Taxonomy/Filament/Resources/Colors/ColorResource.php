<?php

namespace Modules\Taxonomy\Filament\Resources\Colors;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Modules\Taxonomy\Filament\Concerns\IsTaxonomyTermResource;
use Modules\Taxonomy\Filament\Resources\Colors\Pages\CreateColor;
use Modules\Taxonomy\Filament\Resources\Colors\Pages\EditColor;
use Modules\Taxonomy\Filament\Resources\Colors\Pages\ListColors;
use Modules\Taxonomy\Models\Color;

class ColorResource extends Resource
{
    use IsTaxonomyTermResource;

    protected static ?string $model = Color::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPaintBrush;

    protected static ?int $navigationSort = 32;

    protected static string $labelTranslationKey = 'taxonomy::panel.color';

    public static function withHex(): bool
    {
        return true;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListColors::route('/'),
            'create' => CreateColor::route('/create'),
            'edit' => EditColor::route('/{record}/edit'),
        ];
    }
}
