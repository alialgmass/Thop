<?php

namespace Modules\Core\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Core\Models\StateLog;

/**
 * @extends Factory<StateLog>
 */
class StateLogFactory extends Factory
{
    protected $model = StateLog::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'resource_type' => StateLog::class,
            'resource_id' => 1,
            'old_state' => null,
            'new_state' => null,
            'comment' => null,
        ];
    }
}
