<?php

namespace Modules\Inquiries\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Businesses\Models\BusinessAccount;
use Modules\Chat\Models\Conversation;
use Modules\Chat\Models\Message;
use Modules\Inquiries\Enums\ReportableType;
use Modules\Inquiries\Models\Inquiry;
use Modules\Inquiries\Models\Report;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Admin dispute/report queue (US-ADM-08, Phase 9 · T9, issue #38).
 */
class AdminReportTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function a_non_admin_cannot_reach_the_report_queue(): void
    {
        $this->actingAs(User::factory()->importer()->create())
            ->getJson('/api/v1/admin/reports')
            ->assertForbidden();
    }

    #[Test]
    public function the_queue_lists_open_reports_first_and_shows_both_parties(): void
    {
        $inquiry = Inquiry::factory()->create();
        $resolvedReport = Report::factory()->for($inquiry, 'reportable')->create([
            'status' => 'resolved', 'resolved_at' => now(), 'resolution_note' => 'x', 'created_at' => now()->subDay(),
        ]);
        $openReport = Report::factory()->for($inquiry, 'reportable')->create(['created_at' => now()]);

        $response = $this->actingAs(User::factory()->admin()->create())
            ->getJson('/api/v1/admin/reports')
            ->assertOk();

        $ids = collect($response->json('body.reports.data'))->pluck('id')->all();
        $this->assertSame([$openReport->id, $resolvedReport->id], $ids);

        $response->assertJsonFragment(['buyer' => ['id' => $inquiry->buyer_id, 'phone' => $inquiry->buyer->phone]]);
    }

    #[Test]
    public function the_queue_is_filterable_by_status(): void
    {
        $inquiry = Inquiry::factory()->create();
        Report::factory()->for($inquiry, 'reportable')->create(['status' => 'open']);
        $dismissed = Report::factory()->for($inquiry, 'reportable')->create([
            'status' => 'dismissed', 'resolved_at' => now(), 'resolution_note' => 'x',
        ]);

        $response = $this->actingAs(User::factory()->admin()->create())
            ->getJson('/api/v1/admin/reports?status=dismissed')
            ->assertOk();

        $ids = collect($response->json('body.reports.data'))->pluck('id')->all();
        $this->assertSame([$dismissed->id], $ids);
    }

    #[Test]
    public function resolving_a_report_requires_a_note(): void
    {
        $report = Report::factory()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->postJson("/api/v1/admin/reports/{$report->id}/resolve", [])
            ->assertStatus(400);

        $this->assertSame('open', $report->refresh()->status->value);
    }

    #[Test]
    public function an_admin_resolves_a_report_which_records_who_when_and_why_and_is_audited(): void
    {
        $report = Report::factory()->create();
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->postJson("/api/v1/admin/reports/{$report->id}/resolve", ['note' => 'Investigated, no violation found.'])
            ->assertOk();

        $report->refresh();
        $this->assertSame('resolved', $report->status->value);
        $this->assertSame($admin->id, $report->resolved_by);
        $this->assertSame('Investigated, no violation found.', $report->resolution_note);
        $this->assertNotNull($report->resolved_at);
        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->id, 'action' => 'report.resolved', 'auditable_id' => $report->id,
        ]);
    }

    #[Test]
    public function an_admin_can_dismiss_a_report_instead_of_resolving_it(): void
    {
        $report = Report::factory()->create();
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->postJson("/api/v1/admin/reports/{$report->id}/resolve", [
                'note' => 'No evidence of abuse.', 'status' => 'dismissed',
            ])
            ->assertOk();

        $this->assertSame('dismissed', $report->refresh()->status->value);
        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->id, 'action' => 'report.resolved', 'auditable_id' => $report->id,
        ]);
    }

    #[Test]
    public function a_newly_created_report_defaults_to_open(): void
    {
        // ReportFactory deliberately never sets `status` — this proves the
        // migration's column default, not a factory override. refresh() is
        // required: Eloquent's create() doesn't pull back server-applied
        // column defaults into the in-memory model on its own.
        $report = Report::factory()->create()->refresh();

        $this->assertSame('open', $report->status->value);
    }

    #[Test]
    public function resolving_an_already_resolved_report_is_rejected(): void
    {
        $report = Report::factory()->create([
            'status' => 'resolved', 'resolved_at' => now(), 'resolution_note' => 'x',
        ]);

        $this->actingAs(User::factory()->admin()->create())
            ->postJson("/api/v1/admin/reports/{$report->id}/resolve", ['note' => 'again'])
            ->assertStatus(409);
    }

    #[Test]
    public function a_reported_chat_message_resolves_parties_through_its_conversations_inquiry(): void
    {
        $inquiry = Inquiry::factory()->create();
        $conversation = Conversation::query()->create([
            'inquiry_id' => $inquiry->id,
            'buyer_id' => $inquiry->buyer_id,
            'seller_business_id' => $inquiry->seller_business_id,
        ]);
        $message = Message::query()->create([
            'conversation_id' => $conversation->id, 'sender_id' => $inquiry->buyer_id, 'body' => 'hello',
        ]);
        $report = Report::factory()->create([
            'reportable_type' => ReportableType::Message->value, 'reportable_id' => $message->id,
        ]);

        $response = $this->actingAs(User::factory()->admin()->create())
            ->getJson('/api/v1/admin/reports')
            ->assertOk();

        $response->assertJsonFragment([
            'seller_business' => ['id' => $inquiry->seller_business_id, 'company_name' => BusinessAccount::find($inquiry->seller_business_id)->company_name],
        ]);
        $this->assertNotNull($report);
    }
}
