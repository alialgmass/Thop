<?php

namespace Modules\Subscriptions\Filament\Resources\SubscriptionPlans\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Modules\Subscriptions\Filament\Resources\SubscriptionPlans\SubscriptionPlanResource;

class EditSubscriptionPlan extends EditRecord
{
    protected static string $resource = SubscriptionPlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
