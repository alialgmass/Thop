<?php

namespace Modules\Subscriptions\Filament\Resources\Subscriptions;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Modules\Subscriptions\Filament\Resources\Subscriptions\Pages\ListSubscriptions;
use Modules\Subscriptions\Filament\Resources\Subscriptions\Pages\ViewSubscription;
use Modules\Subscriptions\Filament\Resources\Subscriptions\Tables\SubscriptionsTable;
use Modules\Subscriptions\Models\Subscription;
use UnitEnum;

class SubscriptionResource extends Resource
{
    protected static ?string $model = Subscription::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static string|UnitEnum|null $navigationGroup = null;

    protected static ?int $navigationSort = 25;

    public static function getNavigationGroup(): ?string
    {
        return __('panel.nav.billing');
    }

    public static function getNavigationLabel(): string
    {
        return __('subscriptions::panel.subscription.plural');
    }

    public static function getModelLabel(): string
    {
        return __('subscriptions::panel.subscription.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('subscriptions::panel.subscription.plural');
    }

    public static function table(Table $table): Table
    {
        return SubscriptionsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSubscriptions::route('/'),
            'view' => ViewSubscription::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['businessAccount.owner', 'plan']);
    }

    public static function getNavigationBadge(): ?string
    {
        $active = static::getModel()::query()
            ->where('status', 'active')
            ->count();

        return $active > 0 ? (string) $active : null;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(mixed $record): bool
    {
        return false;
    }

    public static function canDelete(mixed $record): bool
    {
        return false;
    }
}
