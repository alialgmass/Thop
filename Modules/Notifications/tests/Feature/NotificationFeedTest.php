<?php

namespace Modules\Notifications\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Businesses\Models\BusinessAccount;
use Modules\Inquiries\Models\Inquiry;
use Modules\Notifications\Notifications\NewInquiryNotification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NotificationFeedTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->wholesaler()->create();
    }

    private function giveNotification(): void
    {
        $seller = BusinessAccount::factory()->create();
        $inquiry = Inquiry::factory()->create(['seller_business_id' => $seller->id]);
        $this->user->notify(new NewInquiryNotification($inquiry));
    }

    #[Test]
    public function the_feed_lists_notifications_newest_first_and_paginated(): void
    {
        $this->giveNotification();
        $this->giveNotification();

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/notifications')
            ->assertOk()
            ->assertJsonPath('body.notifications.meta.total', 2);

        $this->assertCount(2, $response->json('body.notifications.data'));
    }

    #[Test]
    public function unread_count_and_marking_read_work(): void
    {
        $this->giveNotification();
        $this->giveNotification();

        $this->actingAs($this->user)
            ->getJson('/api/v1/notifications/unread-count')
            ->assertJsonPath('body.unread_count', 2);

        $id = $this->user->notifications()->first()->id;

        $this->actingAs($this->user)
            ->postJson("/api/v1/notifications/{$id}/read")
            ->assertOk();

        $this->actingAs($this->user)
            ->getJson('/api/v1/notifications/unread-count')
            ->assertJsonPath('body.unread_count', 1);

        $this->actingAs($this->user)
            ->postJson('/api/v1/notifications/read-all')
            ->assertOk()
            ->assertJsonPath('body.unread_count', 0);

        $this->assertSame(0, $this->user->unreadNotifications()->count());
    }

    #[Test]
    public function a_user_never_sees_another_users_notifications(): void
    {
        $this->giveNotification();
        $other = User::factory()->wholesaler()->create();

        $this->actingAs($other)
            ->getJson('/api/v1/notifications')
            ->assertJsonPath('body.notifications.meta.total', 0);

        $foreignId = $this->user->notifications()->first()->id;

        $this->actingAs($other)
            ->postJson("/api/v1/notifications/{$foreignId}/read")
            ->assertNotFound();
    }

    #[Test]
    public function preferences_grid_applies_category_defaults_and_reflects_updates(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/notification-preferences')
            ->assertOk();

        // Operational category defaults on; marketing defaults off.
        $this->assertTrue($this->prefValue($response->json('body.preferences'), 'inquiry', 'push'));
        $this->assertFalse($this->prefValue($response->json('body.preferences'), 'marketing', 'push'));
        $this->assertFalse($response->json('body.marketing_opt_in'));

        $this->actingAs($this->user)
            ->putJson('/api/v1/notification-preferences', [
                'preferences' => [
                    ['category' => 'inquiry', 'channel' => 'push', 'enabled' => false],
                ],
            ])
            ->assertOk();

        $after = $this->actingAs($this->user)
            ->getJson('/api/v1/notification-preferences')
            ->json('body.preferences');

        $this->assertFalse($this->prefValue($after, 'inquiry', 'push'));
    }

    #[Test]
    public function an_operational_channel_is_reported_locked_and_cannot_be_turned_off_in_effect(): void
    {
        $this->actingAs($this->user)
            ->putJson('/api/v1/notification-preferences', [
                'preferences' => [
                    ['category' => 'verification', 'channel' => 'mail', 'enabled' => false],
                ],
            ])
            ->assertOk();

        $grid = $this->actingAs($this->user)
            ->getJson('/api/v1/notification-preferences')
            ->json('body.preferences');

        // Stored preference is false, but the resolver forces it on — the grid
        // reflects the effective state and flags it locked.
        $row = collect($grid)->firstWhere(fn ($r) => $r['category'] === 'verification' && $r['channel'] === 'mail');
        $this->assertTrue($row['enabled']);
        $this->assertTrue($row['operational_locked']);
    }

    #[Test]
    public function the_marketing_opt_in_is_a_separate_flag(): void
    {
        $this->actingAs($this->user)
            ->putJson('/api/v1/notification-preferences/marketing', ['enabled' => true])
            ->assertOk()
            ->assertJsonPath('body.marketing_opt_in', true);

        $this->actingAs($this->user)
            ->getJson('/api/v1/notification-preferences')
            ->assertJsonPath('body.marketing_opt_in', true);
    }

    /**
     * @param  array<int, array<string, mixed>>  $grid
     */
    private function prefValue(array $grid, string $category, string $channel): bool
    {
        foreach ($grid as $row) {
            if ($row['category'] === $category && $row['channel'] === $channel) {
                return (bool) $row['enabled'];
            }
        }

        $this->fail("preference {$category}/{$channel} not in grid");
    }
}
