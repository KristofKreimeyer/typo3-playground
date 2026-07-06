<?php

declare(strict_types=1);

namespace Vendor\ComponentPlayground\Tests\Functional\Security;

use GuzzleHttp\Psr7\ServerRequest;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Backend\Http\Application;
use TYPO3\CMS\Core\FormProtection\FormProtectionFactory;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class BackendRouteSecurityFunctionalTest extends FunctionalTestCase
{
    private const COMPONENTS_ROUTE = '/typo3/ajax/component-playground/components';
    private const COMPONENTS_ROUTE_IDENTIFIER = 'ajax_component_playground_components';
    private const RENDER_ROUTE = '/typo3/ajax/component-playground/preview/render';
    private const RENDER_ROUTE_IDENTIFIER = 'ajax_component_playground_preview_render';

    protected array $testExtensionsToLoad = [
        'vendor/typo3-component-playground',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/BackendRouteSecurity.csv');
    }

    public function testUnauthenticatedComponentListRequestIsDenied(): void
    {
        $response = $this->dispatchBackendJsonRequest('GET', self::COMPONENTS_ROUTE);
        $this->assertAccessDeniedResponse($response);
        self::assertStringNotContainsString('"components"', (string)$response->getBody());
    }

    public function testUnauthenticatedRenderRequestIsDenied(): void
    {
        $response = $this->dispatchBackendJsonRequest('POST', self::RENDER_ROUTE, $this->heroPayload());
        $this->assertAccessDeniedResponse($response);
        self::assertStringNotContainsString('"html"', (string)$response->getBody());
    }

    public function testAuthenticatedAdminCanReadComponents(): void
    {
        [$cookie, $token] = $this->loginBackendUser(1, self::COMPONENTS_ROUTE_IDENTIFIER);
        $response = $this->dispatchBackendJsonRequest('GET', self::COMPONENTS_ROUTE, null, $cookie, $token);
        $payload = $this->assertSuccessfulJsonResponse($response);

        self::assertArrayHasKey('components', $payload);
        self::assertContains('hero', array_column($payload['components'], 'key'));
    }

    public function testAuthenticatedAdminCanRenderPreview(): void
    {
        [$cookie, $token] = $this->loginBackendUser(1, self::RENDER_ROUTE_IDENTIFIER);
        $response = $this->dispatchBackendJsonRequest('POST', self::RENDER_ROUTE, $this->heroPayload(), $cookie, $token);
        $payload = $this->assertSuccessfulJsonResponse($response);

        self::assertArrayHasKey('html', $payload);
        self::assertStringContainsString('Authenticated security test', $payload['html']);
    }

    public function testRestrictedUserWithModulePermissionCanReadComponents(): void
    {
        [$cookie, $token] = $this->loginBackendUser(2, self::COMPONENTS_ROUTE_IDENTIFIER);
        $response = $this->dispatchBackendJsonRequest('GET', self::COMPONENTS_ROUTE, null, $cookie, $token);
        $payload = $this->assertSuccessfulJsonResponse($response);

        self::assertContains('hero', array_column($payload['components'], 'key'));
    }

    public function testRestrictedUserWithModulePermissionCanRenderPreview(): void
    {
        [$cookie, $token] = $this->loginBackendUser(2, self::RENDER_ROUTE_IDENTIFIER);
        $response = $this->dispatchBackendJsonRequest('POST', self::RENDER_ROUTE, $this->heroPayload(), $cookie, $token);
        $payload = $this->assertSuccessfulJsonResponse($response);

        self::assertStringContainsString('Authenticated security test', $payload['html']);
    }

    public function testRestrictedUserWithoutModulePermissionCannotReadComponents(): void
    {
        [$cookie, $token] = $this->loginBackendUser(3, self::COMPONENTS_ROUTE_IDENTIFIER);
        $response = $this->dispatchBackendJsonRequest('GET', self::COMPONENTS_ROUTE, null, $cookie, $token);

        self::assertSame(403, $response->getStatusCode());
        self::assertStringNotContainsString('"components"', (string)$response->getBody());
    }

    public function testRestrictedUserWithoutModulePermissionCannotRenderPreview(): void
    {
        [$cookie, $token] = $this->loginBackendUser(3, self::RENDER_ROUTE_IDENTIFIER);
        $response = $this->dispatchBackendJsonRequest('POST', self::RENDER_ROUTE, $this->heroPayload(), $cookie, $token);

        self::assertSame(403, $response->getStatusCode());
        self::assertStringNotContainsString('"html"', (string)$response->getBody());
    }

    public function testAuthenticatedRequestWithoutRouteTokenIsDenied(): void
    {
        [$cookie] = $this->loginBackendUser(1, self::RENDER_ROUTE_IDENTIFIER);
        $response = $this->dispatchBackendJsonRequest('POST', self::RENDER_ROUTE, $this->heroPayload(), $cookie);
        $this->assertAccessDeniedResponse($response);
        self::assertStringNotContainsString('"html"', (string)$response->getBody());
    }

    public function testAuthenticatedRequestWithInvalidRouteTokenIsDenied(): void
    {
        [$cookie] = $this->loginBackendUser(1, self::RENDER_ROUTE_IDENTIFIER);
        $response = $this->dispatchBackendJsonRequest('POST', self::RENDER_ROUTE, $this->heroPayload(), $cookie, 'invalid-token');
        $this->assertAccessDeniedResponse($response);
        self::assertStringNotContainsString('"html"', (string)$response->getBody());
    }

    /** @return array{0: string, 1: string} */
    private function loginBackendUser(int $userUid, string $routeIdentifier): array
    {
        $backendUser = $this->setUpBackendUser($userUid);
        $token = GeneralUtility::makeInstance(FormProtectionFactory::class)
            ->createForType('backend')
            ->generateToken('route', $routeIdentifier);
        return [$backendUser->getSession()->getJwt(), $token];
    }

    /**
     * @param array<string, mixed>|null $payload
     */
    private function dispatchBackendJsonRequest(
        string $method,
        string $route,
        ?array $payload = null,
        ?string $sessionCookie = null,
        ?string $token = null,
    ): ResponseInterface {
        $query = $token !== null ? '?token=' . rawurlencode($token) : '';
        $serverParams = [
            'DOCUMENT_ROOT' => $this->getInstancePath(),
            'HTTP_HOST' => 'typo3-testing.local',
            'SERVER_NAME' => 'typo3-testing.local',
            'SERVER_PORT' => '443',
            'HTTPS' => 'on',
            'SCRIPT_NAME' => '/index.php',
            'SCRIPT_FILENAME' => $this->getInstancePath() . '/index.php',
            'REQUEST_URI' => $route . $query,
            'QUERY_STRING' => ltrim($query, '?'),
            'REQUEST_METHOD' => $method,
        ];
        $request = new ServerRequest(
            $method,
            'https://typo3-testing.local' . $route . $query,
            ['Accept' => 'application/json', 'Content-Type' => 'application/json'],
            $payload !== null ? json_encode($payload, JSON_THROW_ON_ERROR) : null,
            '1.1',
            $serverParams,
        );
        $request = $request
            ->withQueryParams($token !== null ? ['token' => $token] : [])
            ->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request));
        if ($sessionCookie !== null) {
            $request = $request->withCookieParams(['be_typo_user' => $sessionCookie]);
        }

        /** @var Application $application */
        $application = $this->get(Application::class);
        return $application->handle($request);
    }

    /** @return array<string, mixed> */
    private function assertSuccessfulJsonResponse(ResponseInterface $response): array
    {
        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('application/json', $response->getHeaderLine('Content-Type'));
        $payload = json_decode((string)$response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($payload);
        return $payload;
    }

    private function assertAccessDeniedResponse(ResponseInterface $response): void
    {
        self::assertContains($response->getStatusCode(), [302, 303, 307, 401, 403]);
    }

    /** @return array{component: string, props: array{headline: string}} */
    private function heroPayload(): array
    {
        return ['component' => 'hero', 'props' => ['headline' => 'Authenticated security test']];
    }
}
