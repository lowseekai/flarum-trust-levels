<?php

namespace Xypp\Collector\Api;

use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;

final class JsonApi
{
    public static function resource(string $type, int|string $id, array $attributes): array
    {
        return [
            'type' => $type,
            'id' => (string) $id,
            'attributes' => $attributes,
        ];
    }

    public static function response(array $data, int $status = 200): ResponseInterface
    {
        return new JsonResponse(['data' => $data], $status);
    }
}
