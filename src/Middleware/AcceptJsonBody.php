<?php

declare(strict_types=1);

namespace Spacetab\AmphpSupport\Middleware;

use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler;
use Amp\Promise;
use JsonException;
use Spacetab\AmphpSupport\Exception\JsonRequestException;
use Spacetab\AmphpSupport\Exception\ValidationException;
use Spacetab\AmphpSupport\Handler\TrustedRequestInterface;
use Spacetab\BodyValidator\BodyValidator;
use function Amp\call;

abstract class AcceptJsonBody extends AbstractBody
{
    /**
     * @param Request $request
     * @param RequestHandler $requestHandler
     * @return Promise<\Amp\Http\Server\Response>
     * @throws JsonRequestException
     */
    public function handleRequest(Request $request, RequestHandler $requestHandler): Promise
    {
        $this->requestHasValidHeader($request);

        // @phpstan-ignore-next-line
        return call(function() use ($request, $requestHandler) {
            $body = yield $request->getBody()->read();

            if (is_null($body)) {
                throw JsonRequestException::bodyRequired();
            }

            try {
                $body = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException $e) {
                throw JsonRequestException::invalidJson($e);
            }

            $validator = new BodyValidator($body, $this);
            /** @var \Spacetab\BodyValidator\ResultSet $result */
            $result = yield $validator->verify();

            if (!$result->isValid()) {
                throw ValidationException::invalidBodyContents($result->getErrors());
            }

            if ($requestHandler instanceof TrustedRequestInterface) {
                $requestHandler->setTrustedBody($body);
            } else {
                throw JsonRequestException::handlerShouldBeImplementedAsTrustedInterface();
            }

            return $requestHandler->handleRequest($request);
        });
    }
}
