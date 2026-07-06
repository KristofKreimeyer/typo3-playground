<?php

declare(strict_types=1);

namespace Vendor\ComponentPlayground\Service;

final class PropsSchemaValidator
{
    /**
     * @param array<string, mixed> $props
     * @param array<string, mixed> $schema
     * @return array{valid: bool, errors: list<array{path: string, code: string, message: string}>}
     */
    public function validate(array $props, array $schema): array
    {
        $errors = [];
        $this->validateValue($props, $schema, '', $errors, true);
        return ['valid' => $errors === [], 'errors' => $errors];
    }

    /**
     * @param array<string, mixed> $schema
     * @param list<array{path: string, code: string, message: string}> $errors
     */
    private function validateValue(mixed $value, array $schema, string $path, array &$errors, bool $rootObject = false): void
    {
        $type = $schema['type'] ?? null;
        if ($type === 'string') {
            if (!is_string($value)) {
                $this->addError($errors, $path, 'invalid_type', $path . ' must be a string.');
            }
            return;
        }

        if ($type === 'array') {
            if (!is_array($value) || !array_is_list($value)) {
                $this->addError($errors, $path, 'invalid_type', $path . ' must be an array.');
                return;
            }
            $itemSchema = $schema['items'] ?? null;
            if (is_array($itemSchema)) {
                foreach ($value as $index => $item) {
                    $this->validateValue($item, $itemSchema, $path . '[' . $index . ']', $errors);
                }
            }
            return;
        }

        if ($type === 'object') {
            if (!is_array($value) || (!$rootObject && $value !== [] && array_is_list($value))) {
                $this->addError($errors, $path, 'invalid_type', ($path !== '' ? $path : 'props') . ' must be an object.');
                return;
            }
            $required = is_array($schema['required'] ?? null) ? $schema['required'] : [];
            foreach ($required as $requiredProperty) {
                if (is_string($requiredProperty) && !array_key_exists($requiredProperty, $value)) {
                    $propertyPath = $this->propertyPath($path, $requiredProperty);
                    $this->addError($errors, $propertyPath, 'required', $propertyPath . ' is required.');
                }
            }
            $properties = is_array($schema['properties'] ?? null) ? $schema['properties'] : [];
            foreach ($properties as $property => $propertySchema) {
                if (is_string($property) && is_array($propertySchema) && array_key_exists($property, $value)) {
                    $this->validateValue($value[$property], $propertySchema, $this->propertyPath($path, $property), $errors);
                }
            }
        }
    }

    private function propertyPath(string $parent, string $property): string
    {
        return $parent === '' ? $property : $parent . '.' . $property;
    }

    /** @param list<array{path: string, code: string, message: string}> $errors */
    private function addError(array &$errors, string $path, string $code, string $message): void
    {
        $errors[] = ['path' => $path, 'code' => $code, 'message' => $message];
    }
}
