<?php

namespace Smbear\WebPowerEmail\Exceptions;

use Exception;

class EmailParamsException extends Exception
{
    public function __construct(string $message = "",int $code = 500)
    {
        parent::__construct($message, $code);
    }
}