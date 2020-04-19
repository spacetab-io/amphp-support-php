<?php

declare(strict_types=1);

namespace Spacetab\AmphpSupport\Server;

use Amp\Http\Server\ErrorHandler as ErrorHandlerInterface;
use Amp\Http\Server\Request;
use Amp\Http\Status;
use Amp\Promise;
use Amp\Success;
use Spacetab\AmphpSupport\Server\Response;
use Spacetab\Transformation\ErrorTransformation;

final class ErrorHandler implements ErrorHandlerInterface
{
    /** {@inheritdoc} */
    public function handleError(int $statusCode, string $reason = null, Request $request = null): Promise
    {
        return new Success(
            Response::asJson(new ErrorTransformation($reason ?: Status::getReason($statusCode)), $statusCode)
        );
    }
}
