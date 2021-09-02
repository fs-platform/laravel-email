<?php

namespace Smbear\WebPowerEmail\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Notification;
use Smbear\WebPowerEmail\Enums\WebPowerEmailEnum;
use Smbear\WebPowerEmail\Exceptions\EmailSendException;
use Smbear\WebPowerEmail\Exceptions\EmailTokenException;
use Smbear\WebPowerEmail\Exceptions\EmailTemplateException;

class EmailOperationService
{
    /**
     * @Notes:发送邮件
     *
     * @param $params
     * @param $email
     * @return bool
     * @throws \Throwable
     * @Author: smile
     * @Date: 2021/8/4
     * @Time: 20:32
     */
    public function send($params, $email): bool
    {
        try{
            $templateArray = config('webpower.template_id');

            $isUseLargeFiled = config('webpower.webpower.is_use_large_filed');

            if (!$templateArray || !$isUseLargeFiled) {
                $mailingId = $this->createTemplate($params);
            } else {
                $mailingId = $templateArray[array_rand($templateArray)];
            }

            if (!$mailingId || !is_int($mailingId)) {
                throw new EmailTemplateException('邮件模板id 获取异常');
            }

            if ($isUseLargeFiled) {
                $result = $this->sendEmailByLargeField($email, $mailingId, $params);
            } else {
                $result = $this->sendEmailByCreateTemplate($email, $mailingId);
            }

            if ($result == false) {
                throw new EmailSendException('email 发送异常');
            }

            return true;
        }catch (\Throwable $exception){
            if ($exception instanceof EmailSendException && $exception->getCode() == 409) {
                Log::channel('webpower')
                    ->emergency('模板获取id 获取异常'.$exception->getMessage());

                return true;
            } else {
                Log::channel('webpower')
                    ->emergency('邮件发送异常'.$exception->getMessage());

                throw $exception;
            }
        }
    }

    /**
     * Notes: 每次发送邮件创建模版
     *
     * author: Aron.Yao
     * Date: 2021/7/27
     * Time: 5:12 下午
     * @param string $email
     * @param int $mailingId
     * @return bool
     * @throws EmailSendException
     */
    protected function sendEmailByCreateTemplate(string $email, int $mailingId): bool
    {
        $contacts = [
            [
                'email' => $email,
                'lang' => config('webpower.webpower.lang'),
                'custom' => [
                    [
                        'field' => config('webpower.webpower.field'),
                        'value' => config('webpower.webpower.value')
                    ]
                ]
            ]
        ];

        $body = [
            'mailingId'           => $mailingId,
            'groups'              => config('webpower.webpower.group', [81]),
            'overwrite'           => true,
            'addDuplicateToGroup' => true,
            'contacts'            => $contacts
        ];

        try {
            $token = $this->getMailToken();

            $response = Http::withHeaders([
                'Accept'        => 'application/json',
                'Content-Type'  => 'application/json',
                'Authorization' => $token
            ])
                ->timeout(30)
                ->withBody(json_encode($body), 'raw')
                ->post(config('webpower.webpower.send_url'));

            if ($response->successful()) {
                $data = $response->json();

                if (!empty($data) && is_array($data)) {
                    return true;
                }
            }

            $response->throw();
        } catch (\Throwable $exception) {
            throw new EmailSendException($exception->getMessage(),$exception->getCode());
        }
    }

