<?php

declare(strict_types=1);

namespace Spacetab\AmphpSupport\Server;

use Amp\Http\Server\Response as ServerResponse;
use Amp\Http\Status;
use JsonException;
use Spacetab\Transformation\ErrorTransformation;
use Spacetab\Transformation\TransformationInterface;

final class Response
{
    private const JSON_HEADERS = [
        'Content-Type' => 'application/json'
    ];

    public static function asJson(TransformationInterface $value, int $status = Status::OK): ServerResponse
    {
        try {
            $output = self::jsonEncode($value->doTransform());
        } catch (JsonException $e) {
            $transformer = new ErrorTransformation("Transformation to json failed: {$e->getMessage()}");

            return new ServerResponse(
                Status::INTERNAL_SERVER_ERROR,
                self::JSON_HEADERS,
                self::jsonEncode($transformer->doTransform())
            );
        }

        return new ServerResponse($status, self::JSON_HEADERS, $output);
    }

    /**
     * @param array $value
     * @return string
     * @throws \JsonException
     */
    private static function jsonEncode($value): string
    {
        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }
}
