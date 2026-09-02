<?php

namespace Modules\Core\Support\Packages;

use Illuminate\Support\Str;
use Modules\Core\Support\Contracts\PhoneNumberInterface;
use Propaganistas\LaravelPhone\Exceptions\CountryCodeException;
use Propaganistas\LaravelPhone\Exceptions\NumberFormatException;
use Propaganistas\LaravelPhone\Exceptions\NumberParseException;
use Propaganistas\LaravelPhone\PhoneNumber as LaravelPhoneNumber;

class PhoneNumber extends LaravelPhoneNumber implements PhoneNumberInterface
{
    /**
     * @throws NumberParseException
     */
    public function phoneUtility(): ?\libphonenumber\PhoneNumber
    {
        return $this->toLibPhoneObject();
    }

    /**
     * @throws NumberFormatException
     */
    public function formatE164WithoutPlus(): string
    {
        return Str::remove('+', $this->formatE164());
    }

    /**
     * @throws NumberParseException
     */
    public function getNationalNumber(): ?string
    {
        return $this->phoneUtility()->getNationalNumber();
    }

    /**
     * @throws NumberParseException
     * @throws NumberFormatException
     * @throws CountryCodeException
     */
    public function phoneUtilityObject(): object
    {
        return collect([
            'country' => $this->getCountry(),
            'country_code' => $this->phoneUtility()->getCountryCode(),
            'country_code_source' => $this->phoneUtility()->getCountryCodeSource(),
            'format_international' => $this->formatInternational(),
            'national_number' => $this->getNationalNumber(),
            'format_E164' => $this->formatE164(),
            'format_E164_without_plus' => $this->formatE164WithoutPlus(),
            'raw_number' => $this->getRawNumber(),
            'format_for_country' => $this->formatForCountry($this->getCountry()),
            'type' => $this->getType(),
        ]);
    }
}
