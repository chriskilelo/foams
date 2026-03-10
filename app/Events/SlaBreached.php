<?php

namespace App\Events;

use App\Models\Issue;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SlaBreached implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Issue $issue) {}

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        $regionId = $this->issue->county->region_id;

        return [
            new PrivateChannel("region.{$regionId}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'sla.breached';
    }
}
