<?php

namespace Modules\Admin\Filament\Resources\SupplierOnboardings\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Modules\Admin\Filament\Resources\SupplierOnboardings\SupplierOnboardingResource;

class ListOnboardedSuppliers extends ListRecords
{
    protected static string $resource = SupplierOnboardingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label(__('admin::panel.supplier_onboarding.onboard')),
        ];
    }
}
