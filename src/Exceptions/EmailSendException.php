<?php

namespace Smbear\WebPowerEmail\Exceptions;

class EmailSendException extends \Exception
{
    public function __toString(): string
    {
      return $this->getMessage();
    }
}