<?php

namespace Modules\Businesses\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Businesses\Enums\VerificationStatus;
use Modules\Businesses\Models\BusinessAccount;
use Modules\Taxonomy\Models\Governorate;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BusinessProfileTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'company_name' => 'Nile Textiles',
            'activity' => 'Fabric import',
            'governorate_id' => Governorate::factory()->create()->id,
            'address' => '12 El Geish St, Cairo',
            'contact_person' => 'Sara Ahmed',
            'contact_channels' => [
                ['type' => 'whatsapp', 'value' => '+201000000000'],
            ],
        ], $overrides);
    }

    #[Test]
    public function a_business_account_user_creates_a_profile_in_unverified_status(): void
    {
        $user = User::factory()->importer()->create();

        $this->actingAs($user)
            ->postJson('/api/v1/businesses', $this->validPayload())
            ->assertCreated()
            ->assertJsonPath('company_name', 'Nile Textiles')
            ->assertJsonPath('verified', false)
            ->assertJsonPath('verification_status', 'unverified');

        $this->assertDatabaseHas('business_accounts', [
            'user_id' => $user->id,
            'company_name' => 'Nile Textiles',
            'verification_status' => VerificationStatus::Unverified->value,
        ]);
    }

    #[Test]
    public function required_fields_are_validated(): void
    {
        $user = User::factory()->importer()->create();

        $this->actingAs($user)
            ->postJson('/api/v1/businesses', ['company_name' => ''])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['company_name', 'activity', 'governorate_id', 'address', 'contact_person']);
    }

    #[Test]
    public function the_governorate_must_exist_in_the_taxonomy(): void
    {
        $user = User::factory()->importer()->create();

        $this->actingAs($user)
            ->postJson('/api/v1/businesses', $this->validPayload(['governorate_id' => 999999]))
            ->assertStatus(422)
            ->assertJsonValidationErrorFor('governorate_id');
    }

    #[Test]
    public function a_user_cannot_create_a_second_profile(): void
    {
        $user = User::factory()->importer()->create();
        BusinessAccount::factory()->for($user, 'owner')->create();

        $this->actingAs($user)
            ->postJson('/api/v1/businesses', $this->validPayload())
            ->assertStatus(422)
            ->assertJsonValidationErrorFor('user_id');

        $this->assertDatabaseCount('business_accounts', 1);
    }

    #[Test]
    public function a_customer_account_cannot_create_a_profile(): void
    {
        $user = User::factory()->customer()->create();

        $this->actingAs($user)
            ->postJson('/api/v1/businesses', $this->validPayload())
            ->assertStatus(422)
            ->assertJsonValidationErrorFor('account_type');
    }

    #[Test]
    public function an_owner_can_edit_their_profile(): void
    {
        $user = User::factory()->wholesaler()->create();
        $business = BusinessAccount::factory()->for($user, 'owner')->create();

        $this->actingAs($user)
            ->patchJson("/api/v1/businesses/{$business->id}", ['company_name' => 'Renamed Co'])
            ->assertOk()
            ->assertJsonPath('company_name', 'Renamed Co');

        $this->assertSame('Renamed Co', $business->refresh()->company_name);
    }

    #[Test]
    public function a_user_cannot_edit_another_users_profile(): void
    {
        $owner = User::factory()->importer()->create();
        $business = BusinessAccount::factory()->for($owner, 'owner')->create();
        $intruder = User::factory()->retailer()->create();

        $this->actingAs($intruder)
            ->patchJson("/api/v1/businesses/{$business->id}", ['company_name' => 'Hijacked'])
            ->assertForbidden();
    }

    #[Test]
    public function a_non_owner_sees_only_the_public_subset(): void
    {
        $owner = User::factory()->importer()->create();
        $business = BusinessAccount::factory()->for($owner, 'owner')->create();
        $viewer = User::factory()->wholesaler()->create();

        $this->actingAs($viewer)
            ->getJson("/api/v1/businesses/{$business->id}")
            ->assertOk()
            ->assertJsonMissingPath('address')
            ->assertJsonMissingPath('contact_person')
            ->assertJsonPath('verified', false);
    }

    #[Test]
    public function an_admin_can_view_and_update_any_profile(): void
    {
        Role::findOrCreate('admin');
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $business = BusinessAccount::factory()->create();

        $this->actingAs($admin)
            ->getJson("/api/v1/businesses/{$business->id}")
            ->assertOk()
            ->assertJsonPath('contact_person', $business->contact_person);

        $this->actingAs($admin)
            ->patchJson("/api/v1/businesses/{$business->id}", ['activity' => 'Reviewed activity'])
            ->assertOk();
    }

    #[Test]
    public function the_verified_flag_tracks_the_verification_status_column(): void
    {
        $business = BusinessAccount::factory()->verified()->create();

        $this->actingAs($business->owner)
            ->getJson("/api/v1/businesses/{$business->id}")
            ->assertOk()
            ->assertJsonPath('verified', true);
    }

    #[Test]
    public function the_endpoints_require_authentication(): void
    {
        $this->postJson('/api/v1/businesses', $this->validPayload())->assertUnauthorized();
    }
}
