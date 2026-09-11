<?php

namespace Modules\Search\Filament\Resources\FeaturedPlacements\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Modules\Favorites\Enums\FavoritableType;

class FeaturedPlacementForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('featurable_type')
                ->label(__('search::panel.fields.type'))
                ->options([
                    FavoritableType::Product->value => __('search::panel.types.product'),
                    FavoritableType::Supplier->value => __('search::panel.types.supplier'),
                ])
                ->required(),
            TextInput::make('featurable_id')
                ->label(__('search::panel.fields.featurable_id'))
                ->helperText(__('search::panel.fields.featurable_id_hint'))
                ->numeric()
                ->required(),
            TextInput::make('slot')
                ->label(__('search::panel.fields.slot'))
                ->required()
                ->maxLength(255),
            DateTimePicker::make('starts_at')
                ->label(__('search::panel.fields.starts_at'))
                ->helperText(__('search::panel.fields.starts_at_hint')),
            DateTimePicker::make('ends_at')
                ->label(__('search::panel.fields.ends_at'))
                ->helperText(__('search::panel.fields.ends_at_hint'))
                ->after('starts_at'),
        ]);
    }
}
