<?php

namespace Modules\Search\Tests\Feature\Filament;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Catalog\Models\Product;
use Modules\Search\Filament\Resources\FeaturedPlacements\Pages\CreateFeaturedPlacement;
use Modules\Search\Filament\Resources\FeaturedPlacements\Pages\ListFeaturedPlacements;
use Modules\Search\Models\FeaturedPlacement;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FeaturedPlacementPanelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function a_non_admin_cannot_open_the_featured_panel(): void
    {
        $this->actingAs(User::factory()->importer()->create())
            ->get('/admin/featured-placements')
            ->assertForbidden();
    }

    #[Test]
    public function an_admin_sees_placements_in_the_list(): void
    {
        $placement = FeaturedPlacement::factory()->create();

        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(ListFeaturedPlacements::class)
            ->assertOk()
            ->assertCanSeeTableRecords([$placement]);
    }

    #[Test]
    public function an_admin_creates_a_placement_from_the_panel(): void
    {
        $product = Product::factory()->published()->create();
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        Livewire::test(CreateFeaturedPlacement::class)
            ->fillForm([
                'featurable_type' => 'product',
                'featurable_id' => $product->id,
                'slot' => 'homepage_top',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('featured_placements', [
            'featurable_type' => 'product', 'featurable_id' => $product->id,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->id, 'action' => 'featured.placed',
        ]);
    }

    #[Test]
    public function an_admin_removes_a_placement_from_the_panel(): void
    {
        $placement = FeaturedPlacement::factory()->create();
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        Livewire::test(ListFeaturedPlacements::class)
            ->callTableAction('remove', $placement);

        $this->assertDatabaseMissing('featured_placements', ['id' => $placement->id]);
        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->id, 'action' => 'featured.removed',
        ]);
    }
}
