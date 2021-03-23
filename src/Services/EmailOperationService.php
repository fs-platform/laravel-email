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
    public function send($params,$email):bool
    {
        $mailingId = $this->createTemplate($params);

        if(!$mailingId || !is_int($mailingId)){

            throw new EmailSendException('邮件模板id 获取异常');
        }

        $result = $this->publish($email,$mailingId);

        if(!$result){
            throw new EmailSendException('邮件发送异常');
        }

        return true;
    }

    /**
     * 根据email 和 mailingId 发布邮件
     * @param string $email
     * @param int $mailingId
     * @return bool
     * @throws EmailSendException
     */
    public function publish(string $email,int $mailingId) :bool
    {
        $contacts = [
            [
                'email'  => $email,
                'lang'   => config('webpower.webpower.lang'),
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
            'groups'              => [],
            'overwrite'           => true,
            'addDuplicateToGroup' => true,
            'contacts'            => $contacts
        ];

        try{
            $token = $this->getMailToken();

            $response = Http::withHeaders([
                'Accept'        => 'application/json',
                'Content-Type'  => 'application/json',
                'Authorization' => $token
            ])
                ->withBody(json_encode($body),'raw')
                ->post(config('webpower.webpower.send_url'));

            if($response->successful()){
                $response = $response->json();

                if( !empty($response) && is_array($response) ){
                    return true;
                }
            }

        }catch (\Exception $exception){
            throw new EmailSendException($exception->getMessage());
        }
    }

    /**
     * 创建邮件模板，并获取到模块id
     * @param array $params
     * @return int
     * @throws EmailSendException
     */
    public function createTemplate(array $params) :int
    {
        $body = [
            'name'          => $params['subject'] .':'.date('Y-m-d h:i:s', time()),
            'lang'          => config('webpower.webpower.lang'),
            'subject'       => $params['subject'],
            "from_name"     => config('webpower.webpower.from_name'),
            "forward_id"    => config('webpower.webpower.forward_id'),
            "reply_id"      => config('webpower.webpower.reply_id'),
            "plaintext_msg" => config('webpower.webpower.plaintext_msg'),
            "html_msg"      => $params['body']
        ];

        try{
            $token = $this->getMailToken();

            $response = Http::withHeaders([
                'Accept'        => 'application/json',
                'Content-Type'  => 'application/json',
                'Authorization' => $token
            ])
                ->withBody(json_encode($body),'raw')
                ->post(config('webpower.webpower.request_url'));

            if($response->successful()){
                $response = $response->json();

                if( !empty($response) && is_array($response) ){

                    if(isset($response['id']) && is_int($response['id'])){
                        return $response['id'];
                    }
                }
            }
        }catch (\Exception $exception){
            throw new EmailSendException($exception->getMessage());
        }
    }

    /**
     * 请求邮件发送接口
     * @return mixed
     * @throws EmailSendException
     */
    public function requestAccessMailToken() :bool
    {
        $params = [
            'grant_type'    => config('webpower.token.grant_type'),
            'client_id'     => config('webpower.token.client_id'),
            'client_secret' => config('webpower.token.client_secret'),
            'scope'         => config('webpower.token.scope'),
        ];

        try{
            $response = Http::asForm()->post(config('webpower.token.oath_token_url'),$params);

            if($response->successful()){
                $response = $response->json();

                if( !empty($response) && is_array($response) ){
                    $token =$response['access_token'];

                    return Redis::setex(WebPowerEmailEnum::EMAIL_TOKEN_KEY,WebPowerEmailEnum::TOKEN_EXPIRE_TIME,$token);
                }
            }
        }catch (\Exception $exception){
            throw new EmailSendException($exception->getMessage());
        }
    }

    /**
     * 获取到邮件发送的token
     * @return string
     * @throws EmailSendException
     */
    public function getMailToken() :string
    {
        if( !Redis::exists(WebPowerEmailEnum::EMAIL_TOKEN_KEY) || empty(Redis::get(WebPowerEmailEnum::EMAIL_TOKEN_KEY)) ){
            $this->requestAccessMailToken();
        }

        $token = Redis::get(WebPowerEmailEnum::EMAIL_TOKEN_KEY);

        if (empty($token)){
            throw new EmailSendException('token 数据获取失败');
        }

        return WebPowerEmailEnum::BEARER. $token;
    }
}