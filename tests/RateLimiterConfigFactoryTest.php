<?php

declare(strict_types=1);

namespace YiiRocks\Voyti\ApiRateLimiter\Tests;

use InvalidArgumentException;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\SimpleCache\CacheInterface;
use YiiRocks\Voyti\ApiRateLimiter\RateLimiterConfig;
use YiiRocks\Voyti\ApiRateLimiter\RateLimiterConfigFactory;
use Yiisoft\Auth\IdentityInterface;
use Yiisoft\Auth\IdentityRepositoryInterface;
use Yiisoft\User\CurrentUser;
use Yiisoft\Yii\RateLimiter\Storage\ApcuStorage;
use Yiisoft\Yii\RateLimiter\Storage\SimpleCacheStorage;
use Yiisoft\Yii\RateLimiter\Storage\StorageInterface;

final class RateLimiterConfigFactoryTest extends TestCase
{
    public function testCreateCounterWiresTheConfiguredLimitIntoTheCounter(): void
    {
        $storage = $this->createStub(StorageInterface::class);
        $storage->method('get')->willReturn(null);
        $storage->method('saveIfNotExists')->willReturn(true);

        $config = new RateLimiterConfig(requestsPerWindow: 5, windowSeconds: 30, useApcu: false);
        $factory = new RateLimiterConfigFactory($this->createStub(ContainerInterface::class));

        $counter = $factory->createCounter($config, $storage);

        self::assertSame(5, $counter->hit('test-id')->getLimit());
    }

    public function testCreateLimitPolicyFingerprintsByTheAuthenticatedUsersId(): void
    {
        $eventDispatcher = $this->createStub(EventDispatcherInterface::class);
        $eventDispatcher->method('dispatch')->willReturnArgument(0);
        $identityRepository = $this->createStub(IdentityRepositoryInterface::class);
        $factory = new RateLimiterConfigFactory($this->createStub(ContainerInterface::class));

        // A numeric-string ID is returned as-is.
        $identity = $this->createStub(IdentityInterface::class);
        $identity->method('getId')->willReturn('42');
        $currentUser = new CurrentUser($identityRepository, $eventDispatcher);
        $currentUser->login($identity);

        $policy = $factory->createLimitPolicy($currentUser);
        self::assertSame('42', $policy->fingerprint($this->createStub(ServerRequestInterface::class)));

        // A null ID (getId()'s declared type is nullable) is cast to an empty string rather than
        // violating the fingerprint closure's non-nullable `: string` return type - LimitCallback then
        // rejects that empty string with its own InvalidArgumentException. Without the cast, returning
        // null under strict_types would instead throw a TypeError, so asserting the exception class
        // distinguishes the two.
        $nullIdentity = $this->createStub(IdentityInterface::class);
        $nullIdentity->method('getId')->willReturn(null);
        $currentUserWithNullId = new CurrentUser($identityRepository, $eventDispatcher);
        $currentUserWithNullId->login($nullIdentity);

        $nullIdPolicy = $factory->createLimitPolicy($currentUserWithNullId);
        $this->expectException(InvalidArgumentException::class);
        $nullIdPolicy->fingerprint($this->createStub(ServerRequestInterface::class));
    }

    public function testCreateStorageChoosesApcuOrSimpleCachePerUseApcu(): void
    {
        // useApcu: builds ApcuStorage without ever touching the container - a host running APCu-only
        // must not be required to bind a CacheInterface it doesn't have.
        $apcuOnlyContainer = $this->createMock(ContainerInterface::class);
        $apcuOnlyContainer->expects(self::never())->method('get');

        $apcuConfig = new RateLimiterConfig(requestsPerWindow: 60, windowSeconds: 60, useApcu: true);
        $apcuStorage = (new RateLimiterConfigFactory($apcuOnlyContainer))->createStorage($apcuConfig);

        self::assertInstanceOf(ApcuStorage::class, $apcuStorage);

        // Not useApcu: resolves the host's CacheInterface from the container and wraps it in
        // SimpleCacheStorage.
        $cache = $this->createStub(CacheInterface::class);
        $container = $this->createMock(ContainerInterface::class);
        $container->expects(self::once())->method('get')->with(CacheInterface::class)->willReturn($cache);

        $defaultConfig = new RateLimiterConfig(requestsPerWindow: 60, windowSeconds: 60, useApcu: false);
        $defaultStorage = (new RateLimiterConfigFactory($container))->createStorage($defaultConfig);

        self::assertInstanceOf(SimpleCacheStorage::class, $defaultStorage);
    }
}
