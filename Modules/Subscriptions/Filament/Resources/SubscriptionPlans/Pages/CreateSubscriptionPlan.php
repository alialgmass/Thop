<?php

namespace Modules\Subscriptions\Filament\Resources\SubscriptionPlans\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Subscriptions\Filament\Resources\SubscriptionPlans\SubscriptionPlanResource;

class CreateSubscriptionPlan extends CreateRecord
{
    protected static string $resource = SubscriptionPlanResource::class;
}
