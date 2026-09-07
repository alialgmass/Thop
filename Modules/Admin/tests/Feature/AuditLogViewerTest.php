<?php

namespace Modules\Admin\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Admin\Models\AuditLog;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AuditLogViewerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function an_admin_lists_audit_log_entries_newest_first(): void
    {
        $actor = User::factory()->admin()->create();
        $target = User::factory()->create();

        AuditLog::record($actor, 'verification.approved', $target);
        AuditLog::record($actor, 'verification.rejected', $target, ['reason' => 'blurry']);

        $this->actingAs($actor)
            ->getJson('/api/v1/admin/audit-logs')
            ->assertOk()
            ->assertJsonCount(2, 'body.audit_logs.data')
            ->assertJsonPath('body.audit_logs.data.0.action', 'verification.rejected')
            ->assertJsonPath('body.audit_logs.data.0.metadata.reason', 'blurry');
    }

    #[Test]
    public function the_list_can_be_filtered_by_actor_action_and_entity(): void
    {
        $admin = User::factory()->admin()->create();
        $other = User::factory()->admin()->create();
        $target = User::factory()->create();

        AuditLog::record($admin, 'verification.approved', $target);
        AuditLog::record($other, 'product.approved', $target);

        $this->actingAs($admin)
            ->getJson('/api/v1/admin/audit-logs?action=product.approved')
            ->assertOk()
            ->assertJsonCount(1, 'body.audit_logs.data')
            ->assertJsonPath('body.audit_logs.data.0.actor_id', $other->id);

        $this->actingAs($admin)
            ->getJson('/api/v1/admin/audit-logs?actor_id='.$admin->id)
            ->assertJsonCount(1, 'body.audit_logs.data')
            ->assertJsonPath('body.audit_logs.data.0.action', 'verification.approved');

        $this->actingAs($admin)
            ->getJson('/api/v1/admin/audit-logs?auditable_type='.urlencode($target->getMorphClass()).'&auditable_id='.$target->id)
            ->assertJsonCount(2, 'body.audit_logs.data');
    }
}
