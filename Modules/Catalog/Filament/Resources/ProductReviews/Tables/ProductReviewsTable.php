<?php

namespace Modules\Catalog\Filament\Resources\ProductReviews\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Modules\Catalog\Enums\ProductStatus;

class ProductReviewsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('name_ar')
                    ->label(__('catalog::panel.columns.name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('businessAccount.company_name')
                    ->label(__('catalog::panel.columns.seller'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('media_count')
                    ->counts('media')
                    ->label(__('catalog::panel.columns.images'))
                    ->badge(),
                TextColumn::make('status')
                    ->label(__('catalog::panel.columns.status'))
                    ->badge()
                    ->color(fn (ProductStatus $state): string => match ($state) {
                        ProductStatus::Draft => 'gray',
                        ProductStatus::PendingReview => 'warning',
                        ProductStatus::Published => 'success',
                        ProductStatus::Hidden, ProductStatus::Unavailable => 'gray',
                        ProductStatus::Rejected => 'danger',
                    })
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label(__('catalog::panel.columns.submitted_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('catalog::panel.columns.status'))
                    ->options(ProductStatus::class)
                    ->default(ProductStatus::PendingReview->value),
                SelectFilter::make('businessAccount')
                    ->label(__('catalog::panel.columns.seller'))
                    ->relationship('businessAccount', 'company_name')
                    ->searchable(),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
