<?php

declare(strict_types=1);

namespace Spacetab\AmphpSupport\Exception;

use Amp\Http\Status;

class HttpException extends AppException
{
    protected int $httpCode;
    protected string $appCode;

    public function __construct(
        string $message,
        int $httpCode = Status::INTERNAL_SERVER_ERROR,
        string $appCode = self::DEFAULT_ERROR_CODE
    ) {
        parent::__construct($message);

        $this->httpCode = $httpCode;
        $this->appCode  = $appCode;
    }

    public function getHttpCode(): int
    {
        return $this->httpCode;
    }

    public function getAppCode(): string
    {
        return $this->appCode;
    }
}
