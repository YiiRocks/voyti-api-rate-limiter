<?php

declare(strict_types=1);

use YiiRocks\Voyti\ApiRateLimiter\AuthenticatedRateLimiterMiddleware;
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

    // The wrapper stands down for public requests, because the policy fingerprints by user ID.
    LimitRequestsMiddleware::class => LimitRequestsMiddleware::class,
    AuthenticatedRateLimiterMiddleware::class => [
        'class' => AuthenticatedRateLimiterMiddleware::class,
        'tags' => ['voyti-api.extension-middleware'],
    ],
];
