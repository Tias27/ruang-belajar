<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StudyRoomMessageChunkGenerated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $studyRoomId;
    public $senderId;
    public $chunk;
    public $tempMsgId;

    /**
     * Create a new event instance.
     */
    public function __construct($studyRoomId, $senderId, $chunk, $tempMsgId)
    {
        $this->studyRoomId = $studyRoomId;
        $this->senderId = $senderId;
        $this->chunk = $chunk;
        $this->tempMsgId = $tempMsgId;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PresenceChannel('study-room.' . $this->studyRoomId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'message.chunk';
    }
}
