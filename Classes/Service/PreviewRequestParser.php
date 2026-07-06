<?php

declare(strict_types=1);

namespace Vendor\ComponentPlayground\Service;

use Vendor\ComponentPlayground\Exception\PreviewRequestException;

final class PreviewRequestParser
{
    public function __construct(
        private readonly ComponentRegistry $componentRegistry,
    ) {
    }

    /** @return array{component: array<string, mixed>, props: array<string, mixed>} */
    public function parse(string $requestBody): array
    {
        try {
            $payload = json_decode($requestBody, false, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw new PreviewRequestException('invalid_json', 'The request body must contain valid JSON.', 400);
        }

        if (!is_object($payload)
            || !property_exists($payload, 'component')
            || !is_string($payload->component)
            || trim($payload->component) === ''
            || !property_exists($payload, 'props')
            || !is_object($payload->props)
        ) {
            throw new PreviewRequestException(
                'invalid_request',
                'The request requires a non-empty component key and props object.',
                400,
            );
        }

        $component = $this->componentRegistry->findByKey($payload->component);
        if ($component === null) {
            throw new PreviewRequestException('unknown_component', 'The requested component is not registered.', 404);
        }

        return [
            'component' => $component,
            'props' => $this->normalizeJsonObject($payload->props),
        ];
    }

    /** @return array<string, mixed> */
    private function normalizeJsonObject(\stdClass $value): array
    {
        $normalized = [];
        foreach (get_object_vars($value) as $key => $item) {
            $normalized[$key] = $this->normalizeJsonValue($item);
        }
        return $normalized;
    }

    private function normalizeJsonValue(mixed $value): mixed
    {
        if ($value instanceof \stdClass) {
            return $this->normalizeJsonObject($value);
        }
        if (is_array($value)) {
            return array_map($this->normalizeJsonValue(...), $value);
        }
        return $value;
    }
}
