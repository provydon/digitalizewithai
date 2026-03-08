<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;

class DataRecordUpdated implements ShouldBroadcast
{
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public int $dataId,
        public string $status,
        public ?int $batchesDone = null,
        public ?int $batchesTotal = null,
    ) {}

    public function broadcastOn(): array
    {
        return [new Channel('data.'.$this->dataId)];
    }

    public function broadcastAs(): string
    {
        return 'DataRecordUpdated';
    }

    public function broadcastWith(): array
    {
        return [
            'dataId' => $this->dataId,
            'status' => $this->status,
            'batchesDone' => $this->batchesDone,
            'batchesTotal' => $this->batchesTotal,
        ];
    }
}
