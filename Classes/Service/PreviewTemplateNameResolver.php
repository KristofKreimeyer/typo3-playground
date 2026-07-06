<?php

declare(strict_types=1);

namespace Vendor\ComponentPlayground\Service;

use RuntimeException;

final class PreviewTemplateNameResolver
{
    /** @param array{template?: mixed} $component */
    public function resolve(array $component): string
    {
        $template = $component['template'] ?? null;
        if (!is_string($template) || preg_match('/^[A-Z][A-Za-z0-9]*$/', $template) !== 1) {
            throw new RuntimeException('Invalid registered preview template.');
        }
        return $template;
    }
}
