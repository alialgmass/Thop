<?php

namespace Modules\Subscriptions\Filament\Resources\SubscriptionPlans;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Modules\Subscriptions\Filament\Resources\SubscriptionPlans\Pages\CreateSubscriptionPlan;
use Modules\Subscriptions\Filament\Resources\SubscriptionPlans\Pages\EditSubscriptionPlan;
use Modules\Subscriptions\Filament\Resources\SubscriptionPlans\Pages\ListSubscriptionPlans;
use Modules\Subscriptions\Filament\Resources\SubscriptionPlans\Schemas\SubscriptionPlanForm;
use Modules\Subscriptions\Filament\Resources\SubscriptionPlans\Tables\SubscriptionPlansTable;
use Modules\Subscriptions\Models\SubscriptionPlan;
use UnitEnum;

class SubscriptionPlanResource extends Resource
{
    protected static ?string $model = SubscriptionPlan::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCreditCard;

    protected static string|UnitEnum|null $navigationGroup = null;

    protected static ?int $navigationSort = 20;

    public static function getNavigationGroup(): ?string
    {
        return __('panel.nav.billing');
    }

    public static function getNavigationLabel(): string
    {
        return __('subscriptions::panel.plan.plural');
    }

    public static function getModelLabel(): string
    {
        return __('subscriptions::panel.plan.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('subscriptions::panel.plan.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return SubscriptionPlanForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SubscriptionPlansTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSubscriptionPlans::route('/'),
            'create' => CreateSubscriptionPlan::route('/create'),
            'edit' => EditSubscriptionPlan::route('/{record}/edit'),
        ];
    }
}
