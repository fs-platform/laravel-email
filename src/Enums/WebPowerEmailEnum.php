<?php

namespace Smbear\WebPowerEmail\Enums;

class WebPowerEmailEnum
{
    //队列名称
    const EMAIL_TOKEN_KEY   = 'email_token';

    //token的过期时间
    const TOKEN_EXPIRE_TIME = 20*60;

    //Token的前缀
    const BEARER = 'Bearer ';
}