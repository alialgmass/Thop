<?php

namespace Modules\Core\Tests\Unit;

use Modules\Core\Support\Enums\StateColor;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StateColorTest extends TestCase
{
    #[Test]
    public function each_color_maps_to_a_tailwind_style_class(): void
    {
        $this->assertSame('green', StateColor::SUCCESS->styleClass());
        $this->assertSame('sky', StateColor::INFO->styleClass());
        $this->assertSame('yellow', StateColor::WARNING->styleClass());
        $this->assertSame('red', StateColor::DANGER->styleClass());
    }

    #[Test]
    public function each_color_maps_to_a_hexadecimal_value(): void
    {
        $this->assertSame('#198754', StateColor::SUCCESS->hexadecimal());
        $this->assertSame('#0dcaf0', StateColor::INFO->hexadecimal());
        $this->assertSame('#ffc107', StateColor::WARNING->hexadecimal());
        $this->assertSame('#dc3545', StateColor::DANGER->hexadecimal());
    }
}
