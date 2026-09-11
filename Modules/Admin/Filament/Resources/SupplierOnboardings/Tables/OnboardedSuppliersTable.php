<?php

namespace Modules\Admin\Filament\Resources\SupplierOnboardings\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class OnboardedSuppliersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('company_name')
                    ->label(__('admin::panel.supplier_onboarding.fields.company_name'))
                    ->searchable(),
                TextColumn::make('owner.phone')
                    ->label(__('admin::panel.supplier_onboarding.fields.phone')),
                TextColumn::make('owner.account_type')
                    ->label(__('admin::panel.supplier_onboarding.fields.account_type'))
                    ->badge(),
                TextColumn::make('verification_status')
                    ->label(__('admin::panel.supplier_onboarding.fields.verification_status'))
                    ->badge(),
                TextColumn::make('created_at')
                    ->label(__('panel.common.created_at'))
                    ->dateTime()
                    ->sortable(),
            ]);
    }
}
