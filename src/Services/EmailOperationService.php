<?php

namespace Smbear\WebPowerEmail\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Notification;
use Smbear\WebPowerEmail\Enums\WebPowerEmailEnum;
use Smbear\WebPowerEmail\Exceptions\EmailSendException;

class EmailOperationService
{
    /**
     * @param $params
     * @param $email
     * @return bool
     * @throws EmailSendException
     */
    public function send($params, $email): bool
    {
        $templateArray = config('webpower.template_id');
        $isUseLargeFiled = config('webpower.webpower.isUseLargeFiled');
        if (!$templateArray || !$isUseLargeFiled) {
            $mailingId = $this->createTemplate($params);
        } else {
            $mailingId = $templateArray[array_rand($templateArray)];
        }

        if (!$mailingId || !is_int($mailingId)) {

            throw new EmailSendException('邮件模板id 获取异常');
        }
        if ($isUseLargeFiled) {
            $result = $this->sendEmailByLargeField($email, $mailingId, $params);
        } else {
            $result = $this->sendEmailByCreateTemplate($email, $mailingId);
        }

        if (!$result) {
            throw new EmailSendException('邮件发送异常');
        }

        return true;
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
            'mailingId' => $mailingId,
            'groups' => config('webpower.webpower.group', [81]),
            'overwrite' => true,
            'addDuplicateToGroup' => true,
            'contacts' => $contacts
        ];

        try {
            $token = $this->getMailToken();

            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'Authorization' => $token
            ])
                ->withBody(json_encode($body), 'raw')
                ->post(config('webpower.webpower.send_url'));

            if ($response->successful()) {
                $response = $response->json();

                if (!empty($response) && is_array($response)) {
                    return true;
                }
            }

            $response->throw();
        } catch (\Exception $exception) {
            throw new EmailSendException($exception->getMessage());
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
     */
    protected function sendEmailByLargeField(string $email, int $mailingId, array $params = []): bool
    {
        $content = $params['body'] ?? '';
        $title = $params['name'] ?? $params['subject'] . ':' . date('Y-m-d h:i:s', time());
        $sender = $params['sender'] ?? config('webpower.webpower.from_name');
        $body = [
            "mailingId" => $mailingId,
            "attachments" => [],
            "contact" => [
                "email" => $email,
                "mobile_nr" => "",
                "lang" => "cn",
                "custom" => [
                    [
                        "field" => "mail_subject",
                        "value" => $title,
                    ],
                    [
                        "field" => "sender_name",
                        "value" => $sender,
                    ],
                ],
            ],
            "overrideDuplicateAndSend" => true,
            "extraContactData" => array(array("field" => 'DMD_extra1', "value" => $content))
        ];
        try {
            $token = $this->getMailToken();
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'Authorization' => $token
               ])->timeout(30)
                ->withBody(json_encode($body), 'raw')
                ->post(config('webpower.webpower.send_large_url'));
            if ($response->successful()) {
                $response = $response->json();
                if (!empty($response) && is_array($response)) {
                    return true;
                }
            }

            $response->throw();
        } catch (\Throwable $e) {
            $code = $e->getCode();
            L($e);
            //当前联系人已经在第三方创建
            if (in_array($code, [409])) {
                return true;
            }
            return false;
        }
    }

    /**
     * 创建邮件模板，并获取到模块id
     * @param array $params
     * @return int
     * @throws EmailSendException
     */
    public function createTemplate(array $params): int
    {
        $body = [
            'name' => $params['name'] ?? $params['subject'] . ':' . date('Y-m-d h:i:s', time()),
            'lang' => config('webpower.webpower.lang'),
            'subject' => $params['subject'],
            "from_name" => $params['sender'] ?? config('webpower.webpower.from_name'),
            "forward_id" => config('webpower.webpower.forward_id'),
            "reply_id" => config('webpower.webpower.reply_id'),
            "plaintext_msg" => config('webpower.webpower.plaintext_msg'),
            "html_msg" => $params['body']
        ];

        try {
            $token = $this->getMailToken();

            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'Authorization' => $token
            ])
                ->withBody(json_encode($body), 'raw')
                ->post(config('webpower.webpower.request_url'));

            if ($response->successful()) {
                $response = $response->json();

                if (!empty($response) && is_array($response)) {

                    if (isset($response['id']) && is_int($response['id'])) {
                        return $response['id'];
                    }
                }
            }

            $response->throw();
        } catch (\Exception $exception) {
            throw new EmailSendException($exception->getMessage());
        }
    }

    /**
     * 请求邮件发送接口
     * @return mixed
     * @throws EmailSendException
     */
    public function requestAccessMailToken(): bool
    {
        $params = [
            'grant_type' => config('webpower.token.grant_type'),
            'client_id' => config('webpower.token.client_id'),
            'client_secret' => config('webpower.token.client_secret'),
            'scope' => config('webpower.token.scope'),
        ];

        try {
            $response = Http::asForm()->post(config('webpower.token.oath_token_url'), $params);

            if ($response->successful()) {
                $response = $response->json();

                if (!empty($response) && is_array($response)) {
                    $token = $response['access_token'];

                    return Redis::setex(WebPowerEmailEnum::EMAIL_TOKEN_KEY, WebPowerEmailEnum::TOKEN_EXPIRE_TIME, $token);
                }
            }

            $response->throw();
        } catch (\Exception $exception) {
            throw new EmailSendException($exception->getMessage());
        }
    }


    /**
     * 获取到邮件发送的token
     * @return string
     * @throws EmailSendException
     */
    public function getMailToken(): string
    {
        if (!Redis::exists(WebPowerEmailEnum::EMAIL_TOKEN_KEY) || empty(Redis::get(WebPowerEmailEnum::EMAIL_TOKEN_KEY))) {
            $this->requestAccessMailToken();
        }

        $token = Redis::get(WebPowerEmailEnum::EMAIL_TOKEN_KEY);

        if (empty($token)) {
            throw new EmailSendException('token 数据获取失败');
        }

        return WebPowerEmailEnum::BEARER . $token;
    }
}
