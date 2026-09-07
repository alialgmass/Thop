<?php

namespace Modules\Admin\Filament\Resources\AuditLogs\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Modules\Admin\Models\AuditLog;

class AuditLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('created_at')
                    ->label(__('admin::panel.columns.when'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('actor.phone')
                    ->label(__('admin::panel.columns.actor'))
                    ->searchable(),
                TextColumn::make('action')
                    ->label(__('admin::panel.columns.action'))
                    ->badge()
                    ->searchable()
                    ->sortable(),
                TextColumn::make('auditable_type')
                    ->label(__('admin::panel.columns.entity'))
                    ->formatStateUsing(fn (string $state): string => class_basename($state))
                    ->searchable(),
                TextColumn::make('auditable_id')
                    ->label(__('admin::panel.columns.entity_id')),
            ])
            ->filters([
                SelectFilter::make('action')
                    ->label(__('admin::panel.filters.action'))
                    ->options(fn (): array => AuditLog::query()
                        ->distinct()
                        ->orderBy('action')
                        ->pluck('action', 'action')
                        ->all()),
                SelectFilter::make('actor_id')
                    ->label(__('admin::panel.filters.actor'))
                    ->relationship('actor', 'phone'),
                SelectFilter::make('auditable_type')
                    ->label(__('admin::panel.filters.entity'))
                    ->options(fn (): array => AuditLog::query()
                        ->distinct()
                        ->orderBy('auditable_type')
                        ->pluck('auditable_type', 'auditable_type')
                        ->map(fn (string $type): string => class_basename($type))
                        ->all()),
            ]);
    }
}
