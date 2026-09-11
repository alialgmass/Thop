<?php

namespace Modules\Admin\Filament\Resources\SupplierOnboardings\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rule;
use Modules\Admin\Http\Requests\OnboardSupplierRequest;
use Modules\Auth\Rules\EgyptianMobile;
use Modules\Taxonomy\Models\Governorate;

/**
 * The two-step wizard an admin fills out to onboard a supplier on their
 * behalf (US-ADM-10, Phase 9 · T10) — account details, then business
 * profile. Filament form schemas and Laravel FormRequest rule arrays are
 * structurally incompatible, so this can't literally import
 * {@see OnboardSupplierRequest}'s rules — but every constraint here is
 * hand-matched to it field-for-field (same {@see EgyptianMobile} rule
 * object, same required/maxLength bounds, same active-governorate-only
 * constraint), so the two describe the same validation, just twice.
 */
class SupplierOnboardingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Wizard::make([
                Step::make(__('admin::panel.supplier_onboarding.steps.account'))
                    ->schema([
                        TextInput::make('phone')
                            ->label(__('admin::panel.supplier_onboarding.fields.phone'))
                            ->required()
                            ->rule(new EgyptianMobile),
                        Select::make('account_type')
                            ->label(__('admin::panel.supplier_onboarding.fields.account_type'))
                            ->options([
                                'importer' => __('admin::panel.supplier_onboarding.account_types.importer'),
                                'wholesaler' => __('admin::panel.supplier_onboarding.account_types.wholesaler'),
                                'retailer' => __('admin::panel.supplier_onboarding.account_types.retailer'),
                            ])
                            ->required(),
                        TextInput::make('email')
                            ->label(__('admin::panel.supplier_onboarding.fields.email'))
                            ->email(),
                        Select::make('language')
                            ->label(__('admin::panel.supplier_onboarding.fields.language'))
                            ->options(['ar' => 'العربية', 'en' => 'English'])
                            ->default('ar'),
                        TextInput::make('password')
                            ->label(__('admin::panel.supplier_onboarding.fields.password'))
                            ->password()
                            ->revealable()
                            ->required()
                            ->same('password_confirmation'),
                        TextInput::make('password_confirmation')
                            ->label(__('admin::panel.supplier_onboarding.fields.password_confirmation'))
                            ->password()
                            ->revealable()
                            ->dehydrated(false)
                            ->required(),
                    ]),

                Step::make(__('admin::panel.supplier_onboarding.steps.business'))
                    ->schema([
                        TextInput::make('company_name')
                            ->label(__('admin::panel.supplier_onboarding.fields.company_name'))
                            ->required()
                            ->maxLength(255),
                        TextInput::make('activity')
                            ->label(__('admin::panel.supplier_onboarding.fields.activity'))
                            ->required()
                            ->maxLength(255),
                        Select::make('governorate_id')
                            ->label(__('admin::panel.supplier_onboarding.fields.governorate'))
                            ->options(fn () => Governorate::query()->active()->pluck('name_en', 'id'))
                            ->searchable()
                            ->required()
                            ->rule(Rule::exists('governorates', 'id')->where('is_active', true)),
                        Textarea::make('address')
                            ->label(__('admin::panel.supplier_onboarding.fields.address'))
                            ->required()
                            ->maxLength(500),
                        TextInput::make('contact_person')
                            ->label(__('admin::panel.supplier_onboarding.fields.contact_person'))
                            ->required()
                            ->maxLength(255),
                    ]),
            ]),
        ]);
    }
}
