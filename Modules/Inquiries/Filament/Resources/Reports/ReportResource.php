<?php

namespace Modules\Inquiries\Filament\Resources\Reports;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Modules\Core\Filament\Concerns\HasLocalizedLabels;
use Modules\Inquiries\Enums\ReportStatus;
use Modules\Inquiries\Filament\Resources\Reports\Pages\ListReports;
use Modules\Inquiries\Filament\Resources\Reports\Pages\ViewReport;
use Modules\Inquiries\Filament\Resources\Reports\Tables\ReportsTable;
use Modules\Inquiries\Models\Report;

/**
 * Admin dispute/report queue (US-ADM-08, Phase 9 · T9). Read + resolve
 * only — reports are created by either party through the API, never here.
 */
class ReportResource extends Resource
{
    use HasLocalizedLabels;

    protected static ?string $model = Report::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFlag;

    protected static ?int $navigationSort = 14;

    protected static string $labelTranslationKey = 'inquiries::panel.report';

    protected static string $navigationGroupTranslationKey = 'panel.nav.moderation';

    public static function table(Table $table): Table
    {
        return ReportsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListReports::route('/'),
            'view' => ViewReport::route('/{record}'),
        ];
    }

    /**
     * @return Builder<Report>
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withPartiesEagerLoaded()->openFirst();
    }

    public static function getNavigationBadge(): ?string
    {
        $open = static::getModel()::query()->where('status', ReportStatus::Open)->count();

        return $open > 0 ? (string) $open : null;
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
