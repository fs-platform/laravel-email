<?php

namespace Smbear\WebPowerEmail\Exceptions;

class EmailSendException extends \Exception
{
    public function __construct(string $message = "",int $code = 500)
    {
        parent::__construct($message, $code);
    }
}