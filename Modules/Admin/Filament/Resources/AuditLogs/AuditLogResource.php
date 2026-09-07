<?php

namespace Modules\Admin\Filament\Resources\AuditLogs;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Modules\Admin\Filament\Resources\AuditLogs\Pages\ListAuditLogs;
use Modules\Admin\Filament\Resources\AuditLogs\Tables\AuditLogsTable;
use Modules\Admin\Models\AuditLog;
use UnitEnum;

/**
 * Read-only window onto the immutable audit trail (US-ADM-09, BR-ADM-01). No
 * create/edit/delete — the table is append-only and the model blocks writes.
 */
class AuditLogResource extends Resource
{
    protected static ?string $model = AuditLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static string|UnitEnum|null $navigationGroup = null;

    protected static ?int $navigationSort = 90;

    public static function getNavigationGroup(): ?string
    {
        return __('panel.nav.system');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin::panel.audit_log.plural');
    }

    public static function getModelLabel(): string
    {
        return __('admin::panel.audit_log.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin::panel.audit_log.plural');
    }

    public static function table(Table $table): Table
    {
        return AuditLogsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAuditLogs::route('/'),
        ];
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
