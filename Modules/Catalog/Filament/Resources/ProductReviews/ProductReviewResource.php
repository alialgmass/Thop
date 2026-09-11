<?php

namespace Modules\Catalog\Filament\Resources\ProductReviews;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Modules\Catalog\Enums\ProductStatus;
use Modules\Catalog\Filament\Resources\ProductReviews\Pages\ListProductReviews;
use Modules\Catalog\Filament\Resources\ProductReviews\Pages\ViewProductReview;
use Modules\Catalog\Filament\Resources\ProductReviews\Tables\ProductReviewsTable;
use Modules\Catalog\Models\Product;
use Modules\Core\Filament\Concerns\HasLocalizedLabels;

/**
 * Admin review queue for the product catalog (US-SEL-11, Phase 9 · T2). Read +
 * decide only — products are created by sellers through the API, never here.
 */
class ProductReviewResource extends Resource
{
    use HasLocalizedLabels;

    protected static ?string $model = Product::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingBag;

    protected static ?int $navigationSort = 11;

    protected static string $labelTranslationKey = 'catalog::panel';

    protected static string $navigationGroupTranslationKey = 'panel.nav.moderation';

    public static function table(Table $table): Table
    {
        return ProductReviewsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProductReviews::route('/'),
            'view' => ViewProductReview::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['businessAccount.owner', 'media']);
    }

    public static function getNavigationBadge(): ?string
    {
        $pending = static::getModel()::query()
            ->where('status', ProductStatus::PendingReview)
            ->count();

        return $pending > 0 ? (string) $pending : null;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(mixed $record): bool
    {
        return false;
    }

    public static function canDelete(mixed $record): bool
    {
        return false;
    }
}
