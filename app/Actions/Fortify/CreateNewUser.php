<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Modules\Tenancy\Actions\RegisterTenantAction;
use Modules\Tenancy\DTOs\RegisterTenantData;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        // Map 'name' to 'owner_name' if coming from a default registration form
        if (! isset($input['owner_name']) && isset($input['name'])) {
            $input['owner_name'] = $input['name'];
        }

        // Default 'store_name' if missing
        if (! isset($input['store_name']) && isset($input['owner_name'])) {
            $input['store_name'] = $input['owner_name']."'s Store";
        }

        Validator::make($input, [
            'store_name' => ['required', 'string', 'max:80'],
            'owner_name' => ['required', 'string', 'max:255'],
            'email' => $this->emailRules(),
            'password' => $this->passwordRules(),
        ])->validate();

        $user = app(RegisterTenantAction::class)->execute(
            RegisterTenantData::fromArray($input),
        );

        Auth::guard(config('fortify.guard', 'web'))->login($user);

        return $user;
    }
}
