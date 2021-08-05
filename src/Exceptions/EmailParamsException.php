<?php

namespace Smbear\WebPowerEmail\Exceptions;

class EmailParamsException extends \Exception
{
    public function __toString(): string
    {
        return $this->getMessage();
    }
}