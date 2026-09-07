<?php

namespace Modules\Admin\Filament\Resources\AuditLogs;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Modules\Admin\Filament\Resources\AuditLogs\Pages\ListAuditLogs;
use Modules\Admin\Filament\Resources\AuditLogs\Tables\AuditLogsTable;
use Modules\Admin\Models\AuditLog;
use Modules\Core\Filament\Concerns\HasLocalizedLabels;

/**
 * Read-only window onto the immutable audit trail (US-ADM-09, BR-ADM-01). No
 * create/edit/delete — the table is append-only and the model blocks writes.
 */
class AuditLogResource extends Resource
{
    use HasLocalizedLabels;

    protected static ?string $model = AuditLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?int $navigationSort = 90;

    protected static string $labelTranslationKey = 'admin::panel.audit_log';

    protected static string $navigationGroupTranslationKey = 'panel.nav.system';

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
