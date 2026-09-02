<?php

namespace Modules\Core\Rules;

use Illuminate\Contracts\Validation\ValidationRule;

class NoSpecialCharacters implements ValidationRule
{
    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, \Closure $fail): void
    {
        if (! preg_match('~^[\p{L}\p{N}\s]+$~uD', $value)) {
            $fail('validation.no special characters')->translate([
                'attribute' => $attribute,
            ]);
        }
    }
}
