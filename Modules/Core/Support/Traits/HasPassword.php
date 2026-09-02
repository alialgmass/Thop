<?php

namespace Modules\Core\Support\Traits;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Hash;

trait HasPassword
{
    /**
     * Interact with the password attribute.
     */
    public function setPasswordAttribute($value)
    {
        if ($value === null || ! is_string($value)) {
            return;
        }
        $this->attributes['password'] = Hash::needsRehash($value) ? Hash::make($value) : $value;
    }
}
