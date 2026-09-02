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
                    ->label('Business')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('businessAccount.owner.phone')
                    ->label('Owner')
                    ->searchable(),
                TextColumn::make('plan.name')
                    ->label('Plan')
                    ->badge()
                    ->sortable(),
                TextColumn::make('plan.account_type')
                    ->label('Account Type')
                    ->badge()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (SubscriptionStatus $state): string => match ($state) {
                        SubscriptionStatus::Active => 'success',
                        SubscriptionStatus::Expired => 'warning',
                        SubscriptionStatus::Cancelled => 'danger',
                        SubscriptionStatus::Restricted => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('current_period_end')
                    ->label('Period Ends')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('—'),
                TextColumn::make('trial_ends_at')
                    ->label('Trial Ends')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('—'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(SubscriptionStatus::class),
                SelectFilter::make('plan.account_type')
                    ->label('Account Type')
                    ->options([
                        'importer' => 'Importer',
                        'wholesaler' => 'Wholesaler',
                        'retailer' => 'Retailer',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
