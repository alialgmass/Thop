<?php

namespace Modules\Admin\Tests\Feature\Filament;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Admin\Filament\Resources\AuditLogs\AuditLogResource;
use Modules\Admin\Filament\Resources\AuditLogs\Pages\ListAuditLogs;
use Modules\Admin\Models\AuditLog;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AuditLogPanelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function a_non_admin_cannot_open_the_audit_log_panel(): void
    {
        $this->actingAs(User::factory()->importer()->create())
            ->get('/admin/audit-logs')
            ->assertForbidden();
    }

    #[Test]
    public function an_admin_sees_audit_entries_in_the_list(): void
    {
        $admin = User::factory()->admin()->create();
        $entry = AuditLog::record($admin, 'verification.approved', User::factory()->create());

        $this->actingAs($admin);

        Livewire::test(ListAuditLogs::class)
            ->assertOk()
            ->assertCanSeeTableRecords([$entry]);
    }

    #[Test]
    public function the_list_offers_actor_action_and_entity_type_filters(): void
    {
        $admin = User::factory()->admin()->create();
        AuditLog::record($admin, 'verification.approved', User::factory()->create());

        $this->actingAs($admin);

        Livewire::test(ListAuditLogs::class)
            ->assertTableFilterExists('action')
            ->assertTableFilterExists('actor_id')
            ->assertTableFilterExists('auditable_type');
    }

    #[Test]
    public function the_resource_exposes_no_write_actions(): void
    {
        $this->assertFalse(AuditLogResource::canCreate());
        $this->assertFalse(AuditLogResource::canEdit(new AuditLog));
        $this->assertFalse(AuditLogResource::canDelete(new AuditLog));
    }
}
