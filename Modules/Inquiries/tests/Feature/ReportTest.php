<?php

namespace Modules\Inquiries\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Businesses\Models\BusinessAccount;
use Modules\Inquiries\Enums\ReportableType;
use Modules\Inquiries\Models\Inquiry;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    private User $buyer;

    private User $sellerUser;

    private Inquiry $inquiry;

    protected function setUp(): void
    {
        parent::setUp();

        $this->buyer = User::factory()->wholesaler()->create();

        $this->sellerUser = User::factory()->importer()->create();
        $sellerBusiness = BusinessAccount::factory()->for($this->sellerUser, 'owner')->create();

        $this->inquiry = Inquiry::factory()->create([
            'buyer_id' => $this->buyer->id,
            'seller_business_id' => $sellerBusiness->id,
        ]);
    }

    #[Test]
    public function the_buyer_reports_an_inquiry(): void
    {
        $this->actingAs($this->buyer)
            ->postJson("/api/v1/inquiries/{$this->inquiry->id}/reports", ['reason' => 'Seller is asking for payment outside the app in a suspicious way'])
            ->assertCreated()
            ->assertJsonPath('body.report.reportable_type', ReportableType::Inquiry->value)
            ->assertJsonPath('body.report.reportable_id', $this->inquiry->id);

        $this->assertDatabaseHas('reports', [
            'reportable_type' => ReportableType::Inquiry->value,
            'reportable_id' => $this->inquiry->id,
            'reporter_id' => $this->buyer->id,
        ]);
    }

    #[Test]
    public function the_seller_reports_an_inquiry(): void
    {
        $this->actingAs($this->sellerUser)
            ->postJson("/api/v1/inquiries/{$this->inquiry->id}/reports", ['reason' => 'Abusive language from the buyer'])
            ->assertCreated();

        $this->assertDatabaseHas('reports', [
            'reportable_id' => $this->inquiry->id,
            'reporter_id' => $this->sellerUser->id,
        ]);
    }

    #[Test]
    public function a_missing_reason_is_a_validation_error(): void
    {
        $this->actingAs($this->buyer)
            ->postJson("/api/v1/inquiries/{$this->inquiry->id}/reports", [])
            ->assertStatus(400);
    }

    #[Test]
    public function a_non_party_cannot_report_an_inquiry(): void
    {
        $stranger = User::factory()->wholesaler()->create();

        $this->actingAs($stranger)
            ->postJson("/api/v1/inquiries/{$this->inquiry->id}/reports", ['reason' => 'Not my business'])
            ->assertStatus(403);

        $this->assertDatabaseCount('reports', 0);
    }

    #[Test]
    public function guests_cannot_report_an_inquiry(): void
    {
        $this->postJson("/api/v1/inquiries/{$this->inquiry->id}/reports", ['reason' => 'x'])
            ->assertStatus(401);
    }
}
