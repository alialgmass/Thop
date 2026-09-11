<?php

namespace Modules\Taxonomy\Filament\Resources\FabricTypes\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Modules\Taxonomy\Filament\Resources\FabricTypes\FabricTypeResource;

class ListFabricTypes extends ListRecords
{
    protected static string $resource = FabricTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
