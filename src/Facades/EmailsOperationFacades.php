<?php

namespace Smbear\WebPowerEmail\Facades;

use Illuminate\Support\Facades\Facade;

class EmailsOperationFacades extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'web-power-operation-email';
    }
}