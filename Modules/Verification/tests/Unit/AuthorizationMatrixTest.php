<?php

namespace Modules\Verification\Tests\Unit;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Businesses\Enums\VerificationStatus;
use Modules\Businesses\Models\BusinessAccount;
use Modules\Businesses\Policies\BusinessPolicy;
use Modules\Verification\Models\DocumentType;
use Modules\Verification\Models\VerificationRequest;
use Modules\Verification\Policies\VerificationPolicy;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Row-by-row assertion of the Spec Section 8 matrix for the two Phase 1
 * policies. The correct actor is allowed; every other actor is denied.
 */
class AuthorizationMatrixTest extends TestCase
{
    use RefreshDatabase;

    private BusinessPolicy $business;

    private VerificationPolicy $verification;

    protected function setUp(): void
    {
        parent::setUp();
        $this->business = new BusinessPolicy;
        $this->verification = new VerificationPolicy;
    }

    #[Test]
    public function own_business_profile_row(): void
    {
        $owner = User::factory()->importer()->create();
        $profile = BusinessAccount::factory()->for($owner, 'owner')->create();
        $otherBusinessUser = User::factory()->wholesaler()->create();
        $customer = User::factory()->customer()->create();
        $admin = User::factory()->admin()->create();

        // create: business-type users with no profile; not customers; not owner (already has one)
        $this->assertTrue($this->business->create($otherBusinessUser));
        $this->assertFalse($this->business->create($customer));
        $this->assertFalse($this->business->create($owner));

        // view / update: owner yes, other business user no, customer no
        foreach (['view', 'update'] as $ability) {
            $this->assertTrue($this->business->{$ability}($owner, $profile));
            $this->assertFalse($this->business->{$ability}($otherBusinessUser, $profile));
            $this->assertFalse($this->business->{$ability}($customer, $profile));
        }

        // admin: R/U via before()
        $this->assertTrue($this->business->before($admin, 'view'));
        $this->assertTrue($this->business->before($admin, 'update'));
        $this->assertNull($this->business->before($admin, 'delete'));
    }

    #[Test]
    public function verification_request_row(): void
    {
        $owner = User::factory()->importer()->create();
        $business = BusinessAccount::factory()->for($owner, 'owner')->create();
        $request = VerificationRequest::factory()->for($business)->create();
        $document = $request->documents()->create([
            'document_type_id' => DocumentType::factory()->create()->id,
            'disk' => 'verification', 'path' => 'x/y.pdf', 'mime_type' => 'application/pdf',
            'size' => 10, 'original_name' => 'y.pdf',
        ]);
        $stranger = User::factory()->wholesaler()->create();
        $customer = User::factory()->customer()->create();
        $admin = User::factory()->admin()->create();

        // upload / submit: owner only
        $this->assertTrue($this->verification->upload($owner, $business));
        $this->assertFalse($this->verification->upload($stranger, $business));
        $this->assertTrue($this->verification->submit($owner, $business));
        $this->assertFalse($this->verification->submit($customer, $business));

        // upload denied once verified
        $business->verification_status = VerificationStatus::Verified;
        $this->assertFalse($this->verification->upload($owner, $business));
        $business->verification_status = VerificationStatus::Unverified;

        // viewStatus / download: owner + admin, nobody else
        $this->assertTrue($this->verification->viewStatus($owner, $business));
        $this->assertTrue($this->verification->viewStatus($admin, $business));
        $this->assertFalse($this->verification->viewStatus($stranger, $business));

        $this->assertTrue($this->verification->download($owner, $document));
        $this->assertTrue($this->verification->download($admin, $document));
        $this->assertFalse($this->verification->download($stranger, $document));

        // review: admin only
        $this->assertTrue($this->verification->review($admin, $request));
        $this->assertFalse($this->verification->review($owner, $request));
        $this->assertFalse($this->verification->review($stranger, $request));
    }
}
