<?php

namespace Modules\Subscriptions\Filament\Resources\SubscriptionPlans\Pages;

use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Checkbox;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Modules\Subscriptions\Actions\ManageSubscriptionPlan;
use Modules\Subscriptions\Filament\Resources\SubscriptionPlans\SubscriptionPlanResource;
use Modules\Subscriptions\Models\SubscriptionPlan;

class EditSubscriptionPlan extends EditRecord
{
    protected static string $resource = SubscriptionPlanResource::class;

    /**
     * Routed through ManageSubscriptionPlan so the panel writes the same
     * `plan.updated` audit row as the REST endpoint, and — critically — so a
     * plain edit here never touches any subscriber's entitlement snapshot
     * (US-SUB-05). See {@see CreateSubscriptionPlan} for why $data never
     * carries `entitlements` on this path.
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var User $admin */
        $admin = auth()->user();

        /** @var SubscriptionPlan $record */
        return app(ManageSubscriptionPlan::class)->update($record, $data, $admin);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('applyToExisting')
                ->label(__('subscriptions::panel.actions.apply_to_existing'))
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->schema([
                    Checkbox::make('confirm')
                        ->label(__('subscriptions::panel.actions.apply_to_existing_confirm'))
                        ->required()
                        ->accepted(),
                ])
                ->action(function (): void {
                    /** @var SubscriptionPlan $plan */
                    $plan = $this->record;
                    /** @var User $admin */
                    $admin = auth()->user();

                    $count = app(ManageSubscriptionPlan::class)->applyToExisting($plan, $admin);

                    Notification::make()
                        ->success()
                        ->title(__('subscriptions::panel.actions.apply_to_existing_done', ['count' => $count]))
                        ->send();
                }),
            DeleteAction::make(),
        ];
    }
}
