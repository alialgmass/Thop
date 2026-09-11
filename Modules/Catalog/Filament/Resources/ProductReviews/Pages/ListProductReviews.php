<?php

namespace Modules\Catalog\Filament\Resources\ProductReviews\Pages;

use Filament\Resources\Pages\ListRecords;
use Modules\Catalog\Filament\Resources\ProductReviews\ProductReviewResource;

class ListProductReviews extends ListRecords
{
    protected static string $resource = ProductReviewResource::class;
}
