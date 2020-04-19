<?php

declare(strict_types=1);

namespace Spacetab\AmphpSupport\Exception;

use Amp\Http\Status;
use JsonException;

final class JsonRequestException extends HttpException
{
    // https://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html#sec10.4.16
    public static function contentTypeNotAllowed(): self
    {
        return new static('Content-Type not allowed. Supported `application/json`.', Status::UNSUPPORTED_MEDIA_TYPE);
    }

    public static function bodyRequired(): self
    {
        return new static('Body required', Status::BAD_REQUEST);
    }

    public static function invalidJson(JsonException $e): self
    {
        return new static("invalid json: {$e->getMessage()}", Status::BAD_REQUEST);
    }

    public static function handlerShouldBeImplementedAsTrustedInterface(): self
    {
        return new static('Your RequestHandler should be implemented as `Trusted RequestInterface` to receive parsed body.');
    }
}
