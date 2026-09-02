<?php

namespace Modules\Core\Http\Controllers\Api;

use Illuminate\Support\Str;
use Modules\Auth\Enums\AccountType;
use Modules\Auth\Enums\UserStatus;
use Modules\Businesses\Enums\VerificationStatus;
use Modules\Core\Enums\CurrencyEnum;
use Modules\Verification\Enums\VerificationRequestStatus;

class EnumController extends ApiController
{
    public function __invoke()
    {
        return self::apiBody([
            'currencies' => CurrencyEnum::toArray(),
            'account_types' => self::catalogOf(AccountType::class),
            'user_status' => self::catalogOf(UserStatus::class),
            'verification_status' => self::catalogOf(VerificationStatus::class),
            'verification_request_status' => self::catalogOf(VerificationRequestStatus::class),
        ])->apiResponse();
    }

    /**
     * @param  class-string<\BackedEnum>  $enum
     * @return list<array{value: string, label: string}>
     */
    private static function catalogOf(string $enum): array
    {
        return array_map(fn ($case): array => [
            'value' => $case->value,
            'label' => method_exists($case, 'label') ? $case->label() : Str::headline($case->value),
        ], $enum::cases());
    }
}
