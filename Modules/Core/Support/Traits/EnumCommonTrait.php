<?php

namespace Modules\Core\Support\Traits;

trait EnumCommonTrait
{
    public function label(): string
    {
        $className = str(class_basename(static::class))->trim('Enum')->snake()->toString();

        return __("enum.{$className}.{$this->value}");
    }

    public static function toArray(): array
    {
        $array = [];

        foreach (static::cases() as $definition) {
            $array[$definition->value] = $definition->label();
        }

        return $array;
    }
}
