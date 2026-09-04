<?php

namespace Modules\Favorites\Enums;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Businesses\Models\BusinessAccount;
use Modules\Catalog\Http\Resources\ProductCardResource;
use Modules\Catalog\Models\Product;
use Modules\Search\Http\Resources\SupplierCardResource;

/**
 * The two things a user can favorite (US-SRC-08). Each case's value is both the
 * public API `type` token and the Eloquent morph alias stored in
 * `favorites.favoritable_type`. This is the single place the product-vs-supplier
 * dispatch lives — model class, lookup and card resource all hang off the enum.
 */
enum FavoritableType: string
{
    case Product = 'product';
    case Supplier = 'supplier';

    /**
     * @return class-string<Model>
     */
    public function modelClass(): string
    {
        return match ($this) {
            self::Product => Product::class,
            self::Supplier => BusinessAccount::class,
        };
    }

    public function find(int $id): ?Model
    {
        return $this->modelClass()::query()->find($id);
    }

    public function card(Model $model): JsonResource
    {
        return match ($this) {
            self::Product => new ProductCardResource($model),
            self::Supplier => new SupplierCardResource($model),
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

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
