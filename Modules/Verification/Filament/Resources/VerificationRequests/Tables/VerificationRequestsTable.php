<?php

namespace Modules\Verification\Filament\Resources\VerificationRequests\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Modules\Verification\Enums\VerificationRequestStatus;

class VerificationRequestsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('submitted_at', 'desc')
            ->columns([
                TextColumn::make('businessAccount.company_name')
                    ->label(__('verification::panel.columns.business'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('businessAccount.owner.phone')
                    ->label(__('verification::panel.columns.owner'))
                    ->searchable(),
                TextColumn::make('businessAccount.governorate.name_en')
                    ->label(__('verification::panel.columns.governorate'))
                    ->toggleable(),
                TextColumn::make('documents_count')
                    ->counts('documents')
                    ->label(__('verification::panel.columns.docs'))
                    ->badge(),
                TextColumn::make('status')
                    ->label(__('verification::panel.columns.status'))
                    ->badge()
                    ->color(fn (VerificationRequestStatus $state): string => match ($state) {
                        VerificationRequestStatus::Pending => 'warning',
                        VerificationRequestStatus::Approved => 'success',
                        VerificationRequestStatus::Rejected => 'danger',
                    })
                    ->sortable(),
                TextColumn::make('submitted_at')
                    ->label(__('verification::panel.columns.submitted_at'))
                    ->dateTime()
                    ->sortable()
                    ->placeholder(__('verification::panel.columns.not_submitted')),
                TextColumn::make('reviewer.phone')
                    ->label(__('verification::panel.columns.reviewed_by'))
                    ->placeholder('—')
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('verification::panel.columns.status'))
                    ->options(VerificationRequestStatus::class)
                    ->default(VerificationRequestStatus::Pending->value),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
