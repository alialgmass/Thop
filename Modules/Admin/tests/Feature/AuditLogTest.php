<?php

namespace Modules\Admin\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Modules\Admin\Models\AuditLog;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function record_appends_one_entry_with_actor_action_and_target(): void
    {
        $actor = User::factory()->create();
        $target = User::factory()->create();

        $entry = AuditLog::record($actor, 'verification.approved', $target, ['reason' => null]);

        $this->assertDatabaseCount('audit_logs', 1);
        $this->assertSame($actor->id, $entry->actor_id);
        $this->assertSame('verification.approved', $entry->action);
        $this->assertSame($target->getMorphClass(), $entry->auditable_type);
        $this->assertSame($target->id, $entry->auditable_id);
        $this->assertSame(['reason' => null], $entry->metadata);
    }

    #[Test]
    public function empty_metadata_is_stored_as_null(): void
    {
        $actor = User::factory()->create();

        $entry = AuditLog::record($actor, 'account.suspended', $actor);

        $this->assertNull($entry->fresh()->metadata);
    }

    #[Test]
    public function the_table_has_no_updated_at_column(): void
    {
        $this->assertFalse(Schema::hasColumn('audit_logs', 'updated_at'));
        $this->assertTrue(Schema::hasColumn('audit_logs', 'created_at'));
    }

    #[Test]
    public function entries_cannot_be_updated(): void
    {
        $actor = User::factory()->create();
        $entry = AuditLog::record($actor, 'verification.approved', $actor);

        $this->expectException(RuntimeException::class);

        $entry->update(['action' => 'tampered']);
    }

    #[Test]
    public function entries_cannot_be_deleted(): void
    {
        $actor = User::factory()->create();
        $entry = AuditLog::record($actor, 'verification.approved', $actor);

        $this->expectException(RuntimeException::class);

        $entry->delete();
    }
}
