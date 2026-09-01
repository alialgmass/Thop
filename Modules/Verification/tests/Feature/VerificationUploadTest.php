<?php

namespace Modules\Verification\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Modules\Businesses\Enums\VerificationStatus;
use Modules\Businesses\Models\BusinessAccount;
use Modules\Verification\Enums\VerificationRequestStatus;
use Modules\Verification\Events\VerificationSubmitted;
use Modules\Verification\Models\DocumentType;
use Modules\Verification\Models\VerificationDocument;
use Modules\Verification\Models\VerificationRequest;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class VerificationUploadTest extends TestCase
{
    use RefreshDatabase;

    private BusinessAccount $business;

    private User $owner;

    private DocumentType $type;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('verification');

        $this->owner = User::factory()->importer()->create();
        $this->business = BusinessAccount::factory()->for($this->owner, 'owner')->create();
        $this->type = DocumentType::factory()->create(['slug' => 'commercial_register']);
    }

    private function pdf(): UploadedFile
    {
        return UploadedFile::fake()->create('commercial-register.pdf', 200, 'application/pdf');
    }

    #[Test]
    public function an_owner_uploads_a_valid_document_and_it_lands_on_the_private_disk(): void
    {
        $this->actingAs($this->owner)
            ->postJson("/api/v1/businesses/{$this->business->id}/verification-documents", [
                'document_type_id' => $this->type->id,
                'file' => $this->pdf(),
            ])
            ->assertCreated()
            ->assertJsonPath('original_name', 'commercial-register.pdf');

        $this->assertDatabaseCount('verification_documents', 1);
        $document = VerificationDocument::first();
        Storage::disk('verification')->assertExists($document->path);
        $this->assertStringNotContainsString('commercial-register', $document->path);
    }

    #[Test]
    public function a_file_with_a_disallowed_type_is_rejected_and_nothing_is_written(): void
    {
        $this->actingAs($this->owner)
            ->postJson("/api/v1/businesses/{$this->business->id}/verification-documents", [
                'document_type_id' => $this->type->id,
                'file' => UploadedFile::fake()->create('malware.exe', 100),
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrorFor('file');

        $this->assertDatabaseCount('verification_documents', 0);
        $this->assertEmpty(Storage::disk('verification')->allFiles());
    }

    #[Test]
    public function an_oversized_file_is_rejected(): void
    {
        config(['verification.max_file_size_kb' => 50]);

        $this->actingAs($this->owner)
            ->postJson("/api/v1/businesses/{$this->business->id}/verification-documents", [
                'document_type_id' => $this->type->id,
                'file' => UploadedFile::fake()->create('big.pdf', 500, 'application/pdf'),
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrorFor('file');
    }

    #[Test]
    public function submitting_moves_the_business_to_pending_and_fires_the_event(): void
    {
        Event::fake([VerificationSubmitted::class]);

        $this->actingAs($this->owner)->postJson(
            "/api/v1/businesses/{$this->business->id}/verification-documents",
            ['document_type_id' => $this->type->id, 'file' => $this->pdf()],
        )->assertCreated();

        $this->actingAs($this->owner)
            ->postJson("/api/v1/businesses/{$this->business->id}/verification-request")
            ->assertOk()
            ->assertJsonPath('verification_status', 'pending');

        $this->assertSame(VerificationStatus::Pending, $this->business->refresh()->verification_status);
        Event::assertDispatched(VerificationSubmitted::class);
    }

    #[Test]
    public function submitting_without_documents_is_refused(): void
    {
        $this->actingAs($this->owner)
            ->postJson("/api/v1/businesses/{$this->business->id}/verification-request")
            ->assertStatus(422)
            ->assertJsonValidationErrorFor('documents');
    }

    #[Test]
    public function the_owner_reads_their_status_including_a_rejection_reason(): void
    {
        $request = VerificationRequest::factory()->for($this->business)->create([
            'status' => VerificationRequestStatus::Rejected,
            'rejection_reason' => 'Commercial register is expired.',
        ]);
        $this->business->update(['verification_status' => VerificationStatus::Rejected]);

        $this->actingAs($this->owner)
            ->getJson("/api/v1/businesses/{$this->business->id}/verification-status")
            ->assertOk()
            ->assertJsonPath('verification_status', 'rejected')
            ->assertJsonPath('rejection_reason', 'Commercial register is expired.');
    }

    #[Test]
    public function an_owner_can_download_their_own_document(): void
    {
        $this->actingAs($this->owner)->postJson(
            "/api/v1/businesses/{$this->business->id}/verification-documents",
            ['document_type_id' => $this->type->id, 'file' => $this->pdf()],
        )->assertCreated();

        $document = VerificationDocument::first();

        $this->actingAs($this->owner)
            ->get("/api/v1/businesses/{$this->business->id}/verification-documents/{$document->id}")
            ->assertOk()
            ->assertDownload('commercial-register.pdf');
    }

    #[Test]
    public function another_business_cannot_touch_this_business_documents_or_status(): void
    {
        $this->actingAs($this->owner)->postJson(
            "/api/v1/businesses/{$this->business->id}/verification-documents",
            ['document_type_id' => $this->type->id, 'file' => $this->pdf()],
        )->assertCreated();
        $document = VerificationDocument::first();

        $intruder = User::factory()->wholesaler()->create();

        $this->actingAs($intruder)
            ->getJson("/api/v1/businesses/{$this->business->id}/verification-status")
            ->assertForbidden();

        $this->actingAs($intruder)
            ->get("/api/v1/businesses/{$this->business->id}/verification-documents/{$document->id}")
            ->assertForbidden();

        $this->actingAs($intruder)
            ->postJson("/api/v1/businesses/{$this->business->id}/verification-documents", [
                'document_type_id' => $this->type->id,
                'file' => $this->pdf(),
            ])
            ->assertForbidden();
    }

    #[Test]
    public function a_verified_business_cannot_upload_more_documents(): void
    {
        $this->business->update(['verification_status' => VerificationStatus::Verified]);

        $this->actingAs($this->owner)
            ->postJson("/api/v1/businesses/{$this->business->id}/verification-documents", [
                'document_type_id' => $this->type->id,
                'file' => $this->pdf(),
            ])
            ->assertForbidden();
    }

    #[Test]
    public function the_endpoints_require_authentication(): void
    {
        $this->postJson("/api/v1/businesses/{$this->business->id}/verification-documents", [])
            ->assertUnauthorized();
    }
}
