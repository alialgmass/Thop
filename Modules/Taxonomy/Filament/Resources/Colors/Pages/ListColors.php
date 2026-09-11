<?php

namespace Modules\Taxonomy\Filament\Resources\Colors\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Modules\Taxonomy\Filament\Resources\Colors\ColorResource;

class ListColors extends ListRecords
{
    protected static string $resource = ColorResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
