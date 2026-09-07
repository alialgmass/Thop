<?php

namespace Modules\Catalog\Support;

use Illuminate\Validation\Rule;

/**
 * The single source of the product field rules. The store / update form requests
 * (via the `ProductRules` trait) and the bulk-import row validation (via
 * `ImportProductRows`) both read from here so single-create and bulk-row
 * validation cannot diverge (US-SEL-09).
 */
class ProductValidationRules
{
    /**
     * Core product field rules. `$partial` (used by the update path) makes each
     * otherwise-required field `sometimes`.
     *
     * @return array<string, mixed>
     */
    public static function fields(bool $partial): array
    {
        $optional = $partial ? ['sometimes'] : [];

        return [
            'name_ar' => [...$optional, 'required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'fabric_type_id' => [...$optional, 'required', 'integer', Rule::exists('fabric_types', 'id')->where('is_active', true)],
            'material_id' => [...$optional, 'required', 'integer', Rule::exists('materials', 'id')->where('is_active', true)],
            'governorate_id' => [...$optional, 'required', 'integer', Rule::exists('governorates', 'id')->where('is_active', true)],
            'width_cm' => ['nullable', 'integer', 'min:0'],
            'weight_gsm' => ['nullable', 'integer', 'min:0'],
            'unit' => [...$optional, 'required', Rule::in(['per_meter', 'per_kg'])],
            'moq' => ['nullable', 'integer', 'min:1'],
            'quantity_available' => [...$optional, 'required', 'integer', 'min:0'],
            'colors' => ['nullable', 'array'],
            'colors.*' => ['integer', Rule::exists('colors', 'id')->where('is_active', true)],
            'price_tiers' => ['nullable', 'array'],
            'price_tiers.*.min_qty' => ['required_with:price_tiers', 'integer', 'min:1'],
            'price_tiers.*.unit_price' => ['required_with:price_tiers', 'numeric', 'min:0'],
        ];
    }

    /**
     * BR-SEL-03: exactly one of {price, price_on_contact} must be set.
     */
    public static function pricingIsValid(mixed $price, mixed $priceOnContact): bool
    {
        $hasPrice = $price !== null && $price !== '';
        $hasContact = filter_var($priceOnContact, FILTER_VALIDATE_BOOLEAN);

        return $hasPrice !== $hasContact;
    }
}
