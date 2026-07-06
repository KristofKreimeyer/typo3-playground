<?php

declare(strict_types=1);

namespace Vendor\ComponentPlayground\Tests\Functional\Controller;

use GuzzleHttp\Psr7\ServerRequest;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Backend\Http\RouteDispatcher;
use TYPO3\CMS\Backend\Routing\Router;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class PreviewApiRouteDispatchFunctionalTest extends FunctionalTestCase
{
    private const ROUTE_IDENTIFIER = 'ajax_component_playground_preview_render';
    private const ROUTE_PATH = '/typo3/ajax/component-playground/preview/render';

    protected bool $initializeDatabase = false;

    protected array $testExtensionsToLoad = [
        'vendor/typo3-component-playground',
    ];

    public function testSuccessfulHeroRenderThroughBackendRoute(): void
    {
        $response = $this->dispatchJsonRequest('POST', self::ROUTE_PATH, [
            'component' => 'hero',
            'props' => [
                'headline' => 'Functional route Hero',
                'text' => 'Rendered through TYPO3 route dispatch.',
                'buttonLabel' => 'Read more',
                'buttonUrl' => '/demo',
            ],
        ]);
        $payload = $this->decodeJsonResponse($response);

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('application/json', $response->getHeaderLine('Content-Type'));
        self::assertArrayHasKey('html', $payload);
        self::assertIsString($payload['html']);
        self::assertNotSame('', trim($payload['html']));
        self::assertStringContainsString('Functional route Hero', $payload['html']);
        self::assertStringContainsString('Rendered through TYPO3 route dispatch.', $payload['html']);
        self::assertStringNotContainsString('<f:', $payload['html']);
        self::assertStringNotContainsString('{headline}', $payload['html']);
    }

    public function testInvalidPropsReturn422ThroughBackendRoute(): void
    {
        $response = $this->dispatchJsonRequest('POST', self::ROUTE_PATH, [
            'component' => 'hero',
            'props' => ['text' => 'Missing required headline'],
        ]);
        $error = $this->assertJsonError($response, 422, 'invalid_props');

        self::assertArrayNotHasKey('html', $this->decodeJsonResponse($response));
        self::assertSame('headline', $error['details'][0]['path'] ?? null);
        self::assertSame('required', $error['details'][0]['code'] ?? null);
    }

    public function testUnknownComponentReturns404ThroughBackendRoute(): void
    {
        $response = $this->dispatchJsonRequest(
            'POST',
            self::ROUTE_PATH,
            '{"component":"doesNotExist","props":{}}',
        );

        $this->assertJsonError($response, 404, 'unknown_component');
        self::assertArrayNotHasKey('html', $this->decodeJsonResponse($response));
    }

    public function testInvalidJsonReturns400ThroughBackendRoute(): void
    {
        $response = $this->dispatchJsonRequest('POST', self::ROUTE_PATH, '{ "component": "hero",');
        $this->assertJsonError($response, 400, 'invalid_json');
    }

    public function testMissingComponentReturns400ThroughBackendRoute(): void
    {
        $response = $this->dispatchJsonRequest('POST', self::ROUTE_PATH, ['props' => []]);
        $this->assertJsonError($response, 400, 'invalid_request');
    }

    public function testNonObjectPropsReturn400ThroughBackendRoute(): void
    {
        $response = $this->dispatchJsonRequest('POST', self::ROUTE_PATH, [
            'component' => 'hero',
            'props' => 'not-an-object',
        ]);
        $this->assertJsonError($response, 400, 'invalid_request');
    }

    /** @param array<string, mixed>|string $payload */
    private function dispatchJsonRequest(string $method, string $route, array|string $payload): ResponseInterface
    {
        $body = is_string($payload) ? $payload : json_encode($payload, JSON_THROW_ON_ERROR);
        $serverParams = [
            'DOCUMENT_ROOT' => $this->getInstancePath(),
            'HTTP_HOST' => 'typo3-testing.local',
            'SERVER_NAME' => 'typo3-testing.local',
            'SERVER_PORT' => '443',
            'HTTPS' => 'on',
            'SCRIPT_NAME' => '/index.php',
            'SCRIPT_FILENAME' => $this->getInstancePath() . '/index.php',
            'REQUEST_URI' => $route,
            'REQUEST_METHOD' => $method,
        ];
        $request = new ServerRequest(
            $method,
            'https://typo3-testing.local' . $route,
            ['Accept' => 'application/json', 'Content-Type' => 'application/json'],
            $body,
            '1.1',
            $serverParams,
        );
        $request = $request->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request));

        /** @var Router $router */
        $router = $this->get(Router::class);
        $routeResult = $router->matchResult($request);
        self::assertSame(self::ROUTE_IDENTIFIER, $routeResult->getRoute()->getOption('_identifier'));

        // Form tokens depend on a persisted backend session; this suite deliberately has no database.
        $matchedRoute = $routeResult->getRoute()->setOption('access', 'public');
        $request = $request
            ->withAttribute('routing', $routeResult)
            ->withAttribute('route', $matchedRoute);

        /** @var RouteDispatcher $dispatcher */
        $dispatcher = $this->get(RouteDispatcher::class);
        return $dispatcher->dispatch($request);
    }

    /** @return array<string, mixed> */
    private function decodeJsonResponse(ResponseInterface $response): array
    {
        $payload = json_decode((string)$response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($payload);
        return $payload;
    }

    /** @return array<string, mixed> */
    private function assertJsonError(ResponseInterface $response, int $statusCode, string $errorCode): array
    {
        self::assertSame($statusCode, $response->getStatusCode());
        self::assertStringContainsString('application/json', $response->getHeaderLine('Content-Type'));
        $payload = $this->decodeJsonResponse($response);
        self::assertArrayHasKey('error', $payload);
        self::assertIsArray($payload['error']);
        self::assertSame($errorCode, $payload['error']['code'] ?? null);
        self::assertArrayNotHasKey('html', $payload);
        return $payload['error'];
    }
}
