<?php

declare(strict_types=1);

use YiiRocks\Voyti\ApiRateLimiter\RateLimiterConfig;

return [
    'yiirocks/voyti' => [
        'api' => [
            'rateLimiter' => [
                // Requests allowed per window per authenticated user.
                'requestsPerWindow' => 60,
                // Window length in seconds - 60 requests per 60 seconds by default (1 req/s sustained).
                'windowSeconds' => 60,
                // Off by default: only safe to enable on a single-server deployment - see
                // {@see RateLimiterConfig::$useApcu}.
                'useApcu' => false,
            ],
        ],
    ],
];
