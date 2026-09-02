<?php

namespace Modules\Core\Support\Traits;

use Spatie\Translatable\HasTranslations as SpatieHasTranslations;

trait HasTranslations
{
    use SpatieHasTranslations;

    public function getTranslationsWithNonEmpty(?string $key = null, ?array $allowedLocales = null): ?array
    {
        return collect($this->getTranslations($key, $allowedLocales))
            ->transform(function ($value) {
                if (empty($value)) {
                    return null;
                }

                return $value;
            })->toArray();
    }

    public function getLocale(): string
    {
        return $this->translationLocale ?: config('app.locale') ?? app()->getFallbackLocale();
    }

    protected function asJson($value)
    {
        return json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
    }
}
