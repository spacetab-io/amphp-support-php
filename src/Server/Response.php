<?php

declare(strict_types=1);

namespace Spacetab\AmphpSupport\Server;

use Amp\ByteStream\InputStream;
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
     * @param InputStream|string|null $stringOrStream
     * @param string|null $filename
     * @return ServerResponse
     */
    public static function asPdf($stringOrStream, string $filename = null): ServerResponse
    {
        if (is_null($filename)) {
            $filename = self::getFilename('pdf');
        }

        return new ServerResponse(Status::OK, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "attachment; filename=\"{$filename}\""
        ], $stringOrStream);
    }

    /**
     * @param InputStream|string|null $stringOrStream
     * @return ServerResponse
     */
    public static function asHtml($stringOrStream): ServerResponse
    {
        return new ServerResponse(Status::OK, [
            'Content-Type' => 'text/html'
        ], $stringOrStream);
    }

    /**
     * @param mixed $value
     * @return string
     * @throws \JsonException
     */
    private static function jsonEncode($value): string
    {
        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }

    private static function getFilename(string $extension): string
    {
        return sprintf("file-%s.%s", date('dmY-His'), $extension);
    }
}
