<?php

namespace App\Http\Concerns;

use App\Models\User;
use Illuminate\Http\Request;

/**
 * Narrows `Request::user()` (nullable, generic) to the concrete {@see User} for
 * controller actions that sit behind `auth:sanctum` and therefore always have one.
 */
trait ResolvesRequestUser
{
    protected function currentUser(Request $request): User
    {
        /** @var User $user */
        $user = $request->user();

        return $user;
    }
}
