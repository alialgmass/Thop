<?php

namespace Modules\Subscriptions\Filament\Resources\SubscriptionPlans\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Modules\Subscriptions\Filament\Resources\SubscriptionPlans\SubscriptionPlanResource;

class ListSubscriptionPlans extends ListRecords
{
    protected static string $resource = SubscriptionPlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
