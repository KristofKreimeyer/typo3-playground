<?php

declare(strict_types=1);

namespace Vendor\ComponentPlayground\Exception;

use RuntimeException;

final class PreviewRequestException extends RuntimeException
{
    public function __construct(
        public readonly string $errorCode,
        string $message,
        public readonly int $statusCode,
    ) {
        parent::__construct($message);
    }
}
