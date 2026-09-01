<?php

namespace Modules\Auth\Tests\Unit;

use Modules\Auth\Support\PhoneNumber;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class PhoneNumberTest extends TestCase
{
    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function acceptedFormats(): array
    {
        return [
            'local with leading zero' => ['01012345678', '+201012345678'],
            'country code no plus' => ['201012345678', '+201012345678'],
            'full e164' => ['+201012345678', '+201012345678'],
            'spaced' => ['+20 101 234 5678', '+201012345678'],
            'no zero no country code' => ['1012345678', '+201012345678'],
            'vodafone prefix' => ['01112345678', '+201112345678'],
        ];
    }

    #[Test]
    #[DataProvider('acceptedFormats')]
    public function it_normalizes_accepted_formats_to_canonical(string $input, string $expected): void
    {
        $this->assertSame($expected, PhoneNumber::normalize($input));
    }

    /**
     * @return array<string, array{0: ?string}>
     */
    public static function rejectedInputs(): array
    {
        return [
            'null' => [null],
            'empty' => [''],
            'too short' => ['0101234'],
            'landline prefix' => ['0221234567'],
            'us number' => ['+15550100000'],
            'letters' => ['not-a-phone'],
        ];
    }

    #[Test]
    #[DataProvider('rejectedInputs')]
    public function it_rejects_invalid_numbers(?string $input): void
    {
        $this->assertNull(PhoneNumber::normalize($input));
        $this->assertFalse(PhoneNumber::isValid($input));
    }
}
