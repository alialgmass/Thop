<?php

namespace Modules\Core\Support\Media;

use Spatie\MediaLibrary\MediaCollections\Models\Media as SpatieMedia;
use Spatie\MediaLibrary\Support\UrlGenerator\UrlGeneratorFactory;

/**
 * @mixin SpatieMedia
 */
class Media extends SpatieMedia
{
    public function getUrl(string $conversionName = '', $withTemporaryUrl = true): string
    {
        $urlGenerator = UrlGeneratorFactory::createForMedia($this, $conversionName)
            ->withTemporaryUrl($withTemporaryUrl);

        return $urlGenerator->getUrl();
    }
}
