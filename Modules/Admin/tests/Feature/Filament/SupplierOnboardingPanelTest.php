<?php

namespace Modules\Admin\Tests\Feature\Filament;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Admin\Filament\Resources\SupplierOnboardings\Pages\CreateOnboardedSupplier;
use Modules\Admin\Filament\Resources\SupplierOnboardings\Pages\ListOnboardedSuppliers;
use Modules\Businesses\Models\BusinessAccount;
use Modules\Taxonomy\Models\Governorate;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Filament parity for assisted supplier onboarding (Phase 9 · T10, issue
 * #39) — mirrors AdminBusinessOnboardingTest's REST coverage.
 */
class SupplierOnboardingPanelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function a_non_admin_cannot_open_the_onboarding_panel(): void
    {
        $this->actingAs(User::factory()->importer()->create())
            ->get('/admin/supplier-onboardings')
            ->assertForbidden();
    }

    #[Test]
    public function an_admin_sees_only_admin_onboarded_businesses_in_the_list(): void
    {
        $onboarded = BusinessAccount::factory()->create(['onboarded_by_admin' => true]);
        BusinessAccount::factory()->create(['onboarded_by_admin' => false]);

        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(ListOnboardedSuppliers::class)
            ->assertOk()
            ->assertCanSeeTableRecords([$onboarded])
            ->assertCountTableRecords(1);
    }

    #[Test]
    public function an_admin_onboards_a_supplier_through_the_wizard(): void
    {
        $governorate = Governorate::factory()->create();
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        Livewire::test(CreateOnboardedSupplier::class)
            ->fillForm([
                'phone' => '01098765432',
                'account_type' => 'wholesaler',
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!',
                'company_name' => 'Delta Fabrics',
                'activity' => 'Wholesale distribution',
                'governorate_id' => $governorate->id,
                'address' => '45 Market Street',
                'contact_person' => 'Karim Youssef',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $business = BusinessAccount::query()->where('company_name', 'Delta Fabrics')->firstOrFail();
        $this->assertTrue($business->onboarded_by_admin);
        $this->assertSame('+201098765432', $business->owner->phone);
        $this->assertSame('wholesaler', $business->owner->account_type->value);
        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->id, 'action' => 'supplier.onboarded', 'auditable_id' => $business->id,
        ]);
    }

    #[Test]
    public function a_duplicate_phone_shows_an_error_instead_of_creating_a_second_account(): void
    {
        User::factory()->create(['phone' => '+201098765432']);
        $governorate = Governorate::factory()->create();

        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(CreateOnboardedSupplier::class)
            ->fillForm([
                'phone' => '01098765432',
                'account_type' => 'importer',
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!',
                'company_name' => 'Delta Fabrics',
                'activity' => 'Wholesale distribution',
                'governorate_id' => $governorate->id,
                'address' => '45 Market Street',
                'contact_person' => 'Karim Youssef',
            ])
            ->call('create');

        $this->assertDatabaseCount('business_accounts', 0);
    }
}
