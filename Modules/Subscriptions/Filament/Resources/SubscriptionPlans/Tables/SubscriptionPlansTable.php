<?php

namespace Modules\Subscriptions\Filament\Resources\SubscriptionPlans\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SubscriptionPlansTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('account_type')
                    ->label(__('subscriptions::panel.fields.account_type'))
                    ->badge()
                    ->sortable(),
                TextColumn::make('name')
                    ->label(__('subscriptions::panel.fields.name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('price')
                    ->label(__('subscriptions::panel.fields.price'))
                    ->formatStateUsing(fn ($state): string => $state !== null ? number_format((float) $state, 2) : __('subscriptions::panel.fields.custom'))
                    ->sortable(),
                TextColumn::make('billing_cycle')
                    ->label(__('subscriptions::panel.fields.cycle'))
                    ->placeholder('—')
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label(__('subscriptions::panel.fields.is_active'))
                    ->boolean()
                    ->sortable(),
                TextColumn::make('entitlements_count')
                    ->counts('entitlements')
                    ->label(__('subscriptions::panel.fields.entitlements'))
                    ->badge(),
                TextColumn::make('created_at')
                    ->label(__('panel.common.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('account_type')
                    ->label(__('subscriptions::panel.fields.account_type'))
                    ->options([
                        'importer' => __('subscriptions::panel.account_types.importer'),
                        'wholesaler' => __('subscriptions::panel.account_types.wholesaler'),
                        'retailer' => __('subscriptions::panel.account_types.retailer'),
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
