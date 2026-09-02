<?php

namespace Modules\Taxonomy\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Taxonomy\Database\Seeders\TaxonomyDatabaseSeeder;
use Modules\Taxonomy\Models\Governorate;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TaxonomyEndpointsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function governorates_endpoint_returns_the_seeded_egyptian_governorates(): void
    {
        $this->seed(TaxonomyDatabaseSeeder::class);

        $response = $this->getJson('/api/v1/taxonomy/governorates')
            ->assertOk()
            ->assertJsonCount(27, 'body.governorates')
            ->assertJsonStructure(['body' => ['governorates' => [['id', 'slug', 'name', 'name_ar', 'name_en']]]]);

        $this->assertcontains('Cairo', array_column($response->json('body.governorates'), 'name_en'));
    }

    #[Test]
    public function every_taxonomy_endpoint_is_public_and_returns_bilingual_terms(): void
    {
        $this->seed(TaxonomyDatabaseSeeder::class);

        foreach (['governorates', 'fabric-types', 'materials', 'colors', 'units'] as $list) {
            $key = $list === 'fabric-types' ? 'fabric_types' : $list;
            $rows = $this->getJson("/api/v1/taxonomy/{$list}")->assertOk()->json("body.{$key}");

            $this->assertNotEmpty($rows, "{$list} should not be empty");
            $this->assertArrayHasKey('name_ar', $rows[0]);
            $this->assertArrayHasKey('name_en', $rows[0]);
        }
    }

    #[Test]
    public function colors_carry_a_hex_value(): void
    {
        $this->seed(TaxonomyDatabaseSeeder::class);

        $this->getJson('/api/v1/taxonomy/colors')
            ->assertOk()
            ->assertJsonFragment(['slug' => 'white', 'hex' => '#FFFFFF']);
    }

    #[Test]
    public function the_localized_name_follows_the_request_locale(): void
    {
        Governorate::query()->create(['slug' => 'cairo', 'name_ar' => 'القاهرة', 'name_en' => 'Cairo']);

        $this->app->setLocale('ar');

        $this->getJson('/api/v1/taxonomy/governorates')
            ->assertOk()
            ->assertJsonPath('body.governorates.0.name', 'القاهرة');
    }

    #[Test]
    public function taxonomy_lists_are_read_only(): void
    {
        $this->postJson('/api/v1/taxonomy/governorates', ['name_en' => 'Nowhere'])->assertStatus(405);
        $this->patchJson('/api/v1/taxonomy/colors', [])->assertStatus(405);
        $this->deleteJson('/api/v1/taxonomy/units')->assertStatus(405);
    }
}
