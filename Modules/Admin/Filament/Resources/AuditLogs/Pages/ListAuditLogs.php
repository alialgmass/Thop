<?php

namespace Modules\Admin\Filament\Resources\AuditLogs\Pages;

use Filament\Resources\Pages\ListRecords;
use Modules\Admin\Filament\Resources\AuditLogs\AuditLogResource;

class ListAuditLogs extends ListRecords
{
    protected static string $resource = AuditLogResource::class;
}
