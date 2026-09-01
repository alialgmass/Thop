<?php

namespace Modules\Verification\Filament\Resources\VerificationRequests\Pages;

use Filament\Resources\Pages\ListRecords;
use Modules\Verification\Filament\Resources\VerificationRequests\VerificationRequestResource;

class ListVerificationRequests extends ListRecords
{
    protected static string $resource = VerificationRequestResource::class;
}
