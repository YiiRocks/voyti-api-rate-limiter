<?php

declare(strict_types=1);

namespace YiiRocks\Voyti\ApiRateLimiter\Tests;

use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Server\RequestHandlerInterface;
use YiiRocks\Voyti\ApiRateLimiter\AuthenticatedRateLimiterMiddleware;
use Yiisoft\Auth\IdentityInterface;
use Yiisoft\Auth\IdentityRepositoryInterface;
use Yiisoft\User\CurrentUser;
use Yiisoft\Yii\RateLimiter\CounterInterface;
use Yiisoft\Yii\RateLimiter\CounterState;
use Yiisoft\Yii\RateLimiter\LimitRequestsMiddleware;

final class AuthenticatedRateLimiterMiddlewareTest extends TestCase
{
    /**
     * @return array<string, array{authenticated: bool}>
     */
    public static function authenticationProvider(): array
    {
        return [
            'authenticated user' => ['authenticated' => true],
            'guest user' => ['authenticated' => false],
        ];
    }

    #[DataProvider('authenticationProvider')]
    public function testAppliesLimitOnlyToAuthenticatedRequest(bool $authenticated): void
    {
        $counter = $this->createMock(CounterInterface::class);
        $counter->expects($authenticated ? self::once() : self::never())
            ->method('hit')
            ->willReturn(new CounterState(10, 9, 123));
        $response = (new Psr17Factory())->createResponse();
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects(self::once())->method('handle')->willReturn($response);
        $currentUser = $this->createCurrentUser();

        if ($authenticated) {
            $identity = $this->createStub(IdentityInterface::class);
            $identity->method('getId')->willReturn('42');
            $currentUser->login($identity);
        }

        $middleware = new AuthenticatedRateLimiterMiddleware($currentUser, new LimitRequestsMiddleware($counter, new Psr17Factory()));
        $result = $middleware->process(new ServerRequest('GET', '/'), $handler);

        if ($authenticated) {
            self::assertSame(200, $result->getStatusCode());
            self::assertSame('10', $result->getHeaderLine('X-Rate-Limit-Limit'));
            self::assertSame('9', $result->getHeaderLine('X-Rate-Limit-Remaining'));
            self::assertSame('123', $result->getHeaderLine('X-Rate-Limit-Reset'));
        } else {
            self::assertSame($response, $result);
            self::assertFalse($result->hasHeader('X-Rate-Limit-Limit'));
        }
    }

    private function createCurrentUser(): CurrentUser
    {
        $eventDispatcher = $this->createStub(EventDispatcherInterface::class);
        $eventDispatcher->method('dispatch')->willReturnArgument(0);

        return new CurrentUser($this->createStub(IdentityRepositoryInterface::class), $eventDispatcher);
    }
}
