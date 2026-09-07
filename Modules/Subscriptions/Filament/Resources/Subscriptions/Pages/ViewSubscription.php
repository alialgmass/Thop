<?php

namespace Modules\Subscriptions\Filament\Resources\Subscriptions\Pages;

use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Modules\Subscriptions\Enums\SubscriptionStatus;
use Modules\Subscriptions\Filament\Resources\Subscriptions\SubscriptionResource;
use Modules\Subscriptions\Models\Subscription;
use Modules\Subscriptions\Models\SubscriptionPlan;

class ViewSubscription extends ViewRecord
{
    protected static string $resource = SubscriptionResource::class;

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('subscriptions::panel.sections.business'))
                ->columns(2)
                ->schema([
                    TextEntry::make('businessAccount.company_name')->label(__('subscriptions::panel.fields.company')),
                    TextEntry::make('businessAccount.activity')->label(__('subscriptions::panel.fields.activity')),
                    TextEntry::make('businessAccount.owner.phone')->label(__('subscriptions::panel.fields.owner_phone')),
                    TextEntry::make('businessAccount.owner.email')->label(__('subscriptions::panel.fields.owner_email'))->placeholder('—'),
                ]),

            Section::make(__('subscriptions::panel.sections.subscription'))
                ->columns(2)
                ->schema([
                    TextEntry::make('plan.name')
                        ->label(__('subscriptions::panel.fields.plan'))
                        ->badge(),
                    TextEntry::make('plan.account_type')
                        ->label(__('subscriptions::panel.fields.account_type'))
                        ->badge(),
                    TextEntry::make('status')
                        ->label(__('subscriptions::panel.fields.status'))
                        ->badge()
                        ->color(fn (SubscriptionStatus $state): string => match ($state) {
                            SubscriptionStatus::Active => 'success',
                            SubscriptionStatus::Expired => 'warning',
                            SubscriptionStatus::Cancelled => 'danger',
                            SubscriptionStatus::Restricted => 'gray',
                        }),
                    TextEntry::make('plan.price')
                        ->label(__('subscriptions::panel.fields.price'))
                        ->formatStateUsing(fn ($state): string => $state !== null ? number_format((float) $state, 2) : __('subscriptions::panel.fields.custom')),
                    TextEntry::make('current_period_end')
                        ->label(__('subscriptions::panel.fields.period_ends'))
                        ->dateTime()
                        ->placeholder('—'),
                    TextEntry::make('trial_ends_at')
                        ->label(__('subscriptions::panel.fields.trial_ends'))
                        ->dateTime()
                        ->placeholder('—'),
                    TextEntry::make('created_at')
                        ->label(__('subscriptions::panel.fields.subscribed_since'))
                        ->dateTime(),
                ]),

            Section::make(__('subscriptions::panel.sections.entitlements'))
                ->schema([
                    TextEntry::make('plan.entitlements')
                        ->hiddenLabel()
                        ->state(fn (Subscription $record): string => $record->plan?->entitlements
                            ?->map(fn ($e) => "{$e->key}: {$e->value}")
                            ->implode("\n") ?? '—'),
                ]),
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('grantTrial')
                ->label(__('subscriptions::panel.actions.grant_trial'))
                ->icon('heroicon-o-gift')
                ->color('primary')
                ->visible(fn (): bool => ! $this->record->isActive())
                ->schema([
                    Select::make('plan_id')
                        ->label(__('subscriptions::panel.fields.plan'))
                        ->options(fn () => SubscriptionPlan::where('is_active', true)
                            ->pluck('name', 'id'))
                        ->required(),
                    DatePicker::make('trial_ends_at')
                        ->label(__('subscriptions::panel.fields.trial_ends'))
                        ->required()
                        ->minDate(now()),
                ])
                ->action(fn (array $data) => $this->grantTrial($data)),

            Action::make('extendPeriod')
                ->label(__('subscriptions::panel.actions.extend_period'))
                ->icon('heroicon-o-calendar')
                ->color('warning')
                ->visible(fn (): bool => $this->record->isActive())
                ->schema([
                    DatePicker::make('new_period_end')
                        ->label(__('subscriptions::panel.fields.new_period_end'))
                        ->required()
                        ->minDate(now()->addDay()),
                ])
                ->action(fn (array $data) => $this->extendPeriod($data)),

            Action::make('cancel')
                ->label(__('subscriptions::panel.actions.cancel'))
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn (): bool => $this->record->isActive())
                ->requiresConfirmation()
                ->action(fn () => $this->cancelSubscription()),
        ];
    }

    private function grantTrial(array $data): void
    {
        /** @var Subscription $subscription */
        $subscription = $this->record;

        $subscription->update([
            'plan_id' => $data['plan_id'],
            'status' => SubscriptionStatus::Active,
            'trial_ends_at' => $data['trial_ends_at'],
            'current_period_end' => null,
        ]);

        Notification::make()->success()->title(__('subscriptions::panel.actions.trial_granted'))->send();

        $this->refreshFormData(['status', 'plan', 'trial_ends_at', 'current_period_end']);
    }

    private function extendPeriod(array $data): void
    {
        /** @var Subscription $subscription */
        $subscription = $this->record;

        $subscription->update([
            'current_period_end' => $data['new_period_end'],
        ]);

        Notification::make()->success()->title(__('subscriptions::panel.actions.period_extended'))->send();

        $this->refreshFormData(['current_period_end']);
    }

    private function cancelSubscription(): void
    {
        /** @var Subscription $subscription */
        $subscription = $this->record;

        $subscription->update([
            'status' => SubscriptionStatus::Cancelled,
        ]);

        Notification::make()->success()->title(__('subscriptions::panel.actions.cancelled'))->send();

        $this->refreshFormData(['status']);
    }
}
