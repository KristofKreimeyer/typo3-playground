<?php

declare(strict_types=1);

namespace Vendor\ComponentPlayground\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Utility\PathUtility;
use Vendor\ComponentPlayground\Exception\PreviewRequestException;
use Vendor\ComponentPlayground\Service\ComponentRegistry;
use Vendor\ComponentPlayground\Service\FluidPreviewRenderer;
use Vendor\ComponentPlayground\Service\PreviewRequestParser;
use Vendor\ComponentPlayground\Service\PropsSchemaValidator;

final class PreviewApiController
{
    public function __construct(
        private readonly ComponentRegistry $componentRegistry,
        private readonly FluidPreviewRenderer $previewRenderer,
        private readonly PreviewRequestParser $requestParser,
        private readonly PropsSchemaValidator $propsSchemaValidator,
    ) {
    }

    public function componentsAction(): ResponseInterface
    {
        try {
            return new JsonResponse([
                'components' => $this->componentRegistry->getComponents(),
            ]);
        } catch (\Throwable) {
            return new JsonResponse([
                'error' => 'The component registry could not be loaded.',
            ], 500);
        }
    }

    public function renderAction(ServerRequestInterface $request): ResponseInterface
    {
        try {
            $previewRequest = $this->requestParser->parse((string)$request->getBody());
        } catch (PreviewRequestException $exception) {
            return $this->errorResponse($exception->errorCode, $exception->getMessage(), $exception->statusCode);
        }

        $schema = $previewRequest['component']['schema'] ?? [];
        $validation = $this->propsSchemaValidator->validate(
            $previewRequest['props'],
            is_array($schema) ? $schema : [],
        );
        if (!$validation['valid']) {
            return $this->errorResponse(
                'invalid_props',
                'The provided props do not match the component schema.',
                422,
                $validation['errors'],
            );
        }

        try {
            return new JsonResponse([
                'html' => $this->previewRenderer->render($previewRequest['component'], $previewRequest['props']),
                'css' => PathUtility::getPublicResourceWebPath(
                    'EXT:component_playground/Resources/Public/Preview/preview.css',
                ),
            ]);
        } catch (\Throwable) {
            return $this->errorResponse(
                'render_failed',
                'The component preview could not be rendered.',
                500,
            );
        }
    }

    /** @param list<array{path: string, code: string, message: string}> $details */
    private function errorResponse(string $code, string $message, int $status, array $details = []): JsonResponse
    {
        $error = [
            'code' => $code,
            'message' => $message,
        ];
        if ($details !== []) {
            $error['details'] = $details;
        }

        return new JsonResponse([
            'error' => [
                ...$error,
            ],
        ], $status);
    }

}
