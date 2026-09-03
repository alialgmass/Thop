<?php

namespace Modules\Catalog\Http\Requests\Concerns;

use Illuminate\Validation\Rule;

/**
 * The product field rules, shared by the store / update requests AND by the
 * bulk-import row validation (US-SEL-09) so single-create and bulk-row
 * validation cannot diverge — mirrors BusinessProfileRules.
 *
 * BR-SEL-03 (exactly one of {price, price_on_contact}) is enforced here via
 * the custom closure in `pricingRules()`, and independently re-checked inside
 * {@see \Modules\Catalog\Actions\CreateProduct} as a hard guard.
 */
trait ProductRules
{
    /**
     * Core product rules. `$partial` (update/import is partially validated by
     * its own path) makes each field optional.
     *
     * @return array<string, mixed>
     */
    protected function productRules(bool $partial): array
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
     * Enforcement of BR-SEL-03 — exactly one of {price, price_on_contact}.
     *
     * @return list<mixed>
     */
    protected function pricingRules(): array
    {
        return [
            function (string $attribute, mixed $value, \Closure $fail): void {
                $price = $this->input('price');
                $priceOnContact = $this->input('price_on_contact', false);

                $hasPrice = $price !== null && $price !== '';
                $hasContact = filter_var($priceOnContact, FILTER_VALIDATE_BOOLEAN);

                if ($hasPrice && $hasContact) {
                    $fail(__('catalog::messages.invalid_pricing'));
                }

                if (! $hasPrice && ! $hasContact) {
                    $fail(__('catalog::messages.invalid_pricing'));
                }
            },
        ];
    }
}
