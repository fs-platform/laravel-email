<?php

namespace Smbear\WebPowerEmail\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Smbear\WebPowerEmail\Channels\EmailChannel;
use Smbear\WebPowerEmail\Exceptions\EmailSendException;
use Smbear\WebPowerEmail\Facades\EmailsOperationFacades;

class EmailNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public array $params;

    public $connection;

    public $queue;

    public $timeout;

    public $tries;

    public $sleep;

    public function __construct(array $params)
    {
        $this->params = $params;

        $this->queue = config('webpower.queue.queue');

        $this->connection = config('webpower.queue.connection');

        $this->timeout = config('webpower.queue.timeout');

        $this->tries = config('webpower.queue.tries');

        $this->sleep = config('webpower.queue.sleep');
    }

    public function via($notifiable): array
    {
        return [EmailChannel::class];
    }

    /**
     * 发送邮件
     * @param $notifiable
     * @return array|null
     * @throws EmailSendException
     */
    public function toEmail($notifiable) :?array
    {
        try{
            if($notifiable->email && filter_var($notifiable->email,FILTER_VALIDATE_EMAIL)){

                $result = EmailsOperationFacades::send($this->params,$notifiable->email);

                if($result){
                    return custom_return_success('发送成功');
                }

                return custom_return_error('发送失败');
            }

            return custom_return_error('邮箱不存在，或格式错误');
        }catch (\Exception $exception){
            throw new EmailSendException($exception->getMessage());
        }
    }
}