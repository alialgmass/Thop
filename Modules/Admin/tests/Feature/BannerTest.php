<?php

namespace Modules\Admin\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Admin\Models\Banner;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Public read of homepage banners for the separate marketplace client
 * (Phase 9 · T6, issue #35) — management is Filament-only, see
 * Modules/Admin/tests/Feature/Filament/BannerPanelTest.php.
 */
class BannerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_returns_only_currently_active_banners_ordered_by_position(): void
    {
        $second = Banner::factory()->create(['position' => 2]);
        $first = Banner::factory()->create(['position' => 1]);
        Banner::factory()->inactive()->create(['position' => 0]);
        Banner::factory()->expired()->create(['position' => 0]);
        Banner::factory()->notYetStarted()->create(['position' => 0]);

        $response = $this->getJson('/api/v1/banners')->assertOk();

        $ids = collect($response->json('body.banners'))->pluck('id')->all();
        $this->assertSame([$first->id, $second->id], $ids);
    }

    #[Test]
    public function it_requires_no_authentication(): void
    {
        Banner::factory()->create();

        $this->getJson('/api/v1/banners')->assertOk();
    }

    #[Test]
    public function it_exposes_only_the_client_facing_fields(): void
    {
        $banner = Banner::factory()->create(['link_url' => 'https://example.com/sale']);

        $this->getJson('/api/v1/banners')
            ->assertOk()
            ->assertJsonFragment([
                'id' => $banner->id,
                'link_url' => 'https://example.com/sale',
                'position' => $banner->position,
            ])
            ->assertJsonMissingPath('body.banners.0.is_active')
            ->assertJsonMissingPath('body.banners.0.created_by');
    }
}
