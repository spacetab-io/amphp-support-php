<?php

declare(strict_types=1);

namespace Spacetab\AmphpSupport\Server;

use Amp\Http\Server\ErrorHandler as ErrorHandlerInterface;
use Amp\Http\Server\Request;
use Amp\Http\Status;
use Amp\Promise;
use Amp\Success;
use Spacetab\Transformation\ErrorTransformation;

final class ErrorHandler implements ErrorHandlerInterface
{
    /**
     * @param int $statusCode
     * @param string|null $reason
     * @param Request|null $request
     * @return Promise<\Amp\Http\Server\Response>
     */
    public function handleError(int $statusCode, string $reason = null, Request $request = null): Promise
    {
        return new Success(
            Response::asJson(new ErrorTransformation($reason ?: Status::getReason($statusCode)), $statusCode)
        );
    }
}
