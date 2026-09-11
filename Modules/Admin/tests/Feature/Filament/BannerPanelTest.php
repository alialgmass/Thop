<?php

namespace Modules\Admin\Tests\Feature\Filament;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Modules\Admin\Filament\Resources\Banners\Pages\CreateBanner;
use Modules\Admin\Filament\Resources\Banners\Pages\EditBanner;
use Modules\Admin\Filament\Resources\Banners\Pages\ListBanners;
use Modules\Admin\Models\Banner;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BannerPanelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    #[Test]
    public function a_non_admin_cannot_open_the_banners_panel(): void
    {
        $this->actingAs(User::factory()->importer()->create())
            ->get('/admin/banners')
            ->assertForbidden();
    }

    #[Test]
    public function an_admin_sees_banners_in_the_list(): void
    {
        $banner = Banner::factory()->create();

        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(ListBanners::class)
            ->assertOk()
            ->assertCanSeeTableRecords([$banner]);
    }

    #[Test]
    public function an_admin_creates_a_banner_from_the_panel(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        Livewire::test(CreateBanner::class)
            ->fillForm([
                'image' => UploadedFile::fake()->image('banner.jpg'),
                'position' => 3,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $banner = Banner::query()->first();
        $this->assertNotNull($banner);
        $this->assertSame(3, $banner->position);
        Storage::disk('public')->assertExists($banner->image_path);
        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->id, 'action' => 'banner.created', 'auditable_id' => $banner->id,
        ]);
    }

    #[Test]
    public function an_admin_edits_a_banner_from_the_panel(): void
    {
        $banner = Banner::factory()->create(['position' => 1]);
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        Livewire::test(EditBanner::class, ['record' => $banner->getKey()])
            ->fillForm(['position' => 9])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(9, $banner->refresh()->position);
        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->id, 'action' => 'banner.updated', 'auditable_id' => $banner->id,
        ]);
    }

    #[Test]
    public function editing_a_banner_without_a_new_image_keeps_the_old_file(): void
    {
        $banner = Banner::factory()->create();
        Storage::disk('public')->put($banner->image_path, 'fake-bytes');
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        Livewire::test(EditBanner::class, ['record' => $banner->getKey()])
            ->fillForm(['position' => 4])
            ->call('save')
            ->assertHasNoFormErrors();

        Storage::disk('public')->assertExists($banner->image_path);
    }

    #[Test]
    public function replacing_a_banners_image_from_the_panel_deletes_the_old_file(): void
    {
        $banner = Banner::factory()->create();
        Storage::disk('public')->put($banner->image_path, 'fake-bytes');
        $oldPath = $banner->image_path;
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        Livewire::test(EditBanner::class, ['record' => $banner->getKey()])
            ->fillForm(['image' => UploadedFile::fake()->image('new.jpg')])
            ->call('save')
            ->assertHasNoFormErrors();

        $banner->refresh();
        $this->assertNotSame($oldPath, $banner->image_path);
        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists($banner->image_path);
    }

    #[Test]
    public function an_admin_removes_a_banner_from_the_panel(): void
    {
        $banner = Banner::factory()->create();
        Storage::disk('public')->put($banner->image_path, 'fake-bytes');
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        Livewire::test(ListBanners::class)
            ->callTableAction('remove', $banner);

        $this->assertDatabaseMissing('banners', ['id' => $banner->id]);
        Storage::disk('public')->assertMissing($banner->image_path);
        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->id, 'action' => 'banner.removed',
        ]);
    }
}
