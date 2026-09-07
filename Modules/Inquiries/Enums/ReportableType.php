<?php

namespace Modules\Inquiries\Enums;

use Illuminate\Database\Eloquent\Model;
use Modules\Chat\Models\Message;
use Modules\Inquiries\Models\Inquiry;

/**
 * What can be reported (US-INQ-09 / US-CHT-09): an `Inquiry` thread or a chat
 * `Message`. Each case's value is both the `reportable_type` morph alias and
 * an API-facing token, the same shape as Favorites' `FavoritableType`.
 */
enum ReportableType: string
{
    case Inquiry = 'inquiry';
    case Message = 'message';

    /**
     * @return class-string<Model>
     */
    public function modelClass(): string
    {
        return match ($this) {
            self::Inquiry => Inquiry::class,
            self::Message => Message::class,
        };
    }

    /**
     * The non-enforcing morph map: alias => model class.
     *
     * @return array<string, class-string<Model>>
     */
    public static function morphMap(): array
    {
        return array_reduce(
            self::cases(),
            fn (array $map, self $case): array => $map + [$case->value => $case->modelClass()],
            [],
        );
    }
}
