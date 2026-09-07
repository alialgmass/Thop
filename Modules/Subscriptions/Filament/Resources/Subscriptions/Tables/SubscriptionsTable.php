<?php

namespace Modules\Subscriptions\Filament\Resources\Subscriptions\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Modules\Subscriptions\Enums\SubscriptionStatus;

class SubscriptionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('businessAccount.company_name')
                    ->label(__('subscriptions::panel.fields.company'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('businessAccount.owner.phone')
                    ->label(__('subscriptions::panel.fields.owner_phone'))
                    ->searchable(),
                TextColumn::make('plan.name')
                    ->label(__('subscriptions::panel.fields.plan'))
                    ->badge()
                    ->sortable(),
                TextColumn::make('plan.account_type')
                    ->label(__('subscriptions::panel.fields.account_type'))
                    ->badge()
                    ->sortable(),
                TextColumn::make('status')
                    ->label(__('subscriptions::panel.fields.status'))
                    ->badge()
                    ->color(fn (SubscriptionStatus $state): string => match ($state) {
                        SubscriptionStatus::Active => 'success',
                        SubscriptionStatus::Expired => 'warning',
                        SubscriptionStatus::Cancelled => 'danger',
                        SubscriptionStatus::Restricted => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('current_period_end')
                    ->label(__('subscriptions::panel.fields.period_ends'))
                    ->dateTime()
                    ->sortable()
                    ->placeholder('—'),
                TextColumn::make('trial_ends_at')
                    ->label(__('subscriptions::panel.fields.trial_ends'))
                    ->dateTime()
                    ->sortable()
                    ->placeholder('—'),
                TextColumn::make('created_at')
                    ->label(__('panel.common.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('subscriptions::panel.fields.status'))
                    ->options(SubscriptionStatus::class),
                SelectFilter::make('plan.account_type')
                    ->label(__('subscriptions::panel.fields.account_type'))
                    ->options([
                        'importer' => __('subscriptions::panel.account_types.importer'),
                        'wholesaler' => __('subscriptions::panel.account_types.wholesaler'),
                        'retailer' => __('subscriptions::panel.account_types.retailer'),
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
