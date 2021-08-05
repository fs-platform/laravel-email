<?php

namespace Smbear\WebPowerEmail\Exceptions;

class EmailTokenException extends \Exception
{
    public function __toString(): string
    {
        return $this->getMessage();
    }
}