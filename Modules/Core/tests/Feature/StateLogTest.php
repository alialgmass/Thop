<?php

namespace Modules\Core\Tests\Feature;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Modules\Core\Models\StateLog;
use Modules\Core\Support\Enums\StateColor;
use Modules\Core\Support\ModelState\BaseState;
use Modules\Core\Support\Traits\HasStates;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

abstract class ReviewState extends BaseState {}

class DraftState extends ReviewState
{
    public static string $name = 'draft';

    public static function label(): string
    {
        return 'Draft';
    }

    public static function color(): StateColor
    {
        return StateColor::INFO;
    }
}

class PublishedState extends ReviewState
{
    public static string $name = 'published';

    public static function label(): string
    {
        return 'Published';
    }

    public static function color(): StateColor
    {
        return StateColor::SUCCESS;
    }
}

class StatefulThing extends Model
{
    use HasStates;

    public $timestamps = false;

    protected $guarded = [];
}

class StateLogTest extends TestCase
{
    use RefreshDatabase;

    private StatefulThing $thing;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('stateful_things', function (Blueprint $table) {
            $table->id();
        });

        $this->thing = StatefulThing::create();
    }

    #[Test]
    public function a_state_log_persists_and_is_linked_to_its_resource(): void
    {
        $log = StateLog::factory()->create([
            'resource_type' => StatefulThing::class,
            'resource_id' => $this->thing->id,
            'old_state' => null,
            'new_state' => DraftState::class,
            'comment' => 'Initial draft.',
        ]);

        $this->assertDatabaseHas('state_logs', ['id' => $log->id]);
        $this->assertSame(1, $this->thing->stateLogs()->count());
        $this->assertSame('Initial draft.', $this->thing->lastLog?->comment);
    }

    #[Test]
    public function state_history_serializes_the_old_and_last_new_state(): void
    {
        StateLog::factory()->create([
            'resource_type' => StatefulThing::class,
            'resource_id' => $this->thing->id,
            'old_state' => DraftState::class,
            'new_state' => null,
            'created_at' => now()->subMinutes(10),
        ]);

        StateLog::factory()->create([
            'resource_type' => StatefulThing::class,
            'resource_id' => $this->thing->id,
            'old_state' => DraftState::class,
            'new_state' => PublishedState::class,
            'created_at' => now(),
        ]);

        $history = $this->thing->getStateHistory();

        $this->assertCount(3, $history);
        $this->assertSame('Draft', $history[0]['label']);
        $this->assertSame('Draft', $history[1]['label']);
        $this->assertSame('sky', $history[0]['styleClass']);
        $this->assertSame('Published', $history[2]['label']);
    }

    #[Test]
    public function unresolved_state_strings_are_skipped_instead_of_failing(): void
    {
        StateLog::factory()->create([
            'resource_type' => StatefulThing::class,
            'resource_id' => $this->thing->id,
            'old_state' => 'Some\\Unknown\\State',
            'new_state' => PublishedState::class,
        ]);

        $this->assertSame([['label' => 'Published', 'styleClass' => 'green']], $this->thing->getStateHistory());
    }
}
