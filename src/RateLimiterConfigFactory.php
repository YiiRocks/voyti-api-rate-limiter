<?php

declare(strict_types=1);

namespace YiiRocks\Voyti\ApiRateLimiter;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\SimpleCache\CacheInterface;
use Yiisoft\User\CurrentUser;
use Yiisoft\Yii\RateLimiter\Counter;
use Yiisoft\Yii\RateLimiter\CounterInterface;
use Yiisoft\Yii\RateLimiter\Policy\LimitCallback;
use Yiisoft\Yii\RateLimiter\Policy\LimitPolicyInterface;
use Yiisoft\Yii\RateLimiter\Storage\ApcuStorage;
use Yiisoft\Yii\RateLimiter\Storage\SimpleCacheStorage;
use Yiisoft\Yii\RateLimiter\Storage\StorageInterface;

/**
 * Builds the `yiisoft/rate-limiter` services wired in `config/di.php`. Keeping this decision logic
 * (which storage backend, how requests are fingerprinted) in a real, testable class instead of DI
 * closures matters beyond style: Infection can't generate mutants for code in a class-less config
 * file at all, no matter how well it's covered.
 */
final readonly class RateLimiterConfigFactory
{
    public function __construct(
        private ContainerInterface $container,
    ) {}

    public function createCounter(RateLimiterConfig $config, StorageInterface $storage): CounterInterface
    {
        return new Counter(
            storage: $storage,
            limit: $config->requestsPerWindow,
            periodInSeconds: $config->windowSeconds,
        );
    }

    // Fingerprints requests by authenticated user rather than by IP address.
    public function createLimitPolicy(CurrentUser $currentUser): LimitPolicyInterface
    {
        return new LimitCallback(
            static fn(ServerRequestInterface $request): string => (string) $currentUser->getIdentity()->getId(),
        );
    }

    /**
     * Chooses between {@see ApcuStorage} and {@see SimpleCacheStorage} per
     * {@see RateLimiterConfig::$useApcu}. Resolves {@see CacheInterface} from the container itself,
     * rather than taking it as a constructor dependency, so a host that opts into APCu never needs a
     * `CacheInterface` binding of its own - it's only resolved when it's actually going to be used.
     */
    public function createStorage(RateLimiterConfig $config): StorageInterface
    {
        if ($config->useApcu) {
            return new ApcuStorage();
        }

        /** @var CacheInterface $cache */
        $cache = $this->container->get(CacheInterface::class);

        return new SimpleCacheStorage($cache);
    }
}
