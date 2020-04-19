<?php

namespace Spacetab\AmphpSupport\Exception;

use Amp\Http\Status;

final class ValidationException extends HttpException
{
    public const VALIDATION_CODE = 'ValidationError';
    public const VALIDATION_MESSAGE = 'Error occurred in input data. Please, correct them and send request again.';

    private array $errors;

    public static function invalidBodyContents(array $errors): self
    {
        $exception = new static(self::VALIDATION_MESSAGE, Status::UNPROCESSABLE_ENTITY, self::VALIDATION_CODE);
        $exception->setErrors($errors);

        return $exception;
    }

    public function setErrors(array $errors): void
    {
        $this->errors = $errors;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
