<?php

namespace Modules\Businesses\Http\Requests\Concerns;

use Illuminate\Validation\Rule;

/**
 * The company-profile field rules, shared by the store and update requests
 * (US-ACC-03 / §7 Validation Catalogue). The update request makes each field
 * optional via {@see self::profileRules()}'s `$partial` flag.
 */
trait BusinessProfileRules
{
    /**
     * @return array<string, list<mixed>>
     */
    protected function profileRules(bool $partial): array
    {
        $optional = $partial ? ['sometimes'] : [];

        return [
            'company_name' => [...$optional, 'required', 'string', 'max:255'],
            'activity' => [...$optional, 'required', 'string', 'max:255'],
            'governorate_id' => [...$optional, 'required', 'integer', Rule::exists('governorates', 'id')->where('is_active', true)],
            'address' => [...$optional, 'required', 'string', 'max:500'],
            'contact_person' => [...$optional, 'required', 'string', 'max:255'],
            'contact_channels' => ['nullable', 'array'],
            'contact_channels.*.type' => ['required_with:contact_channels', 'string', 'max:50'],
            'contact_channels.*.value' => ['required_with:contact_channels', 'string', 'max:255'],
        ];
    }
}
