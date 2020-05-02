<?php

declare(strict_types=1);

namespace Spacetab\AmphpSupport\Middleware;

use Amp\Http\Server\Middleware;
use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler;
use Amp\Promise;
use Amp\Success;
use Spacetab\AmphpSupport\Exception\HttpException;
use Spacetab\AmphpSupport\Exception\ValidationException;
use Spacetab\AmphpSupport\Server\Response;
use Spacetab\Transformation\ErrorTransformation;
use Spacetab\Transformation\ValidationError;
use Spacetab\Transformation\ValidationTransformation;
use function Amp\call;

final class ExceptionMiddleware implements Middleware
{
    /**
     * @var array<string, string>
     */
    private array $validationMap;

    /**
     * ExceptionMiddleware constructor.
     *
     * @param array<string, string> $validationMap
     */
    public function __construct(array $validationMap = [])
    {
        $this->validationMap = $validationMap;
    }

    /**
     * @param Request $request
     * @param RequestHandler $requestHandler
     * @return Promise<\Amp\Http\Server\Response>
     */
    public function handleRequest(Request $request, RequestHandler $requestHandler): Promise
    {
        // @phpstan-ignore-next-line
        return call(function () use ($request, $requestHandler) {
            try {
                return yield $requestHandler->handleRequest($request);
            } catch (ValidationException $e) {
                $errors = $this->fillUserMistakes($e->getErrors());
                return new Success(Response::asJson(
                    new ValidationTransformation($errors), $e->getHttpCode()
                ));
            } catch (HttpException $e) {
                return new Success(Response::asJson(
                    new ErrorTransformation($e->getMessage()), $e->getHttpCode()
                ));
            }
        });
    }

    /**
     * @param array<string, array<\HarmonyIO\Validation\Result\Error>> $errors
     * @return \Spacetab\Transformation\ValidationError
     */
    protected function fillUserMistakes(array $errors): ValidationError
    {
        $valid = new ValidationError();

        foreach ($errors as $path => $messages) {
            foreach ($messages as $message) {
                /** @var \HarmonyIO\Validation\Result\Error $message */
                $msg = $this->validationMap[$message->getMessage()] ?? $message->getMessage();
                $valid->addError($path, $msg);
            }
        }

        return $valid;
    }
}
