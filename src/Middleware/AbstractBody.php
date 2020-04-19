<?php

declare(strict_types=1);

namespace Spacetab\AmphpSupport\Middleware;

use Amp\Http\Server\Middleware;
use Amp\Http\Server\Request;
use Spacetab\AmphpSupport\Exception\JsonRequestException;
use Spacetab\BodyValidator\BodyValidatorInterface;

abstract class AbstractBody implements Middleware, BodyValidatorInterface
{
    /**
     * @param \Amp\Http\Server\Request $request
     * @throws \Spacetab\AmphpSupport\Exception\JsonRequestException
     */
    protected function requestHasValidHeader(Request $request): void
    {
        if ($request->getHeader('Content-Type') !== 'application/json') {
            throw JsonRequestException::contentTypeNotAllowed();
        }
    }
}
