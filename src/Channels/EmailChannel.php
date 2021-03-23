<?php

namespace Smbear\WebPowerEmail\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\AnonymousNotifiable;

class EmailChannel
{
    public function send($notifiable,Notification $notification)
    {
        if($notifiable instanceof AnonymousNotifiable){
            $class = new \stdClass();

            $class->email  = $notifiable->routes[__CLASS__];
            $notifiable = $class;
        }

        $notification->toEmail($notifiable);
    }
}