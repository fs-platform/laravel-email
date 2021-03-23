<?php

namespace Smbear\WebPowerEmail\Facades;

use Illuminate\Support\Facades\Facade;

class WebPowerFacades extends Facade
{
    protected static function getFacadeAccessor():string
    {
        return 'web-power-email';
    }
}