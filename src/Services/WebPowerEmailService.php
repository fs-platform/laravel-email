<?php

namespace Smbear\WebPowerEmail\Services;

use Illuminate\Support\Facades\Notification;
use Smbear\WebPowerEmail\Channels\EmailChannel;
use Smbear\WebPowerEmail\Interfaces\WebPowerInterfaces;
use Smbear\WebPowerEmail\Exceptions\EmailParamsException;
use Smbear\WebPowerEmail\Notifications\EmailNotification;

class WebPowerEmailService implements WebPowerInterfaces
{
    /**
     * 发送邮件
     * @param string $emails
     * @param array $params
     * @throws EmailParamsException
     */
    public function sendEmail($emails,array $params)
    {
        if (!custom_array_key($params,'body,subject',true)){
            throw new EmailParamsException('参数异常');
        }

        if(is_array($emails)){
            foreach ($emails as $email){
                Notification::route(EmailChannel::class,$email)->notify(new EmailNotification($params));
            }
        }

        if(is_string($emails)){
            Notification::route(EmailChannel::class,$emails)->notify(new EmailNotification($params));
        }
    }
}