<?php

namespace Smbear\WebPowerEmail\Exceptions;

class EmailTemplateException extends \Exception
{
    public function __toString(): string
    {
        return $this->getMessage();
    }
}