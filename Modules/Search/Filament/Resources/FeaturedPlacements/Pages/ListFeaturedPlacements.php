<?php

namespace Modules\Search\Filament\Resources\FeaturedPlacements\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Modules\Search\Filament\Resources\FeaturedPlacements\FeaturedPlacementResource;

class ListFeaturedPlacements extends ListRecords
{
    protected static string $resource = FeaturedPlacementResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
