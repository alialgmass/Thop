<?php

namespace Modules\Taxonomy\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Catalog\Support\ProductValidationRules;
use Modules\Taxonomy\Models\Color;
use Modules\Taxonomy\Models\FabricType;
use Modules\Taxonomy\Models\Material;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Admin CRUD over the controlled reference lists (Phase 9 · T3, US-ADM-03).
 */
class AdminTaxonomyTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function a_non_admin_cannot_reach_taxonomy_management(): void
    {
        $this->actingAs(User::factory()->importer()->create())
            ->getJson('/api/v1/admin/taxonomy/fabric-types')
            ->assertForbidden();
    }

    #[Test]
    public function an_admin_lists_terms_of_a_type_including_inactive_ones(): void
    {
        $active = FabricType::factory()->create();
        $inactive = FabricType::factory()->create(['is_active' => false]);

        $this->actingAs(User::factory()->admin()->create())
            ->getJson('/api/v1/admin/taxonomy/fabric-types')
            ->assertOk()
            ->assertJsonFragment(['id' => $active->id])
            ->assertJsonFragment(['id' => $inactive->id]);
    }

    #[Test]
    public function an_unknown_taxonomy_type_404s(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->getJson('/api/v1/admin/taxonomy/not-a-real-type')
            ->assertNotFound();
    }

    #[Test]
    public function an_admin_creates_a_term_with_a_bilingual_name(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)
            ->postJson('/api/v1/admin/taxonomy/materials', [
                'name_ar' => 'دنيم',
                'name_en' => 'Denim',
            ])
            ->assertOk();

        $this->assertDatabaseHas('materials', [
            'name_ar' => 'دنيم',
            'name_en' => 'Denim',
            'slug' => 'denim',
            'is_active' => true,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->id,
            'action' => 'taxonomy.created',
            'auditable_type' => Material::class,
        ]);
    }

    #[Test]
    public function creating_a_term_requires_both_names(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->postJson('/api/v1/admin/taxonomy/materials', ['name_ar' => 'دنيم'])
            ->assertStatus(400);

        $this->assertDatabaseCount('materials', 0);
    }

    #[Test]
    public function a_second_term_with_a_colliding_english_name_gets_a_unique_slug(): void
    {
        FabricType::factory()->create(['name_en' => 'Cotton', 'slug' => 'cotton']);
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->postJson('/api/v1/admin/taxonomy/fabric-types', ['name_ar' => 'قطن ٢', 'name_en' => 'Cotton'])
            ->assertOk();

        $this->assertDatabaseHas('fabric_types', ['name_en' => 'Cotton', 'slug' => 'cotton-2']);
    }

    #[Test]
    public function an_admin_edits_a_terms_names(): void
    {
        $term = FabricType::factory()->create(['name_en' => 'Cotton']);
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->patchJson("/api/v1/admin/taxonomy/fabric-types/{$term->id}", ['name_en' => 'Cotton (Egyptian)'])
            ->assertOk();

        $this->assertSame('Cotton (Egyptian)', $term->refresh()->name_en);
        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->id,
            'action' => 'taxonomy.updated',
            'auditable_id' => $term->id,
        ]);
    }

    #[Test]
    public function deactivating_a_term_is_audited_as_a_deactivation_not_a_generic_update(): void
    {
        $term = FabricType::factory()->create(['is_active' => true]);
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->patchJson("/api/v1/admin/taxonomy/fabric-types/{$term->id}", ['is_active' => false])
            ->assertOk();

        $this->assertFalse($term->refresh()->is_active);
        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->id,
            'action' => 'taxonomy.deactivated',
            'auditable_id' => $term->id,
        ]);
        $this->assertDatabaseMissing('audit_logs', [
            'actor_id' => $admin->id,
            'action' => 'taxonomy.updated',
            'auditable_id' => $term->id,
        ]);
    }

    #[Test]
    public function reactivating_a_term_is_audited_as_a_generic_update(): void
    {
        $term = FabricType::factory()->create(['is_active' => false]);
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->patchJson("/api/v1/admin/taxonomy/fabric-types/{$term->id}", ['is_active' => true])
            ->assertOk();

        $this->assertTrue($term->refresh()->is_active);
        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->id,
            'action' => 'taxonomy.updated',
            'auditable_id' => $term->id,
        ]);
    }

    /**
     * Both product-creation forms and search filter dropdowns source their
     * term lists from this same public taxonomy read endpoint — there is no
     * separate "available filters" endpoint in Search (confirmed: no facet/
     * filter-options code exists there) — so this one test covers both
     * halves of the "disappears from new product forms and search filters"
     * acceptance criterion.
     */
    #[Test]
    public function a_deactivated_term_disappears_from_the_public_read_endpoint(): void
    {
        $term = FabricType::factory()->create(['is_active' => true]);
        $this->actingAs(User::factory()->admin()->create())
            ->patchJson("/api/v1/admin/taxonomy/fabric-types/{$term->id}", ['is_active' => false])
            ->assertOk();

        $this->getJson('/api/v1/taxonomy/fabric-types')
            ->assertOk()
            ->assertJsonMissing(['id' => $term->id]);
    }

    #[Test]
    public function a_deactivated_term_is_rejected_by_product_creation_validation(): void
    {
        $term = FabricType::factory()->create(['is_active' => true]);
        $this->actingAs(User::factory()->admin()->create())
            ->patchJson("/api/v1/admin/taxonomy/fabric-types/{$term->id}", ['is_active' => false]);

        $rules = ProductValidationRules::fields(false);

        $this->assertFalse(
            validator(['fabric_type_id' => $term->id], ['fabric_type_id' => $rules['fabric_type_id']])->passes(),
        );
    }

    #[Test]
    public function a_color_can_be_created_and_edited_with_a_hex_value(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->postJson('/api/v1/admin/taxonomy/colors', [
                'name_ar' => 'أزرق ملكي', 'name_en' => 'Royal Blue', 'hex' => '#1E3A8A',
            ])
            ->assertOk();

        $color = Color::query()->where('name_en', 'Royal Blue')->firstOrFail();
        $this->assertSame('#1E3A8A', $color->hex);

        $this->actingAs($admin)
            ->patchJson("/api/v1/admin/taxonomy/colors/{$color->id}", ['hex' => '#0F172A'])
            ->assertOk();

        $this->assertSame('#0F172A', $color->refresh()->hex);
    }

    #[Test]
    public function hex_is_ignored_for_non_color_term_types(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->postJson('/api/v1/admin/taxonomy/materials', [
                'name_ar' => 'دنيم', 'name_en' => 'Denim', 'hex' => '#123456',
            ])
            ->assertOk();

        $this->assertDatabaseHas('materials', ['name_en' => 'Denim']);
    }
}
