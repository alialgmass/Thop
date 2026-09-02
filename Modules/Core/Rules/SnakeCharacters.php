<?php

namespace Modules\Core\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class SnakeCharacters implements ValidationRule
{
    public function __construct(private readonly string $dashLodash = '-_') {}

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! preg_match("/^[a-zA-Z0-9-{$this->dashLodash}]+$/u", $value)) {
            $fail('validation.snake-characters-lodash')->translate([
                'attribute' => __("validation.attributes.$attribute"),
                'value' => $value,
            ]);
        }
    }
}
