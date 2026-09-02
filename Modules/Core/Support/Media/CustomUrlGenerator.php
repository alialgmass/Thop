<?php

namespace Modules\Core\Support\Media;

use Spatie\MediaLibrary\Support\UrlGenerator\DefaultUrlGenerator;

class CustomUrlGenerator extends DefaultUrlGenerator
{
    protected bool $withTemporaryUrl = true;

    public function getUrl(): string
    {
        if (in_array($this->getDiskName(), ['s3', 'gcs']) && $this->withTemporaryUrl) {
            return $this->getTemporaryUrl(
                now()->addSeconds(config('media-library.temporary_url_duration')),
                ['version' => 'v4']
            );
        }

        return parent::getUrl();
    }

    public function withTemporaryUrl(bool $status = true): static
    {
        $this->withTemporaryUrl = $status;

        return $this;
    }
}
