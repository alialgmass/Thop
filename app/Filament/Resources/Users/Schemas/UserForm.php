<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;
use Modules\Auth\Enums\AccountType;
use Modules\Auth\Enums\UserStatus;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('phone')
                    ->required()
                    ->tel(),
                TextInput::make('email')
                    ->label('Email address')
                    ->email(),
                TextInput::make('password')
                    ->password()
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->required(fn (string $operation): bool => $operation === 'create'),
                Select::make('account_type')
                    ->options(collect(AccountType::cases())
                        ->mapWithKeys(fn (AccountType $t): array => [$t->value => Str::headline($t->value)])
                        ->all()),
                Select::make('language')
                    ->options(['ar' => 'العربية', 'en' => 'English'])
                    ->default('ar')
                    ->required(),
                Select::make('status')
                    ->options(collect(UserStatus::cases())
                        ->mapWithKeys(fn (UserStatus $s): array => [$s->value => Str::headline($s->value)])
                        ->all())
                    ->default(UserStatus::PendingTypeSelection->value)
                    ->required(),
            ]);
    }
}
