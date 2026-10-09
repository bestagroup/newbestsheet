<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Message $message;

    public function __construct(Message $message)
    {
        $this->message = $message->load([
            'sender:id,name',
            'attachments',
        ]);
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('conversation.'.$this->message->conversation_id);
    }

    public function broadcastAs(): string
    {
        return 'message.sent';
    }

    public function broadcastWith(): array
    {
        return [
            'conversation_id' => $this->message->conversation_id,
            'parent_id' => $this->message->parent_id,
            'message' => [
                'id' => $this->message->id,
                'senderId' => $this->message->sender_id,
                'sender' => $this->message->sender->name,
                'body' => (string) $this->message->body,
                'time' => $this->message->created_at->toDateTimeString(),
                'attachments' => $this->message->attachments->map(fn ($a) => [
                    'id' => $a->id,
                    'name' => $a->original_name,
                    'url' => $a->url,
                    'size' => $a->size,
                    'mime' => $a->mime_type,
                ])->values(),
            ],
        ];
    }
}
