<?php

declare(strict_types=1);

namespace Spacetab\AmphpSupport\Handler;

use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler;
use Amp\Http\Server\Response;
use Amp\Http\Status;
use Amp\Promise;
use function Amp\call;

class HealthcheckHandler implements RequestHandler
{
    public function handleRequest(Request $request): Promise
    {
        return  call(fn () => new Response(Status::OK, [], 'ok'));
    }
}
