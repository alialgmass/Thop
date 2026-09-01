<?php

namespace Modules\Verification\Filament\Resources\VerificationRequests;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Modules\Verification\Enums\VerificationRequestStatus;
use Modules\Verification\Filament\Resources\VerificationRequests\Pages\ListVerificationRequests;
use Modules\Verification\Filament\Resources\VerificationRequests\Pages\ViewVerificationRequest;
use Modules\Verification\Filament\Resources\VerificationRequests\Tables\VerificationRequestsTable;
use Modules\Verification\Models\VerificationRequest;

/**
 * Admin review queue for business verification (US-ADM-01). Read + decide only —
 * requests are created by businesses through the API, never here.
 */
class VerificationRequestResource extends Resource
{
    protected static ?string $model = VerificationRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static ?string $navigationLabel = 'Verification requests';

    protected static ?int $navigationSort = 10;

    public static function table(Table $table): Table
    {
        return VerificationRequestsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListVerificationRequests::route('/'),
            'view' => ViewVerificationRequest::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['businessAccount.owner', 'businessAccount.governorate', 'documents.documentType', 'reviewer']);
    }

    public static function getNavigationBadge(): ?string
    {
        $pending = static::getModel()::query()
            ->where('status', VerificationRequestStatus::Pending)
            ->whereNotNull('submitted_at')
            ->count();

        return $pending > 0 ? (string) $pending : null;
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
