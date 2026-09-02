<?php

namespace Modules\Subscriptions\Filament\Resources\Subscriptions\Pages;

use Filament\Resources\Pages\ListRecords;
use Modules\Subscriptions\Filament\Resources\Subscriptions\SubscriptionResource;

class ListSubscriptions extends ListRecords
{
    protected static string $resource = SubscriptionResource::class;
}
