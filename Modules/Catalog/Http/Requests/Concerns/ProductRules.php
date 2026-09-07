<?php

namespace Modules\Catalog\Http\Requests\Concerns;

use Modules\Catalog\Support\ProductValidationRules;

/**
 * Adapts the shared {@see ProductValidationRules} onto the store / update form
 * requests. The rule source itself lives in that class so the single-create and
 * bulk-import paths cannot diverge (US-SEL-09).
 *
 * BR-SEL-03 (exactly one of {price, price_on_contact}) is enforced here via the
 * closure in `pricingRules()`, and independently re-checked inside the
 * `CreateProduct` action as a hard guard.
 */
trait ProductRules
{
    /**
     * @return array<string, mixed>
     */
    protected function productRules(bool $partial): array
    {
        return ProductValidationRules::fields($partial);
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
                if (! ProductValidationRules::pricingIsValid($this->input('price'), $this->input('price_on_contact', false))) {
                    $fail(__('catalog::messages.invalid_pricing'));
                }
            },
        ];
    }
}
