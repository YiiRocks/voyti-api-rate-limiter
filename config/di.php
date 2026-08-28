<?php

declare(strict_types=1);

use YiiRocks\Voyti\ApiRateLimiter\RateLimiterConfig;
use YiiRocks\Voyti\ApiRateLimiter\RateLimiterConfigFactory;
use Yiisoft\User\CurrentUser;
use Yiisoft\Yii\RateLimiter\CounterInterface;
use Yiisoft\Yii\RateLimiter\LimitRequestsMiddleware;
use Yiisoft\Yii\RateLimiter\Policy\LimitPolicyInterface;
use Yiisoft\Yii\RateLimiter\Storage\StorageInterface;

/** @var array $params */

return [
    // Package configuration, built once from the host's `yiirocks/voyti.api.rateLimiter` params array.
    RateLimiterConfig::class => static fn() => new RateLimiterConfig(
        requestsPerWindow: $params['yiirocks/voyti']['api']['rateLimiter']['requestsPerWindow'] ?? 60,
        windowSeconds: $params['yiirocks/voyti']['api']['rateLimiter']['windowSeconds'] ?? 60,
        useApcu: $params['yiirocks/voyti']['api']['rateLimiter']['useApcu'] ?? false,
    ),

    StorageInterface::class => static fn(RateLimiterConfigFactory $factory, RateLimiterConfig $config)
        => $factory->createStorage($config),

    CounterInterface::class => static fn(
        RateLimiterConfigFactory $factory,
        RateLimiterConfig $config,
        StorageInterface $storage,
    ) => $factory->createCounter($config, $storage),

    LimitPolicyInterface::class => static fn(RateLimiterConfigFactory $factory, CurrentUser $currentUser)
        => $factory->createLimitPolicy($currentUser),

    // Tagged with `voyti-api.extension-middleware` so voyti-api's ApiExtensionMiddleware picks this up
    // automatically once the package is installed - no host wiring, no change to voyti-api's own
    // routes.php - and covers every versioned route group ApiExtensionMiddleware is wired into (v1
    // today, v2+ later), not just v1/.
    LimitRequestsMiddleware::class => [
        'class' => LimitRequestsMiddleware::class,
        'tags' => ['voyti-api.extension-middleware'],
    ],
];
