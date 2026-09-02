<?php

namespace Modules\Core\Tests\Unit;

use Modules\Core\Support\Packages\PhoneNumber;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PhoneNumberTest extends TestCase
{
    #[Test]
    public function the_e164_format_is_returned_without_the_leading_plus(): void
    {
        $phone = new PhoneNumber('+20 101 234 5678', 'EG');

        $this->assertSame('+201012345678', $phone->formatE164());
        $this->assertSame('201012345678', $phone->formatE164WithoutPlus());
    }

    #[Test]
    public function the_national_number_is_exposed(): void
    {
        $phone = new PhoneNumber('+20 101 234 5678', 'EG');

        $this->assertSame('1012345678', $phone->getNationalNumber());
    }

    #[Test]
    public function the_utility_object_aggregates_the_phone_components(): void
    {
        $phone = new PhoneNumber('+201012345678');

        $object = $phone->phoneUtilityObject();

        $this->assertSame('EG', $object->get('country'));
        $this->assertSame(20, $object->get('country_code'));
        $this->assertSame('201012345678', $object->get('format_E164_without_plus'));
        $this->assertSame('1012345678', $object->get('national_number'));
        $this->assertSame('+20 10 12345678', $object->get('format_international'));
    }
}
