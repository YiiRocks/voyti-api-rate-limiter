<?php

declare(strict_types=1);

namespace YiiRocks\Voyti\ApiRateLimiter;

/**
 * Single source of truth for this package's settings: an immutable value object used to build the
 * `yiisoft/rate-limiter` services wired in `config/di.php`.
 * Default values live in `config/params.php`.
 */
final readonly class RateLimiterConfig
{
    public function __construct(
        public int $requestsPerWindow,
        public int $windowSeconds,
        /**
         * Whether to store counters in APCu instead of the host's PSR-16 cache. APCu gives real
         * atomic compare-and-swap, unlike PSR-16, but is per-server local storage - only safe to
         * enable on a single-server deployment, since a load-balanced/multi-server host would then
         * enforce the configured limit independently per server instead of across the whole cluster.
         */
        public bool $useApcu,
    ) {}
}
