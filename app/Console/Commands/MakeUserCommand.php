<?php

namespace App\Console\Commands;

use Filament\Commands\MakeUserCommand as BaseMakeUserCommand;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Modules\Auth\Enums\UserStatus;
use Spatie\Permission\Models\Role;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(name: 'make:filament-user', aliases: [
    'filament:make-user',
    'filament:user',
])]
class MakeUserCommand extends BaseMakeUserCommand
{
    protected function getOptions(): array
    {
        return [
            new InputOption(
                name: 'phone',
                shortcut: null,
                mode: InputOption::VALUE_REQUIRED,
                description: 'The phone number of the user',
            ),
            new InputOption(
                name: 'email',
                shortcut: null,
                mode: InputOption::VALUE_OPTIONAL,
                description: 'A valid and unique email address',
            ),
            new InputOption(
                name: 'password',
                shortcut: null,
                mode: InputOption::VALUE_REQUIRED,
                description: 'The password for the user (min. 8 characters)',
            ),
            new InputOption(
                name: 'panel',
                shortcut: null,
                mode: InputOption::VALUE_REQUIRED,
                description: 'The panel to create the user in',
            ),
        ];
    }

    protected function getUserData(): array
    {
        return [
            'phone' => $this->options['phone'] ?? text(
                label: 'Phone number',
                required: true,
                validate: fn (string $phone): ?string => match (true) {
                    static::getUserModel()::query()->where('phone', $phone)->exists() => 'A user with this phone number already exists.',
                    default => null,
                },
            ),
            'email' => $this->options['email'] ?? text(
                label: 'Email address (optional)',
                required: false,
                validate: function (?string $email): ?string {
                    if (blank($email)) {
                        return null;
                    }

                    if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        return 'The email address must be valid.';
                    }

                    if (static::getUserModel()::query()->where('email', $email)->exists()) {
                        return 'A user with this email address already exists.';
                    }

                    return null;
                },
            ),
            'password' => $this->options['password'] ?? \Laravel\Prompts\password(
                label: 'Password',
                required: true,
            ),
            'status' => UserStatus::Active,
        ];
    }

    protected function createUser(): Model&Authenticatable
    {
        $user = parent::createUser();

        $user->assignRole(Role::findOrCreate('admin', 'web'));

        return $user;
    }
}
