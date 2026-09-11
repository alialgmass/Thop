<?php

namespace Modules\Search\Filament\Resources\FeaturedPlacements\Tables;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Modules\Favorites\Enums\FavoritableType;
use Modules\Search\Actions\ManageFeaturedPlacement;
use Modules\Search\Models\FeaturedPlacement;

class FeaturedPlacementsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('featurable_type')
                    ->label(__('search::panel.fields.type'))
                    ->badge(),
                TextColumn::make('featurable_id')
                    ->label(__('search::panel.fields.featurable_id')),
                TextColumn::make('slot')
                    ->label(__('search::panel.fields.slot')),
                TextColumn::make('starts_at')
                    ->label(__('search::panel.fields.starts_at'))
                    ->dateTime()
                    ->placeholder(__('search::panel.fields.immediately')),
                TextColumn::make('ends_at')
                    ->label(__('search::panel.fields.ends_at'))
                    ->dateTime()
                    ->placeholder(__('search::panel.fields.no_end')),
                IconColumn::make('is_active')
                    ->label(__('search::panel.fields.active_now'))
                    ->boolean()
                    ->state(fn (FeaturedPlacement $record): bool => $record->isCurrentlyActive()),
                TextColumn::make('creator.phone')
                    ->label(__('search::panel.fields.created_by'))
                    ->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('featurable_type')
                    ->label(__('search::panel.fields.type'))
                    ->options([
                        FavoritableType::Product->value => __('search::panel.types.product'),
                        FavoritableType::Supplier->value => __('search::panel.types.supplier'),
                    ]),
            ])
            ->recordActions([
                Action::make('remove')
                    ->label(__('search::panel.actions.remove'))
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (FeaturedPlacement $record): void {
                        app(ManageFeaturedPlacement::class)->remove($record, auth()->user());

                        Notification::make()->success()->title(__('search::panel.actions.removed'))->send();
                    }),
            ]);
    }
}
