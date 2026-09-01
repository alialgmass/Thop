<?php

namespace Modules\Auth\Tests\Unit;

use Modules\Auth\Enums\AccountType;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class AccountTypeTest extends TestCase
{
    #[Test]
    public function business_types_require_a_business_profile(): void
    {
        $this->assertTrue(AccountType::Importer->requiresBusinessProfile());
        $this->assertTrue(AccountType::Wholesaler->requiresBusinessProfile());
        $this->assertTrue(AccountType::Retailer->requiresBusinessProfile());
        $this->assertSame('business_profile', AccountType::Importer->nextOnboardingStep());
    }

    #[Test]
    public function customers_do_not_require_a_business_profile(): void
    {
        $this->assertFalse(AccountType::Customer->requiresBusinessProfile());
        $this->assertSame('none', AccountType::Customer->nextOnboardingStep());
    }
}
