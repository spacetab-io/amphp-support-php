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
    private array $validationMap;

    /**
     * ExceptionMiddleware constructor.
     *
     * @param array $validationMap
     */
    public function __construct(array $validationMap = [])
    {
        $this->validationMap = $validationMap;
    }

    public function handleRequest(Request $request, RequestHandler $requestHandler): Promise
    {
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
