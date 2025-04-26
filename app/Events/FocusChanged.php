<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FocusChanged implements ShouldBroadcastNow {
    public function __construct(public $uuid, public $name) {}
    public function broadcastOn() { return new Channel('ghost-mouse.' . $this->uuid); }
    public function broadcastAs() { return 'focus.change'; }
}
