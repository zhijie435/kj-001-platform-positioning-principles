<?php

namespace App\Exceptions;

class ForbiddenException extends BaseException
{
    protected int $httpCode = 403;

    protected string $errorCode = 'FORBIDDEN';

    public function __construct(string $message = '无权访问该资源', array $details = [])
    {
        parent::__construct($message);
        $this->details = $details;
    }
}
