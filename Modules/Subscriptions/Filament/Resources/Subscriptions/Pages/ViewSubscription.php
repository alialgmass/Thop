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
            Section::make('Business')
                ->columns(2)
                ->schema([
                    TextEntry::make('businessAccount.company_name')->label('Company'),
                    TextEntry::make('businessAccount.activity')->label('Activity'),
                    TextEntry::make('businessAccount.owner.phone')->label('Owner phone'),
                    TextEntry::make('businessAccount.owner.email')->label('Owner email')->placeholder('—'),
                ]),

            Section::make('Subscription')
                ->columns(2)
                ->schema([
                    TextEntry::make('plan.name')
                        ->label('Plan')
                        ->badge(),
                    TextEntry::make('plan.account_type')
                        ->label('Account Type')
                        ->badge(),
                    TextEntry::make('status')
                        ->badge()
                        ->color(fn (SubscriptionStatus $state): string => match ($state) {
                            SubscriptionStatus::Active => 'success',
                            SubscriptionStatus::Expired => 'warning',
                            SubscriptionStatus::Cancelled => 'danger',
                            SubscriptionStatus::Restricted => 'gray',
                        }),
                    TextEntry::make('plan.price')
                        ->label('Price')
                        ->formatStateUsing(fn ($state): string => $state !== null ? number_format((float) $state, 2) : 'Custom'),
                    TextEntry::make('current_period_end')
                        ->label('Period Ends')
                        ->dateTime()
                        ->placeholder('—'),
                    TextEntry::make('trial_ends_at')
                        ->label('Trial Ends')
                        ->dateTime()
                        ->placeholder('—'),
                    TextEntry::make('created_at')
                        ->label('Subscribed Since')
                        ->dateTime(),
                ]),

            Section::make('Entitlements')
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
                ->label('Grant Trial/Promo')
                ->icon('heroicon-o-gift')
                ->color('primary')
                ->visible(fn (): bool => ! $this->record->isActive())
                ->schema([
                    Select::make('plan_id')
                        ->label('Plan')
                        ->options(fn () => SubscriptionPlan::where('is_active', true)
                            ->pluck('name', 'id'))
                        ->required(),
                    DatePicker::make('trial_ends_at')
                        ->label('Trial Ends')
                        ->required()
                        ->minDate(now()),
                ])
                ->action(fn (array $data) => $this->grantTrial($data)),

            Action::make('extendPeriod')
                ->label('Extend Period')
                ->icon('heroicon-o-calendar')
                ->color('warning')
                ->visible(fn (): bool => $this->record->isActive())
                ->schema([
                    DatePicker::make('new_period_end')
                        ->label('New Period End Date')
                        ->required()
                        ->minDate(now()->addDay()),
                ])
                ->action(fn (array $data) => $this->extendPeriod($data)),

            Action::make('cancel')
                ->label('Cancel Subscription')
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

        Notification::make()->success()->title('Trial/Promo granted.')->send();

        $this->refreshFormData(['status', 'plan', 'trial_ends_at', 'current_period_end']);
    }

    private function extendPeriod(array $data): void
    {
        /** @var Subscription $subscription */
        $subscription = $this->record;

        $subscription->update([
            'current_period_end' => $data['new_period_end'],
        ]);

        Notification::make()->success()->title('Subscription period extended.')->send();

        $this->refreshFormData(['current_period_end']);
    }

    private function cancelSubscription(): void
    {
        /** @var Subscription $subscription */
        $subscription = $this->record;

        $subscription->update([
            'status' => SubscriptionStatus::Cancelled,
        ]);

        Notification::make()->success()->title('Subscription cancelled.')->send();

        $this->refreshFormData(['status']);
    }
}
