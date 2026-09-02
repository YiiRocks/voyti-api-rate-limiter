<?php

declare(strict_types=1);

namespace YiiRocks\Voyti\ApiRateLimiter;

use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\User\CurrentUser;
use Yiisoft\User\Guest\GuestIdentityInterface;
use Yiisoft\Yii\RateLimiter\LimitRequestsMiddleware;

/**
 * Applies the configured per-user limiter only when bearer authentication resolved an identity.
 * Public API requests continue without this limiter because they have no user ID to fingerprint.
 */
final readonly class AuthenticatedRateLimiterMiddleware implements MiddlewareInterface
{
    public function __construct(
        private CurrentUser $currentUser,
        private LimitRequestsMiddleware $limiter,
    ) {}

    #[Override]
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($this->currentUser->getIdentity() instanceof GuestIdentityInterface) {
            return $handler->handle($request);
        }

        return $this->limiter->process($request, $handler);
    }
}
