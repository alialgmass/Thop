<?php

namespace Modules\Core\Support\Enums;

enum StateColor: string
{
    case SUCCESS = 'success';
    case INFO = 'info';
    case WARNING = 'warning';
    case DANGER = 'danger';

    public function styleClass(): string
    {
        return match ($this->value) {
            'success' => 'green',
            'info' => 'sky',
            'warning' => 'yellow',
            'danger' => 'red',
            default => ''
        };
    }

    public function hexadecimal(): string
    {
        return match ($this->value) {
            'success' => '#198754',
            'info' => '#0dcaf0',
            'warning' => '#ffc107',
            'danger' => '#dc3545',
            default => ''
        };
    }
}
