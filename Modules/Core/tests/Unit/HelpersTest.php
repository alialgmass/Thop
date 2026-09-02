<?php

namespace Modules\Core\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class HelpersTest extends TestCase
{
    #[Test]
    public function registered_modules_contains_the_expected_thop_modules(): void
    {
        $this->assertSame([
            'Admin',
            'Auth',
            'Businesses',
            'Taxonomy',
            'Verification',
            'Core',
        ], registeredModules());
    }

    #[Test]
    public function get_current_lang_reflects_the_application_locale(): void
    {
        $this->assertSame('en', app()->getLocale());
        $this->assertSame('en', getCurrentLang());

        app()->setLocale('ar');

        $this->assertSame('ar', getCurrentLang());
    }

    #[Test]
    public function user_is_null_when_no_guard_is_authenticated(): void
    {
        $this->assertNull(activeGuard());
        $this->assertNull(user());
    }
}
