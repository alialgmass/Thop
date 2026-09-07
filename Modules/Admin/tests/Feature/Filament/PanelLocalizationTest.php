<?php

namespace Modules\Admin\Tests\Feature\Filament;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Admin\Filament\Resources\AuditLogs\AuditLogResource;
use Modules\Admin\Filament\Resources\AuditLogs\Pages\ListAuditLogs;
use Modules\Subscriptions\Filament\Resources\Subscriptions\SubscriptionResource;
use Modules\Verification\Filament\Resources\VerificationRequests\VerificationRequestResource;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PanelLocalizationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function navigation_groups_and_resource_labels_resolve_in_arabic(): void
    {
        $this->app->setLocale('ar');

        $this->assertSame('النظام', __('panel.nav.system'));
        $this->assertSame('المراجعة', __('panel.nav.moderation'));
        $this->assertSame('الاشتراكات', __('panel.nav.billing'));
        $this->assertSame('الصلاحيات', __('panel.nav.access_control'));

        $this->assertSame('سجل التدقيق', AuditLogResource::getNavigationLabel());
        $this->assertSame('النظام', AuditLogResource::getNavigationGroup());
        $this->assertSame('المراجعة', VerificationRequestResource::getNavigationGroup());
        $this->assertSame('الاشتراكات', SubscriptionResource::getNavigationGroup());
    }

    #[Test]
    public function the_audit_log_page_renders_for_an_admin_in_arabic(): void
    {
        $this->app->setLocale('ar');

        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(ListAuditLogs::class)->assertOk();
    }

    #[Test]
    public function english_labels_still_resolve(): void
    {
        $this->app->setLocale('en');

        $this->assertSame('System', __('panel.nav.system'));
        $this->assertSame('Audit log', AuditLogResource::getNavigationLabel());
    }
}
