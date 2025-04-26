<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Support\Facades\Log;

class MouseMoved implements ShouldBroadcastNow
{
    public function __construct(public $uuid, public $x, public $y) {}
    public function broadcastOn() { return new Channel('ghost-mouse.'.$this->uuid); }
    public function broadcastAs() { return 'mouse.move'; }
}
