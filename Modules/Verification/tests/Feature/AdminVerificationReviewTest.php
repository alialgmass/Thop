<?php

namespace Modules\Verification\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Modules\Businesses\Enums\VerificationStatus;
use Modules\Businesses\Models\BusinessAccount;
use Modules\Verification\Enums\VerificationRequestStatus;
use Modules\Verification\Events\VerificationApproved;
use Modules\Verification\Events\VerificationRejected;
use Modules\Verification\Models\DocumentType;
use Modules\Verification\Models\VerificationRequest;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdminVerificationReviewTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $owner;

    private BusinessAccount $business;

    private DocumentType $type;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('verification');

        $this->admin = User::factory()->admin()->create();
        $this->owner = User::factory()->importer()->create();
        $this->business = BusinessAccount::factory()->for($this->owner, 'owner')->create();
        $this->type = DocumentType::factory()->create(['slug' => 'commercial_register']);
    }

    private function submitRequest(): VerificationRequest
    {
        $this->actingAs($this->owner)->postJson(
            "/api/v1/businesses/{$this->business->id}/verification-documents",
            ['document_type_id' => $this->type->id, 'file' => UploadedFile::fake()->create('cr.pdf', 100, 'application/pdf')],
        )->assertCreated();

        $this->actingAs($this->owner)
            ->postJson("/api/v1/businesses/{$this->business->id}/verification-request")
            ->assertOk();

        return $this->business->verificationRequests()->latest('id')->firstOrFail();
    }

    #[Test]
    public function an_admin_sees_the_pending_queue(): void
    {
        $this->submitRequest();

        $this->actingAs($this->admin)
            ->getJson('/api/v1/admin/verification-requests')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.business_account_id', $this->business->id);
    }

    #[Test]
    public function approving_verifies_the_business_writes_an_audit_row_and_fires_the_event(): void
    {
        Event::fake([VerificationApproved::class]);
        $request = $this->submitRequest();

        $this->actingAs($this->admin)
            ->postJson("/api/v1/admin/verification-requests/{$request->id}/approve")
            ->assertOk()
            ->assertJsonPath('status', 'approved');

        $request->refresh();
        $this->assertSame(VerificationRequestStatus::Approved, $request->status);
        $this->assertSame($this->admin->id, $request->reviewed_by);
        $this->assertNotNull($request->reviewed_at);
        $this->assertSame(VerificationStatus::Verified, $this->business->refresh()->verification_status);

        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $this->admin->id,
            'action' => 'verification.approved',
            'auditable_id' => $request->id,
        ]);
        Event::assertDispatched(VerificationApproved::class);
    }

    #[Test]
    public function the_verified_badge_shows_after_approval(): void
    {
        $request = $this->submitRequest();
        $this->actingAs($this->admin)->postJson("/api/v1/admin/verification-requests/{$request->id}/approve")->assertOk();

        $this->actingAs($this->owner)
            ->getJson("/api/v1/businesses/{$this->business->id}")
            ->assertOk()
            ->assertJsonPath('verified', true);
    }

    #[Test]
    public function rejecting_requires_a_reason(): void
    {
        $request = $this->submitRequest();

        $this->actingAs($this->admin)
            ->postJson("/api/v1/admin/verification-requests/{$request->id}/reject", [])
            ->assertStatus(422)
            ->assertJsonValidationErrorFor('reason');
    }

    #[Test]
    public function rejecting_records_the_reason_writes_an_audit_row_and_fires_the_event(): void
    {
        Event::fake([VerificationRejected::class]);
        $request = $this->submitRequest();

        $this->actingAs($this->admin)
            ->postJson("/api/v1/admin/verification-requests/{$request->id}/reject", [
                'reason' => 'Tax card is unreadable.',
            ])
            ->assertOk()
            ->assertJsonPath('status', 'rejected')
            ->assertJsonPath('rejection_reason', 'Tax card is unreadable.');

        $this->assertSame(VerificationStatus::Rejected, $this->business->refresh()->verification_status);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'verification.rejected',
            'auditable_id' => $request->id,
        ]);
        Event::assertDispatched(VerificationRejected::class);
    }

    #[Test]
    public function re_uploading_after_rejection_returns_the_business_to_pending(): void
    {
        $request = $this->submitRequest();
        $this->actingAs($this->admin)->postJson("/api/v1/admin/verification-requests/{$request->id}/reject", [
            'reason' => 'Please re-scan the document.',
        ])->assertOk();

        $this->actingAs($this->owner)->postJson(
            "/api/v1/businesses/{$this->business->id}/verification-documents",
            ['document_type_id' => $this->type->id, 'file' => UploadedFile::fake()->create('cr2.pdf', 100, 'application/pdf')],
        )->assertCreated();

        $this->actingAs($this->owner)
            ->postJson("/api/v1/businesses/{$this->business->id}/verification-request")
            ->assertOk()
            ->assertJsonPath('verification_status', 'pending');

        $this->assertSame(2, $this->business->verificationRequests()->count());
    }

    #[Test]
    public function a_non_admin_cannot_reach_the_admin_endpoints(): void
    {
        $request = $this->submitRequest();

        $this->actingAs($this->owner)->getJson('/api/v1/admin/verification-requests')->assertForbidden();
        $this->actingAs($this->owner)->postJson("/api/v1/admin/verification-requests/{$request->id}/approve")->assertForbidden();
        $this->actingAs($this->owner)->postJson("/api/v1/admin/verification-requests/{$request->id}/reject", [
            'reason' => 'nope',
        ])->assertForbidden();

        $this->assertDatabaseCount('audit_logs', 0);
    }

    #[Test]
    public function an_admin_can_download_any_business_document(): void
    {
        $request = $this->submitRequest();
        $document = $request->documents()->firstOrFail();

        $url = URL::temporarySignedRoute('api.verification.documents.download', now()->addMinutes(5), [
            'business' => $this->business->id,
            'document' => $document->id,
        ]);

        $this->actingAs($this->admin)->get($url)->assertOk();
    }

    #[Test]
    public function an_already_decided_request_cannot_be_re_reviewed(): void
    {
        $request = $this->submitRequest();
        $this->actingAs($this->admin)->postJson("/api/v1/admin/verification-requests/{$request->id}/approve")->assertOk();

        $this->actingAs($this->admin)
            ->postJson("/api/v1/admin/verification-requests/{$request->id}/reject", ['reason' => 'changed my mind'])
            ->assertStatus(409);

        $this->assertDatabaseCount('audit_logs', 1);
    }
}
