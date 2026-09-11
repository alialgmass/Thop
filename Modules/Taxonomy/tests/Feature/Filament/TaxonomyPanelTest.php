<?php

namespace Modules\Taxonomy\Tests\Feature\Filament;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Taxonomy\Filament\Resources\Colors\Pages\CreateColor;
use Modules\Taxonomy\Filament\Resources\Colors\Pages\EditColor;
use Modules\Taxonomy\Filament\Resources\FabricTypes\Pages\CreateFabricType;
use Modules\Taxonomy\Filament\Resources\FabricTypes\Pages\EditFabricType;
use Modules\Taxonomy\Filament\Resources\FabricTypes\Pages\ListFabricTypes;
use Modules\Taxonomy\Models\Color;
use Modules\Taxonomy\Models\FabricType;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TaxonomyPanelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function a_non_admin_cannot_open_a_taxonomy_panel(): void
    {
        $this->actingAs(User::factory()->importer()->create())
            ->get('/admin/fabric-types')
            ->assertForbidden();
    }

    #[Test]
    public function an_admin_sees_fabric_types_in_the_list(): void
    {
        $term = FabricType::factory()->create();

        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(ListFabricTypes::class)
            ->assertOk()
            ->assertCanSeeTableRecords([$term]);
    }

    #[Test]
    public function an_admin_creates_a_fabric_type_from_the_panel(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        Livewire::test(CreateFabricType::class)
            ->fillForm(['name_ar' => 'دنيم', 'name_en' => 'Denim'])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('fabric_types', ['name_en' => 'Denim']);
        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->id,
            'action' => 'taxonomy.created',
        ]);
    }

    #[Test]
    public function creating_a_fabric_type_requires_both_names(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(CreateFabricType::class)
            ->fillForm(['name_ar' => '', 'name_en' => ''])
            ->call('create')
            ->assertHasFormErrors(['name_ar' => 'required', 'name_en' => 'required']);
    }

    #[Test]
    public function an_admin_deactivates_a_fabric_type_from_the_edit_page(): void
    {
        $term = FabricType::factory()->create(['is_active' => true]);
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        Livewire::test(EditFabricType::class, ['record' => $term->getKey()])
            ->fillForm(['is_active' => false])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertFalse($term->refresh()->is_active);
        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->id,
            'action' => 'taxonomy.deactivated',
            'auditable_id' => $term->id,
        ]);
    }

    #[Test]
    public function an_admin_creates_and_edits_a_color_with_a_hex_value(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        Livewire::test(CreateColor::class)
            ->fillForm(['name_ar' => 'كحلي', 'name_en' => 'Navy', 'hex' => '#0B1D51'])
            ->call('create')
            ->assertHasNoFormErrors();

        $color = Color::query()->where('name_en', 'Navy')->firstOrFail();
        $this->assertSame('#0b1d51', mb_strtolower((string) $color->hex));

        Livewire::test(EditColor::class, ['record' => $color->getKey()])
            ->fillForm(['hex' => '#123ABC'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('#123abc', mb_strtolower((string) $color->refresh()->hex));
    }
}
