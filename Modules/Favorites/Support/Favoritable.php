<?php

namespace Modules\Favorites\Support;

use Illuminate\Database\Eloquent\Model;
use Modules\Businesses\Models\BusinessAccount;
use Modules\Catalog\Models\Product;

/**
 * The two things a user can favorite (US-SRC-08). The keys are the public API
 * `type` tokens and the Eloquent morph aliases; the values are the models.
 */
final class Favoritable
{
    /**
     * @var array<string, class-string<Model>>
     */
    public const MAP = [
        'product' => Product::class,
        'supplier' => BusinessAccount::class,
    ];

    /**
     * @return list<string>
     */
    public static function types(): array
    {
        return array_keys(self::MAP);
    }

    /**
     * @return class-string<Model>
     */
    public static function model(string $type): string
    {
        return self::MAP[$type];
    }

    public static function find(string $type, int $id): ?Model
    {
        return self::model($type)::query()->find($id);
    }
}
