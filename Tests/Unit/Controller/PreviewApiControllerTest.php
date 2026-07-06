<?php

declare(strict_types=1);

namespace Vendor\ComponentPlayground\Tests\Unit\Controller;

use GuzzleHttp\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Vendor\ComponentPlayground\Controller\PreviewApiController;
use Vendor\ComponentPlayground\Service\ComponentRegistry;
use Vendor\ComponentPlayground\Service\FluidPreviewRenderer;
use Vendor\ComponentPlayground\Service\PreviewRequestParser;
use Vendor\ComponentPlayground\Service\PreviewTemplateNameResolver;
use Vendor\ComponentPlayground\Service\PropsSchemaValidator;

final class PreviewApiControllerTest extends TestCase
{
    public function testInvalidPropsReturnStructured422ResponseBeforeRendering(): void
    {
        $registry = new ComponentRegistry();
        $controller = new PreviewApiController(
            $registry,
            new FluidPreviewRenderer(new PreviewTemplateNameResolver()),
            new PreviewRequestParser($registry),
            new PropsSchemaValidator(),
        );
        $request = new ServerRequest('POST', '/', [], '{"component":"hero","props":{"text":"Missing headline"}}');

        $response = $controller->renderAction($request);
        $payload = json_decode((string)$response->getBody(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(422, $response->getStatusCode());
        self::assertSame('invalid_props', $payload['error']['code']);
        self::assertSame('headline', $payload['error']['details'][0]['path']);
        self::assertSame('required', $payload['error']['details'][0]['code']);
    }
}
