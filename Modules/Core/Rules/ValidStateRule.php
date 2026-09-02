<?php

namespace Modules\Core\Rules;

class ValidStateRule extends \Spatie\ModelStates\Validation\ValidStateRule
{
    public function message(): string
    {
        return __('validation.enum');
    }
}
