<?php

namespace Modules\Core\Support\Traits;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Modules\Core\Models\StateLog;
use Modules\Core\Support\ModelState\BaseState;

trait HasStates
{
    use \Spatie\ModelStates\HasStates;

    public function stateLogs(): MorphMany
    {
        return $this->morphMany(StateLog::class, 'resource');
    }

    public function lastLog(): MorphOne
    {
        return $this->morphOne(StateLog::class, 'resource')->latest();
    }

    public function getStateHistory(): array
    {
        $logs = $this->stateLogs()->get();

        $history = [];
        foreach ($logs as $index => $log) {
            if ($log->old_state && $state = $this->resolveStateValue($log->old_state)) {
                $history[] = $state->toArray();
            }

            if ($index === count($logs) - 1 && $log->new_state && $state = $this->resolveStateValue($log->new_state)) {
                $history[] = $state->toArray();
            }
        }

        return $history;
    }

    private function resolveStateValue(mixed $value): ?BaseState
    {
        if ($value instanceof BaseState) {
            return $value;
        }

        if (is_string($value) && is_subclass_of($value, BaseState::class)) {
            return new $value($this);
        }

        return null;
    }
}
