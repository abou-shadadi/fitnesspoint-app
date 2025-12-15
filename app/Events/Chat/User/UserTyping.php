<?php

namespace App\Events\Chat\User;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserTyping implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $userId;
    public $userChatId;

    public function __construct($userId, $userChatId)
    {
        $this->userId = $userId;
        $this->userChatId = $userChatId;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('user-typing-channel-'.$this->userChatId.'-'.$this->userId);
    }

    public function broadcastAs()
    {
        return 'UserTyping';
    }
}
