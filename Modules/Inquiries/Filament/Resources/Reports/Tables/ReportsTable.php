<?php

namespace Modules\Inquiries\Filament\Resources\Reports\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Modules\Inquiries\Enums\ReportStatus;

class ReportsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('reportable_type')
                    ->label(__('inquiries::panel.report.columns.type'))
                    ->badge(),
                TextColumn::make('reason')
                    ->label(__('inquiries::panel.report.columns.reason'))
                    ->limit(60),
                TextColumn::make('reporter.phone')
                    ->label(__('inquiries::panel.report.columns.reporter')),
                TextColumn::make('status')
                    ->label(__('inquiries::panel.report.columns.status'))
                    ->badge()
                    ->color(fn (ReportStatus $state): string => match ($state) {
                        ReportStatus::Open => 'warning',
                        ReportStatus::Investigating => 'info',
                        ReportStatus::Resolved => 'success',
                        ReportStatus::Dismissed => 'gray',
                    }),
                TextColumn::make('created_at')
                    ->label(__('inquiries::panel.report.columns.reported_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('inquiries::panel.report.columns.status'))
                    ->options(ReportStatus::class),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
