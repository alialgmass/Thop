<?php

namespace Modules\Admin\Tests\Feature\Filament;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Admin\Filament\Widgets\LiquidityStatsWidget;
use Modules\Businesses\Models\BusinessAccount;
use Modules\Catalog\Models\Product;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LiquidityStatsWidgetTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function a_non_admin_cannot_open_the_admin_dashboard(): void
    {
        $this->actingAs(User::factory()->importer()->create())
            ->get('/admin')
            ->assertForbidden();
    }

    #[Test]
    public function an_admin_sees_the_computed_metrics_in_the_widget(): void
    {
        $business = BusinessAccount::factory()->verified()->create();
        Product::factory()->published()->for($business)->count(2)->create();

        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(LiquidityStatsWidget::class)
            ->assertOk()
            ->assertSee('1') // active sellers
            ->assertSee('2'); // active products
    }
}
