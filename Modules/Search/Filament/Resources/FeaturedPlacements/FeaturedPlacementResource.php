<?php

namespace Modules\Search\Filament\Resources\FeaturedPlacements;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Modules\Core\Filament\Concerns\HasLocalizedLabels;
use Modules\Search\Filament\Resources\FeaturedPlacements\Pages\CreateFeaturedPlacement;
use Modules\Search\Filament\Resources\FeaturedPlacements\Pages\ListFeaturedPlacements;
use Modules\Search\Filament\Resources\FeaturedPlacements\Schemas\FeaturedPlacementForm;
use Modules\Search\Filament\Resources\FeaturedPlacements\Tables\FeaturedPlacementsTable;
use Modules\Search\Models\FeaturedPlacement;

/**
 * Admin curation of featured placements (US-SRC-10, BR-SRC-01, Phase 9 · T5).
 * Create + remove only — no edit; change the date window by removing and
 * re-creating, same as the REST surface.
 */
class FeaturedPlacementResource extends Resource
{
    use HasLocalizedLabels;

    protected static ?string $model = FeaturedPlacement::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedStar;

    protected static ?int $navigationSort = 12;

    protected static string $labelTranslationKey = 'search::panel.featured';

    protected static string $navigationGroupTranslationKey = 'panel.nav.moderation';

    public static function form(Schema $schema): Schema
    {
        return FeaturedPlacementForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FeaturedPlacementsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFeaturedPlacements::route('/'),
            'create' => CreateFeaturedPlacement::route('/create'),
        ];
    }

    public static function canEdit(mixed $record): bool
    {
        return false;
    }
}
