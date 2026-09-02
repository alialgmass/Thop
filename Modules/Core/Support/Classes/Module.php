<?php

namespace Modules\Core\Support\Classes;

class Module
{
    public static function registered(): array
    {

        return [
            'Admin',
            'Auth',
            'Businesses',
            'Taxonomy',
            'Verification',
            'Core',
        ];
    }
}
