<?php

namespace Modules\Inquiries\Tests\Feature\Filament;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Chat\Models\Conversation;
use Modules\Chat\Models\Message;
use Modules\Inquiries\Enums\ReportableType;
use Modules\Inquiries\Filament\Resources\Reports\Pages\ListReports;
use Modules\Inquiries\Filament\Resources\Reports\Pages\ViewReport;
use Modules\Inquiries\Models\Inquiry;
use Modules\Inquiries\Models\Report;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ReportPanelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function a_non_admin_cannot_open_the_reports_panel(): void
    {
        $this->actingAs(User::factory()->importer()->create())
            ->get('/admin/reports')
            ->assertForbidden();
    }

    #[Test]
    public function an_admin_sees_open_reports_first_in_the_list(): void
    {
        $inquiry = Inquiry::factory()->create();
        $resolved = Report::factory()->for($inquiry, 'reportable')->create([
            'status' => 'resolved', 'resolved_at' => now(), 'resolution_note' => 'x', 'created_at' => now()->subDay(),
        ]);
        $open = Report::factory()->for($inquiry, 'reportable')->create(['created_at' => now()]);

        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(ListReports::class)
            ->assertOk()
            ->assertCanSeeTableRecords([$open, $resolved], inOrder: true);
    }

    #[Test]
    public function an_admin_resolves_a_report_with_a_note_from_the_view_page(): void
    {
        $report = Report::factory()->create();
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        Livewire::test(ViewReport::class, ['record' => $report->getKey()])
            ->callAction('resolve', ['status' => 'resolved', 'note' => 'Reviewed, action taken.']);

        $report->refresh();
        $this->assertSame('resolved', $report->status->value);
        $this->assertSame('Reviewed, action taken.', $report->resolution_note);
        $this->assertSame($admin->id, $report->resolved_by);
        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->id, 'action' => 'report.resolved', 'auditable_id' => $report->id,
        ]);
    }

    #[Test]
    public function resolving_requires_a_note(): void
    {
        $report = Report::factory()->create();
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(ViewReport::class, ['record' => $report->getKey()])
            ->callAction('resolve', ['status' => 'resolved', 'note' => ''])
            ->assertHasActionErrors(['note' => 'required']);

        $this->assertSame('open', $report->refresh()->status->value);
    }

    #[Test]
    public function the_view_page_resolves_both_parties_for_a_reported_chat_message(): void
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

        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(ViewReport::class, ['record' => $report->getKey()])
            ->assertOk()
            ->assertSee($inquiry->buyer->phone)
            ->assertSee($inquiry->sellerBusiness->company_name);
    }

    #[Test]
    public function the_resolve_action_is_hidden_once_a_report_is_resolved(): void
    {
        $report = Report::factory()->create([
            'status' => 'dismissed', 'resolved_at' => now(), 'resolution_note' => 'x',
        ]);

        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(ViewReport::class, ['record' => $report->getKey()])
            ->assertActionHidden('resolve');
    }
}
