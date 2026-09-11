<?php

namespace Modules\Admin\Filament\Resources\Banners\Tables;

use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Modules\Admin\Actions\ManageBanner;
use Modules\Admin\Models\Banner;

class BannersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('position')
            ->columns([
                ImageColumn::make('image_path')
                    ->label(__('admin::panel.banner.fields.image'))
                    ->disk(fn (Banner $record): string => $record->image_disk),
                TextColumn::make('position')
                    ->label(__('admin::panel.banner.fields.position'))
                    ->sortable(),
                TextColumn::make('link_url')
                    ->label(__('admin::panel.banner.fields.link_url'))
                    ->placeholder('—')
                    ->limit(40),
                TextColumn::make('starts_at')
                    ->label(__('admin::panel.banner.fields.starts_at'))
                    ->dateTime()
                    ->placeholder(__('admin::panel.banner.fields.immediately')),
                TextColumn::make('ends_at')
                    ->label(__('admin::panel.banner.fields.ends_at'))
                    ->dateTime()
                    ->placeholder(__('admin::panel.banner.fields.no_end')),
                IconColumn::make('is_active')
                    ->label(__('admin::panel.banner.fields.is_active'))
                    ->boolean()
                    ->sortable(),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('remove')
                    ->label(__('admin::panel.banner.actions.remove'))
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (Banner $record): void {
                        app(ManageBanner::class)->remove($record, auth()->user());

                        Notification::make()->success()->title(__('admin::panel.banner.actions.removed'))->send();
                    }),
            ]);
    }
}
