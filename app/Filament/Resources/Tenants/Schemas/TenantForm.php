<?php

namespace App\Filament\Resources\Tenants\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class TenantForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                Textarea::make('settings')
                    ->columnSpanFull(),
                TextInput::make('plan_id')
                    ->numeric(),
                DateTimePicker::make('trial_ends_at'),
            ]);
    }
}
