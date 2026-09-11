<?php

namespace Modules\Admin\Filament\Resources\Banners;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Modules\Admin\Filament\Resources\Banners\Pages\CreateBanner;
use Modules\Admin\Filament\Resources\Banners\Pages\EditBanner;
use Modules\Admin\Filament\Resources\Banners\Pages\ListBanners;
use Modules\Admin\Filament\Resources\Banners\Schemas\BannerForm;
use Modules\Admin\Filament\Resources\Banners\Tables\BannersTable;
use Modules\Admin\Models\Banner as BannerModel;
use Modules\Core\Filament\Concerns\HasLocalizedLabels;

/**
 * Homepage banner management for the separate marketplace client's homepage
 * (Phase 9 · T6, issue #35) — this repo has no homepage of its own.
 */
class BannerResource extends Resource
{
    use HasLocalizedLabels;

    protected static ?string $model = BannerModel::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoto;

    protected static ?int $navigationSort = 13;

    protected static string $labelTranslationKey = 'admin::panel.banner';

    protected static string $navigationGroupTranslationKey = 'panel.nav.moderation';

    public static function form(Schema $schema): Schema
    {
        return BannerForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BannersTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBanners::route('/'),
            'create' => CreateBanner::route('/create'),
            'edit' => EditBanner::route('/{record}/edit'),
        ];
    }

    public static function canDelete(mixed $record): bool
    {
        return false;
    }
}
