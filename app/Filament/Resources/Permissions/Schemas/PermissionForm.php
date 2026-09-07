<?php

namespace App\Filament\Resources\Permissions\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class PermissionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label(__('panel.permission.fields.name'))
                    ->required(),
                TextInput::make('guard_name')
                    ->label(__('panel.common.guard_name'))
                    ->required(),
            ]);
    }
}
