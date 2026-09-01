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
                    ->label('Business')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('businessAccount.owner.phone')
                    ->label('Owner')
                    ->searchable(),
                TextColumn::make('businessAccount.governorate.name_en')
                    ->label('Governorate')
                    ->toggleable(),
                TextColumn::make('documents_count')
                    ->counts('documents')
                    ->label('Docs')
                    ->badge(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (VerificationRequestStatus $state): string => match ($state) {
                        VerificationRequestStatus::Pending => 'warning',
                        VerificationRequestStatus::Approved => 'success',
                        VerificationRequestStatus::Rejected => 'danger',
                    })
                    ->sortable(),
                TextColumn::make('submitted_at')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('Not submitted'),
                TextColumn::make('reviewer.phone')
                    ->label('Reviewed by')
                    ->placeholder('—')
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(VerificationRequestStatus::class)
                    ->default(VerificationRequestStatus::Pending->value),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
