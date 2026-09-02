<?php

namespace Modules\Core\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class TestEvent implements ShouldBroadcast
{
    use InteractsWithSockets;

    public function __construct(public $message) {}

    public function broadcastOn()
    {
        return new Channel('test-channel'); // Public channel
    }

    public function broadcastAs()
    {
        return 'TestEvent'; // Optional: sets custom event name
    }

    public function broadcastWith()
    {
        return ['message' => $this->message];
    }
}
