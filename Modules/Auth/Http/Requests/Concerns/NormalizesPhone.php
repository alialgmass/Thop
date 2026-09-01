<?php

namespace Modules\Auth\Http\Requests\Concerns;

use LogicException;
use Modules\Auth\Rules\EgyptianMobile;
use Modules\Auth\Support\PhoneNumber;

/**
 * Shared canonical-phone accessor for requests whose `phone` field is validated
 * by {@see EgyptianMobile}, so normalization always succeeds.
 */
trait NormalizesPhone
{
    public function phone(): string
    {
        return PhoneNumber::normalize($this->input('phone'))
            ?? throw new LogicException('phone() was read before the EgyptianMobile rule accepted the value.');
    }
}
