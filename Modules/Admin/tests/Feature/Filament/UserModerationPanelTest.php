<?php

namespace Modules\Admin\Tests\Feature\Filament;

use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Admin\Tests\Feature\AdminAccountModerationTest;
use Modules\Auth\Enums\UserStatus;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Suspend/reactivate row actions on the Users panel (Phase 9 · T8, issue
 * #37) — Filament parity with {@see AdminAccountModerationTest}.
 */
class UserModerationPanelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function an_admin_suspends_an_account_from_the_panel(): void
    {
        $target = User::factory()->importer()->create();
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        Livewire::test(ListUsers::class)
            ->callTableAction('suspend', $target, ['confirm' => true]);

        $this->assertSame(UserStatus::Suspended, $target->refresh()->status);
        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->id, 'action' => 'account.suspended', 'auditable_id' => $target->id,
        ]);
    }

    #[Test]
    public function the_suspend_action_is_hidden_for_admin_accounts(): void
    {
        $target = User::factory()->admin()->create();

        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(ListUsers::class)
            ->assertTableActionHidden('suspend', $target);
    }

    #[Test]
    public function an_admin_reactivates_a_suspended_account_from_the_panel(): void
    {
        $target = User::factory()->importer()->create(['status' => UserStatus::Suspended]);
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        Livewire::test(ListUsers::class)
            ->callTableAction('reactivate', $target);

        $this->assertSame(UserStatus::Active, $target->refresh()->status);
        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->id, 'action' => 'account.reactivated', 'auditable_id' => $target->id,
        ]);
    }

    #[Test]
    public function the_reactivate_action_is_hidden_for_a_non_suspended_account(): void
    {
        $target = User::factory()->importer()->create();

        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(ListUsers::class)
            ->assertTableActionHidden('reactivate', $target);
    }
}
