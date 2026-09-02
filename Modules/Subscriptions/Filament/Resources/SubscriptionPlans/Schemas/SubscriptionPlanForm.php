<?php

namespace Modules\Subscriptions\Filament\Resources\SubscriptionPlans\Schemas;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SubscriptionPlanForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Plan Details')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Select::make('account_type')
                            ->options([
                                'importer' => 'Importer',
                                'wholesaler' => 'Wholesaler',
                                'retailer' => 'Retailer',
                            ])
                            ->required(),
                        TextInput::make('price')
                            ->label('Price')
                            ->numeric()
                            ->step(0.01)
                            ->placeholder('Leave empty for custom pricing'),
                        Select::make('billing_cycle')
                            ->options([
                                'monthly' => 'Monthly',
                                'annual' => 'Annual',
                            ])
                            ->nullable()
                            ->placeholder('None'),
                        Toggle::make('is_active')
                            ->default(true),
                    ]),

                Section::make('Entitlements')
                    ->description('Key/value pairs — admin can edit without a migration (MNT-NFR-02).')
                    ->schema([
                        Repeater::make('entitlements')
                            ->relationship()
                            ->schema([
                                TextInput::make('key')
                                    ->required()
                                    ->placeholder('e.g. product_limit'),
                                TextInput::make('value')
                                    ->required()
                                    ->placeholder('e.g. Large, true, 100'),
                            ])
                            ->columns(2)
                            ->defaultItems(0)
                            ->addActionLabel('Add Entitlement')
                            ->reorderable(),
                    ]),
            ]);
    }
}
