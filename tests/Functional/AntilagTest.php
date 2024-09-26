<?php

/*
 * Nytris Antilag
 * Copyright (c) Dan Phillimore (asmblah)
 * https://github.com/nytris/antilag/
 *
 * Released under the MIT license.
 * https://github.com/nytris/antilag/raw/main/MIT-LICENSE.txt
 */

declare(strict_types=1);

namespace Nytris\Antilag\Tests\Functional;

use Mockery\MockInterface;
use Nytris\Antilag\Antilag;
use Nytris\Antilag\StorageInterface;
use Nytris\Antilag\Tests\AbstractTestCase;

/**
 * Class AntilagTest.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class AntilagTest extends AbstractTestCase
{
    private MockInterface&StorageInterface $storage;

    public function setUp(): void
    {
        $this->storage = mock(StorageInterface::class, [
            'fetchStatCache' => [
                '/my/first/path' => ['size' => 1234],
            ],
            'isSupported' => true,
            'saveStatCache' => null,
        ]);
    }

    public function tearDown(): void
    {
        if (Antilag::isOn()) {
            Antilag::turnOff();
        }
    }

    public function testTurnOffClearsInMemoryStatCache(): void
    {
        Antilag::turnOn($this->storage);

        Antilag::turnOff();

        static::assertEquals([], Antilag::$statCache);
    }

    public function testTurnOnLoadsStatCacheWhenSupported(): void
    {
        Antilag::turnOn($this->storage);

        static::assertEquals(
            [
                '/my/first/path' => ['size' => 1234],
            ],
            Antilag::$statCache
        );
    }

    public function testTurnOnDoesNotLoadStatCacheWhenNotSupported(): void
    {
        $this->storage->allows()
            ->isSupported()
            ->andReturnFalse();

        $this->storage->expects()
            ->fetchStatCache()
            ->never();

        Antilag::turnOn($this->storage);

        static::assertEquals([], Antilag::$statCache);
    }

    public function testCachedStatIsReturnedForStreamWrapperUrlStat(): void
    {
        Antilag::turnOn($this->storage);

        $stat = stat('/my/first/path');

        static::assertEquals(1234, $stat['size']);
        static::assertEquals(1234, $stat[7]);
    }

    public function testCachedStatIsReturnedForStreamWrapperStreamStat(): void
    {
        $this->storage->allows()
            ->fetchStatCache()
            ->andReturn([
                __FILE__ => ['size' => 4321],
            ]);
        Antilag::turnOn($this->storage);

        $stream = fopen(__FILE__, 'rb');
        $stat = fstat($stream);

        static::assertEquals(4321, $stat['size']);
        static::assertEquals(4321, $stat[7]);
    }

    public function testTurnOffStoresNewlyCachedStatsFromStreamWrapperUrlStat(): void
    {
        Antilag::turnOn($this->storage);
        $stat = stat(__FILE__);

        $this->storage->expects('saveStatCache')
            ->once()
            ->andReturnUsing(function (array $statCache) {
                static::assertArrayHasKey(__FILE__, $statCache);
                $stat = $statCache[__FILE__];
                static::assertSame((int) filesize(__FILE__), $stat['size']);
            });

        Antilag::turnOff();

        static::assertIsArray($stat);
    }

    public function testTurnOffStoresNewlyCachedStatsFromStreamWrapperStreamStat(): void
    {
        Antilag::turnOn($this->storage);
        $stream = fopen(__FILE__, 'rb');
        $stat = fstat($stream);

        $this->storage->expects('saveStatCache')
            ->once()
            ->andReturnUsing(function (array $statCache) {
                static::assertArrayHasKey(__FILE__, $statCache);
                $stat = $statCache[__FILE__];
                static::assertSame((int) filesize(__FILE__), $stat['size']);
            });

        Antilag::turnOff();

        static::assertIsArray($stat);
    }
}
