<?php

namespace AscentCreative\Approval\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

use AscentCreative\Approval\Models\ApprovalItem;
use App\Models\User;


class ItemApproved
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $item = null;
    public $user = null;
    public $ip = null;


    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(ApprovalItem $item, User $user=null, $ip=null)
    {
        $this->item = $item;

        if(is_null($user)) {
            $user = auth()->user();
        }
        $this->user = $user;

        if(is_null($ip)) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        $this->ip = $ip;

    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
  /*  public function broadcastOn()
    {
        return new PrivateChannel('channel-name');
    } */
}
