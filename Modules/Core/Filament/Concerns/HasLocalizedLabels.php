<?php

namespace Modules\Core\Filament\Concerns;

/**
 * Resolves a Filament resource's model, navigation and group labels from
 * translation files so the whole admin panel is bilingual (UI-FR-02). The
 * resource declares two keys; everything else is derived.
 *
 * @property-read string $labelTranslationKey  base key holding `.label` + `.plural`
 * @property-read string $navigationGroupTranslationKey  full key for the nav group
 */
trait HasLocalizedLabels
{
    public static function getModelLabel(): string
    {
        return __(static::$labelTranslationKey.'.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __(static::$labelTranslationKey.'.plural');
    }

    public static function getNavigationLabel(): string
    {
        return __(static::$labelTranslationKey.'.plural');
    }

    public static function getNavigationGroup(): ?string
    {
        return __(static::$navigationGroupTranslationKey);
    }
}
