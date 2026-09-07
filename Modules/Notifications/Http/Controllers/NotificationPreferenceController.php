<?php

namespace Modules\Notifications\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Http\Controllers\Controller;
use Modules\Core\Support\Api\ApiResponse;
use Modules\Notifications\Enums\NotificationCategory;
use Modules\Notifications\Enums\NotificationChannel;
use Modules\Notifications\Http\Requests\UpdateMarketingPreferenceRequest;
use Modules\Notifications\Http\Requests\UpdateNotificationPreferencesRequest;
use Modules\Notifications\Models\NotificationPreference;

/**
 * Per-category / per-channel notification preferences (US-NOT-02) plus the
 * separate marketing opt-in (US-NOT-04).
 */
class NotificationPreferenceController extends Controller
{
    use ApiResponse;

    /**
     * The caller's effective preference grid — every (category, channel) with
     * its resolved `enabled` (defaults applied), plus the marketing opt-in.
     */
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $grid = [];

        foreach (NotificationCategory::cases() as $category) {
            foreach (NotificationChannel::cases() as $channel) {
                $locked = ($channel->isOperationalOverride() && $this->categoryIsOperational($category))
                    || $channel === NotificationChannel::Database;

                $grid[] = [
                    'category' => $category->value,
                    'channel' => $channel->value,
                    // Effective state: a locked channel is always on regardless
                    // of the stored preference (US-NOT-03) — the client sees the
                    // override, not a value that won't be honoured.
                    'enabled' => $locked || NotificationPreference::resolveEnabled($user, $category, $channel),
                    'operational_locked' => $locked,
                ];
            }
        }

        return $this
            ->apiMessage(__('notifications::messages.preferences_retrieved'))
            ->apiBody([
                'preferences' => $grid,
                'marketing_opt_in' => $this->marketingOptIn($user),
            ])
            ->apiResponse();
    }

    public function update(UpdateNotificationPreferencesRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        foreach ($request->array('preferences') as $preference) {
            NotificationPreference::updateOrCreate(
                [
                    'user_id' => $user->getKey(),
                    'category' => $preference['category'],
                    'channel' => $preference['channel'],
                ],
                ['enabled' => (bool) $preference['enabled']],
            );
        }

        return $this->index($request);
    }

    public function updateMarketing(UpdateMarketingPreferenceRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $enabled = $request->boolean('enabled');

        foreach (NotificationChannel::cases() as $channel) {
            if ($channel === NotificationChannel::Database) {
                continue;
            }

            NotificationPreference::updateOrCreate(
                [
                    'user_id' => $user->getKey(),
                    'category' => NotificationCategory::Marketing->value,
                    'channel' => $channel->value,
                ],
                ['enabled' => $enabled],
            );
        }

        return $this
            ->apiMessage(__('notifications::messages.marketing_updated'))
            ->apiBody(['marketing_opt_in' => $enabled])
            ->apiResponse();
    }

    private function categoryIsOperational(NotificationCategory $category): bool
    {
        return in_array($category, [NotificationCategory::Verification, NotificationCategory::Subscription], true);
    }

    private function marketingOptIn(User $user): bool
    {
        return NotificationPreference::query()
            ->where('user_id', $user->getKey())
            ->where('category', NotificationCategory::Marketing->value)
            ->where('enabled', true)
            ->exists();
    }
}
