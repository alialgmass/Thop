<?php

namespace Modules\Notifications\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Notifications\Database\Factories\NotificationPreferenceFactory;
use Modules\Notifications\Enums\NotificationCategory;
use Modules\Notifications\Enums\NotificationChannel;

/**
 * One explicit preference override. Absence of a row = the category default
 * (see {@see NotificationCategory::defaultEnabled()}).
 *
 * @property int $id
 * @property int $user_id
 * @property NotificationCategory $category
 * @property NotificationChannel $channel
 * @property bool $enabled
 */
class NotificationPreference extends Model
{
    /** @use HasFactory<NotificationPreferenceFactory> */
    use HasFactory;

    protected $guarded = ['id'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'category' => NotificationCategory::class,
            'channel' => NotificationChannel::class,
            'enabled' => 'boolean',
        ];
    }

    protected static function newFactory(): NotificationPreferenceFactory
    {
        return NotificationPreferenceFactory::new();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Whether the given user wants this category on this channel — the
     * explicit row if one exists, otherwise the category default.
     */
    public static function resolveEnabled(
        User $user,
        NotificationCategory $category,
        NotificationChannel $channel,
    ): bool {
        $row = static::query()
            ->where('user_id', $user->getKey())
            ->where('category', $category->value)
            ->where('channel', $channel->value)
            ->first();

        return $row?->enabled ?? $category->defaultEnabled();
    }
}