    /**
     * Notes: 根据大字段发送邮件
     *
     * author: Aron.Yao
     * Date: 2021/7/27
     * Time: 5:29 下午
     * @param string $email
     * @param int $mailingId
     * @param array $params
     * @return bool
     * @throws EmailSendException
     */
    public function sendEmailByLargeField(string $email, int $mailingId, array $params = []): bool
    {
        $body = [
            "mailingId"   => $mailingId,
            "attachments" => [],
            "contact" => [
                "email"     => $email,
                "mobile_nr" => "",
                "lang"      => "cn",
                "custom" => [
                    [
                        "field" => "mail_subject",
                        "value" => $params['subject'],
                    ],
                    [
                        "field" => "sender_name",
                        "value" => $params['sender'] ?? config('webpower.webpower.from_name'),
                    ],
                ],
            ],
            "overrideDuplicateAndSend" => true,
            "extraContactData"         => [
                [
                    'field' => 'DMD_extra1',
                    'value' => $params['body'] ?? ''
                ]
            ]
        ];

        try {
            $token = $this->getMailToken();

            $response = Http::withHeaders([
                'Accept'        => 'application/json',
                'Content-Type'  => 'application/json',
                'Authorization' => $token
               ])
                ->timeout(30)
                ->withBody(json_encode($body), 'raw')
                ->post(config('webpower.webpower.send_large_url'));

            if ($response->successful()) {
                $data = $response->json();

                if (!empty($data) && is_array($data)) {
                    return true;
                }
            }

            $response->throw();
        } catch (\Throwable $exception) {
            throw new EmailSendException($exception->getMessage(),$exception->getCode());
        }
    }

    /**
     * 创建邮件模板，并获取到模块id
     * @param array $params
     * @return int
     * @throws EmailTemplateException
     */
    public function createTemplate(array $params): int
    {
        $body = [
            'name'          => $params['name'] ?? $params['subject'] . ':' . date('Y-m-d h:i:s', time()),
            'lang'          => config('webpower.webpower.lang'),
            'subject'       => $params['subject'],
            "from_name"     => $params['sender'] ?? config('webpower.webpower.from_name'),
            "forward_id"    => config('webpower.webpower.forward_id'),
            "reply_id"      => config('webpower.webpower.reply_id'),
            "plaintext_msg" => config('webpower.webpower.plaintext_msg'),
            "html_msg"      => $params['body']
        ];

        try {
            $token = $this->getMailToken();

            $response = Http::withHeaders([
                'Accept'        => 'application/json',
                'Content-Type'  => 'application/json',
                'Authorization' => $token
            ])
                ->timeout(30)
                ->withBody(json_encode($body), 'raw')
                ->post(config('webpower.webpower.request_url'));

            if ($response->successful()) {
                $data = $response->json();

                if (!empty($data) && is_array($data)) {

                    if (isset($data['id']) && is_int($data['id'])) {
                        return $data['id'];
                    }
                }
            }

            $response->throw();
        } catch (\Throwable $exception) {
            throw new EmailTemplateException($exception->getMessage());
        }
    }

    /**
     * @Notes:获取到token
     *
     * @throws EmailTokenException
     * @Author: smile
     * @Date: 2021/8/4
     * @Time: 19:26
     */
    public function requestAccessMailToken()
    {
        $params = [
            'grant_type'    => config('webpower.token.grant_type'),
            'client_id'     => config('webpower.token.client_id'),
            'client_secret' => config('webpower.token.client_secret'),
            'scope'         => config('webpower.token.scope'),
        ];

        try {
            $response = Http::asForm()
                ->timeout(30)
                ->post(config('webpower.token.oath_token_url'), $params);

            if ($response->successful()) {
                $data = $response->json();

                if (!empty($data) && is_array($data)) {
                    $token = $data['access_token'];

                    Redis::setex(WebPowerEmailEnum::EMAIL_TOKEN_KEY, WebPowerEmailEnum::TOKEN_EXPIRE_TIME, $token);
                }
            }

            $response->throw();
        } catch (\Throwable $exception) {
            throw new EmailTokenException($exception->getMessage());
        }
    }

    /**
     * 获取到邮件发送的token
     * @return string
     * @throws EmailTokenException
     */
    public function getMailToken(): string
    {
        if (!Redis::exists(WebPowerEmailEnum::EMAIL_TOKEN_KEY) || empty(Redis::get(WebPowerEmailEnum::EMAIL_TOKEN_KEY))) {
            $this->requestAccessMailToken();
        }

        $token = Redis::get(WebPowerEmailEnum::EMAIL_TOKEN_KEY);

        if (empty($token)) {
            throw new EmailTokenException('token 数据获取失败');
        }

        return WebPowerEmailEnum::BEARER . $token;
    }
}
