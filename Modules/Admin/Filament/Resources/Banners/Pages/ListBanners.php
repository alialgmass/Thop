<?php

namespace Modules\Admin\Filament\Resources\Banners\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Modules\Admin\Filament\Resources\Banners\BannerResource;

class ListBanners extends ListRecords
{
    protected static string $resource = BannerResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
