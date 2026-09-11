<?php

namespace Modules\Subscriptions\Filament\Resources\SubscriptionPlans\Pages;

use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Modules\Subscriptions\Actions\ManageSubscriptionPlan;
use Modules\Subscriptions\Filament\Resources\SubscriptionPlans\SubscriptionPlanResource;

class CreateSubscriptionPlan extends CreateRecord
{
    protected static string $resource = SubscriptionPlanResource::class;

    /**
     * Routed through ManageSubscriptionPlan so the panel writes the same
     * `plan.created` audit row as the REST endpoint (Phase 9 · T4). The
     * entitlements Repeater saves separately via its own `->relationship()`
     * wiring after this returns — $data never carries an `entitlements` key
     * here, so the action's own entitlement-sync is a no-op on this path.
     */
    protected function handleRecordCreation(array $data): Model
    {
        /** @var User $admin */
        $admin = auth()->user();

        return app(ManageSubscriptionPlan::class)->create($data, $admin);
    }
}
