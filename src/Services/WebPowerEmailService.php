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

        if (isset($params['name']) && (empty($params['name']) || !is_string($params['name']))){
            unset($params['name']);
        }

        if (isset($params['sender']) && (empty($params['sender']) || !is_string($params['sender']))){
            unset($params['sender']);
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