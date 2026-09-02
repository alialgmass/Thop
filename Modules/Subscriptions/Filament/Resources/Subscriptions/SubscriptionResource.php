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

class SubscriptionResource extends Resource
{
    protected static ?string $model = Subscription::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?string $navigationLabel = 'Subscriptions';

    protected static ?int $navigationSort = 25;

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
