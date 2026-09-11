<?php

namespace Modules\Taxonomy\Filament\Support;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class TaxonomyTermTable
{
    public static function configure(Table $table, bool $withHex = false): Table
    {
        return $table
            ->defaultSort('name_en')
            ->columns([
                TextColumn::make('name_ar')
                    ->label(__('taxonomy::panel.fields.name_ar'))
                    ->searchable(),
                TextColumn::make('name_en')
                    ->label(__('taxonomy::panel.fields.name_en'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('slug')
                    ->label(__('taxonomy::panel.fields.slug'))
                    ->toggleable(),
                ...($withHex ? [
                    TextColumn::make('hex')
                        ->label(__('taxonomy::panel.fields.hex'))
                        ->badge()
                        ->color(fn (?string $state): string => $state ?? 'gray'),
                ] : []),
                IconColumn::make('is_active')
                    ->label(__('taxonomy::panel.fields.is_active'))
                    ->boolean()
                    ->sortable(),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label(__('taxonomy::panel.fields.is_active')),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
