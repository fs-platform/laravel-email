###说明
* 采用通知和频道的方式发送邮件
* 邮件采用webpower方式推送
### 安装使用
安装composer
>composer require smbear/webpower-email

发布配置文件
> php artisan vendor:publish --provider="Smbear\WebPowerEmail\AppServiceProvider"

添加门面别名
```injectablephp
'aliases' => [ 
  'WebPower' => \Smbear\WebPowerEmail\Facades\WebPowerFacades::class
] 
```
使用方法
```injectablephp
name和sender不是必填参数，当name会使用默认数据，将subject后添加日期作为name 当sender不填写的时候，会直接使用配置文件中的form_name
//字符串
\WebPower::sendEmail('723891137@qq.com',[
    'body'    => view('welcome')->render(),
    'subject' => '发送',
    'name'    => 'demo',
    'sender'  => '723891137@qq.com'
]);

//数组
\WebPower::sendEmail(['723891137@qq.com','723891137@qq.com'],[
    'body'    => view('welcome')->render(),
    'subject' => '发送',
    'name'    => 'demo',
    'sender'  => '723891137@qq.com'
]);

```
使用队列发送
>php artisan queue:work redis --queue=webpower






