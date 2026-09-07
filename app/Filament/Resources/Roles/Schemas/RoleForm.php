<?php

namespace App\Filament\Resources\Roles\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class RoleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label(__('panel.role.fields.name'))
                    ->required(),
                TextInput::make('guard_name')
                    ->label(__('panel.common.guard_name'))
                    ->required(),
            ]);
    }
}
