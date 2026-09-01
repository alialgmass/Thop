<?php

namespace Modules\Verification\Tests\Feature\Filament;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;
use Modules\Businesses\Enums\VerificationStatus;
use Modules\Businesses\Models\BusinessAccount;
use Modules\Verification\Enums\VerificationRequestStatus;
use Modules\Verification\Events\VerificationApproved;
use Modules\Verification\Events\VerificationRejected;
use Modules\Verification\Filament\Resources\VerificationRequests\Pages\ListVerificationRequests;
use Modules\Verification\Filament\Resources\VerificationRequests\Pages\ViewVerificationRequest;
use Modules\Verification\Models\DocumentType;
use Modules\Verification\Models\VerificationRequest;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class VerificationRequestPanelTest extends TestCase
{
    use RefreshDatabase;

    private function pendingRequest(): VerificationRequest
    {
        $business = BusinessAccount::factory()->pending()->create();
        $request = VerificationRequest::factory()->for($business)->create([
            'status' => VerificationRequestStatus::Pending,
            'submitted_at' => now(),
        ]);
        $request->documents()->create([
            'document_type_id' => DocumentType::factory()->create()->id,
            'disk' => 'verification', 'path' => 'x/y.pdf', 'mime_type' => 'application/pdf',
            'size' => 2048, 'original_name' => 'commercial-register.pdf',
        ]);

        return $request;
    }

    #[Test]
    public function a_non_admin_cannot_open_the_verification_panel(): void
    {
        $this->actingAs(User::factory()->importer()->create())
            ->get('/admin/verification-requests')
            ->assertForbidden();
    }

    #[Test]
    public function an_admin_sees_pending_requests_in_the_list(): void
    {
        $request = $this->pendingRequest();

        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(ListVerificationRequests::class)
            ->assertOk()
            ->assertCanSeeTableRecords([$request]);
    }

    #[Test]
    public function an_admin_approves_from_the_view_page(): void
    {
        Event::fake([VerificationApproved::class]);
        $request = $this->pendingRequest();
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        Livewire::test(ViewVerificationRequest::class, ['record' => $request->getKey()])
            ->callAction('approve');

        $this->assertSame(VerificationRequestStatus::Approved, $request->refresh()->status);
        $this->assertSame(VerificationStatus::Verified, $request->businessAccount->refresh()->verification_status);
        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->id,
            'action' => 'verification.approved',
            'auditable_id' => $request->id,
        ]);
        Event::assertDispatched(VerificationApproved::class);
    }

    #[Test]
    public function an_admin_rejects_with_a_reason_from_the_view_page(): void
    {
        Event::fake([VerificationRejected::class]);
        $request = $this->pendingRequest();
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(ViewVerificationRequest::class, ['record' => $request->getKey()])
            ->callAction('reject', ['reason' => 'Commercial register is expired.']);

        $request->refresh();
        $this->assertSame(VerificationRequestStatus::Rejected, $request->status);
        $this->assertSame('Commercial register is expired.', $request->rejection_reason);
        $this->assertSame(VerificationStatus::Rejected, $request->businessAccount->refresh()->verification_status);
        Event::assertDispatched(VerificationRejected::class);
    }

    #[Test]
    public function reject_requires_a_reason(): void
    {
        $request = $this->pendingRequest();
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(ViewVerificationRequest::class, ['record' => $request->getKey()])
            ->callAction('reject', ['reason' => ''])
            ->assertHasActionErrors(['reason' => 'required']);

        $this->assertSame(VerificationRequestStatus::Pending, $request->refresh()->status);
    }

    #[Test]
    public function the_decide_actions_are_hidden_once_a_request_is_resolved(): void
    {
        $business = BusinessAccount::factory()->verified()->create();
        $request = VerificationRequest::factory()->for($business)->create([
            'status' => VerificationRequestStatus::Approved,
            'submitted_at' => now(),
            'reviewed_at' => now(),
        ]);

        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(ViewVerificationRequest::class, ['record' => $request->getKey()])
            ->assertActionHidden('approve')
            ->assertActionHidden('reject');
    }
}
