<?php

namespace Modules\Core\Tests\Unit;

use Modules\Core\Support\Traits\EnumCommonTrait;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

enum ShippingStatus: string
{
    use EnumCommonTrait;

    case Pending = 'pending';
    case Shipped = 'shipped';
    case Delivered = 'delivered';
}

class EnumTraitTest extends TestCase
{
    #[Test]
    public function the_label_resolves_a_translated_key_based_on_the_class_and_value(): void
    {
        $this->app->translator->addLines([
            'enum.shipping_status.pending' => 'Pending shipment',
        ], 'en', '*');

        $this->assertSame('Pending shipment', ShippingStatus::Pending->label());
    }

    #[Test]
    public function to_array_maps_every_case_to_its_label(): void
    {
        $this->app->translator->addLines([
            'enum.shipping_status.pending' => 'Pending',
            'enum.shipping_status.shipped' => 'Shipped',
            'enum.shipping_status.delivered' => 'Delivered',
        ], 'en', '*');

        $this->assertSame([
            'pending' => 'Pending',
            'shipped' => 'Shipped',
            'delivered' => 'Delivered',
        ], ShippingStatus::toArray());
    }
}
