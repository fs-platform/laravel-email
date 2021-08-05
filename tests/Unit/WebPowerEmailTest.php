<?php

namespace Smbear\WebPowerEmail\Unit\Tests;

use Tests\TestCase;
use Smbear\WebPowerEmail\Enums\WebPowerEmailEnum;
use Smbear\WebPowerEmail\Facades\WebPowerFacades;
use Illuminate\Foundation\Testing\WithFaker;
use Smbear\WebPowerEmail\Facades\EmailsOperationFacades;

class WebPowerEmailTest extends TestCase
{
    use WithFaker;

    public string $email = 'ywjmylove@163.com';

    public function testSendEmail()
    {
        $email = $this->email;

        for($i =0; $i<10;$i++){
            $emails[] = $this->email;
        }

        $emailData = [
            $email,
            $emails ?? []
        ];

        $emails = $emailData[$this->faker->numberBetween(0,1)];

        $params = [
            'body'     => $this->faker->randomHtml(8,10),
            'subject'  => $this->faker->text(10)
        ];

        $result = WebPowerFacades::sendEmail($emails,$params);

        $this->assertNull($result);
    }

    /**
     * 测试请求邮箱token的接口
     * @test
     */
    public function testRequestAccessMailToken()
    {
        $this->assertTrue(EmailsOperationFacades::requestAccessMailToken());
    }

    /**
     * 测试获取到token
     * @test
     */
    public function testGetMailToken()
    {
        $this->assertStringContainsString(WebPowerEmailEnum::BEARER,EmailsOperationFacades::getMailToken());
    }

    /**
     * 测试获取到模板id
     * @test
     */
    public function testCreateTemplateId()
    {
        $params = [
            'body'     => $this->faker->randomHtml(8,10),
            'subject'  => $this->faker->text(10)
        ];

        $result = EmailsOperationFacades::createTemplate($params);

        $this->assertIsInt($result);
    }

    /**
     * 测试邮件发送
     * @test
     */
    public function testSend()
    {
        $params = [
            'body'    => $this->faker->randomHtml(1,2),
            'subject' => $this->faker->jobTitle(),
        ];

        $this->assertTrue(EmailsOperationFacades::send($params,$this->email));
    }
}
