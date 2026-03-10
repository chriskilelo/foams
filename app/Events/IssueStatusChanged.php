<?php

namespace App\Events;

use App\Enums\IssueStatus;
use App\Models\Issue;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class IssueStatusChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Issue $issue,
        public IssueStatus $previousStatus,
        public IssueStatus $newStatus,
        public User $actor,
    ) {}

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
        return 'issue.status_changed';
    }
}
