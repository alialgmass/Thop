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
                Section::make(__('subscriptions::panel.sections.plan_details'))
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label(__('subscriptions::panel.fields.name'))
                            ->required()
                            ->maxLength(255),
                        Select::make('account_type')
                            ->label(__('subscriptions::panel.fields.account_type'))
                            ->options([
                                'importer' => __('subscriptions::panel.account_types.importer'),
                                'wholesaler' => __('subscriptions::panel.account_types.wholesaler'),
                                'retailer' => __('subscriptions::panel.account_types.retailer'),
                            ])
                            ->required(),
                        TextInput::make('price')
                            ->label(__('subscriptions::panel.fields.price'))
                            ->numeric()
                            ->step(0.01)
                            ->placeholder(__('subscriptions::panel.fields.price_custom_hint')),
                        Select::make('billing_cycle')
                            ->label(__('subscriptions::panel.fields.billing_cycle'))
                            ->options([
                                'monthly' => __('subscriptions::panel.billing_cycles.monthly'),
                                'annual' => __('subscriptions::panel.billing_cycles.annual'),
                            ])
                            ->nullable()
                            ->placeholder(__('subscriptions::panel.fields.none')),
                        TextInput::make('trial_days')
                            ->label(__('subscriptions::panel.fields.trial_days'))
                            ->numeric()
                            ->minValue(1)
                            ->placeholder(__('subscriptions::panel.fields.none')),
                        Toggle::make('is_active')
                            ->label(__('subscriptions::panel.fields.is_active'))
                            ->default(true),
                    ]),

                Section::make(__('subscriptions::panel.sections.entitlements'))
                    ->description(__('subscriptions::panel.sections.entitlements_hint'))
                    ->schema([
                        Repeater::make('entitlements')
                            ->relationship()
                            ->schema([
                                TextInput::make('key')
                                    ->label(__('subscriptions::panel.fields.entitlement_key'))
                                    ->required()
                                    ->placeholder(__('subscriptions::panel.fields.entitlement_key_hint')),
                                TextInput::make('value')
                                    ->label(__('subscriptions::panel.fields.entitlement_value'))
                                    ->required()
                                    ->placeholder(__('subscriptions::panel.fields.entitlement_value_hint')),
                            ])
                            ->columns(2)
                            ->defaultItems(0)
                            ->addActionLabel(__('subscriptions::panel.fields.add_entitlement'))
                            ->reorderable(),
                    ]),
            ]);
    }
}
