<?php

declare(strict_types=1);

namespace YiiRocks\Voyti\ApiRateLimiter\Tests;

use YiiRocks\Voyti\ApiRateLimiter\RateLimiterConfig;

final class RateLimiterConfigTest extends TestCase
{
    public function testConstructorAssignsProperties(): void
    {
        $config = new RateLimiterConfig(requestsPerWindow: 60, windowSeconds: 120, useApcu: true);

        self::assertSame(60, $config->requestsPerWindow);
        self::assertSame(120, $config->windowSeconds);
        self::assertTrue($config->useApcu);
    }
}
