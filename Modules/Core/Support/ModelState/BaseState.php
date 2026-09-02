<?php

namespace Modules\Core\Support\ModelState;

use Modules\Core\Support\Enums\StateColor;
use Spatie\ModelStates\State;

abstract class BaseState extends State
{
    private ?string $comment = null;

    abstract public static function label(): string;

    abstract public static function color(): StateColor;

    public static function name(): string
    {
        return get_called_class()::$name;
    }

    public function toArray(): array
    {
        return [
            'label' => get_called_class()::label(),
            'styleClass' => get_called_class()::color()->styleClass(),
        ];
    }

    public function withComment(string|array|null $comment): static
    {
        if (is_array($comment)) {
            $comment = implode(' ', $comment);
        }

        $this->comment = $comment;

        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function transitionTo($newState, ...$transitionArgs)
    {
        $from = static::getMorphClass();
        $to = $newState::getMorphClass();
        if ($from == $to) {
            return;
        }

        return parent::transitionTo($newState, $transitionArgs);
    }
}
