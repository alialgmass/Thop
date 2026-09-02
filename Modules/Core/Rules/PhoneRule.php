<?php

namespace Modules\Core\Rules;

use Propaganistas\LaravelPhone\Rules\Phone;

class PhoneRule extends Phone
{
    protected ?string $countryField = null;

    protected array $countries = ['*'];

    protected array $allowedTypes = [];

    protected array $blockedTypes = [];

    protected bool $international = false;

    protected bool $lenient = false;
}
