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

namespace Nytris\Antilag\Tests\Functional\Direct;

use Mockery\MockInterface;
use Nytris\Antilag\Antilag;
use Nytris\Antilag\StorageInterface;
use Nytris\Antilag\Tests\Functional\AbstractFunctionalTestCase;

/**
 * Class AntilagTest.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class AntilagTest extends AbstractFunctionalTestCase
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
        Antilag::stage3();
    }

    public function testStage1LoadsStatCacheWhenSupported(): void
    {
        Antilag::stage1($this->storage);

        static::assertEquals(
            [
                '/my/first/path' => ['size' => 1234],
            ],
            Antilag::getStatCache()
        );
    }

    public function testStage1DoesNotLoadStatCacheWhenNotSupported(): void
    {
        $this->storage->allows()
            ->isSupported()
            ->andReturnFalse();

        $this->storage->expects()
            ->fetchStatCache()
            ->never();

        Antilag::stage1($this->storage);

        static::assertEquals([], Antilag::getStatCache());
    }

    public function testCachedStatIsReturnedForStage1StreamWrapperStreamStat(): void
    {
        $this->storage->allows()
            ->fetchStatCache()
            ->andReturn([
                __FILE__ => ['size' => 4321],
            ]);
        Antilag::stage1($this->storage);

        $stream = fopen(__FILE__, 'rb');
        $stat = fstat($stream);

        static::assertEquals(4321, $stat['size']);
        static::assertEquals(4321, $stat[7]);
    }

    public function testCachedStatIsReturnedForStage1StreamWrapperUrlStat(): void
    {
        Antilag::stage1($this->storage);

        $stat = stat('/my/first/path');

        static::assertEquals(1234, $stat['size']);
        static::assertEquals(1234, $stat[7]);
    }

    public function testStage3ClearsInMemoryStatCache(): void
    {
        Antilag::stage1($this->storage);

        Antilag::stage3();

        static::assertEquals([], Antilag::getStatCache());
    }

    public function testStage3StoresNewlyCachedStatsFromStage1StreamWrapperStreamStat(): void
    {
        Antilag::stage1($this->storage);
        $stream = fopen(__FILE__, 'rb');

        $this->storage->expects('saveStatCache')
            ->once()
            ->andReturnUsing(function (array $statCache) {
                static::assertArrayHasKey(__FILE__, $statCache);
                $stat = $statCache[__FILE__];
                static::assertSame((int) filesize(__FILE__), $stat['size']);
            });

        $stat = fstat($stream);
        Antilag::stage3();

        static::assertIsArray($stat);
    }

    public function testStage3StoresNewlyCachedStatsFromStage1StreamWrapperUrlStat(): void
    {
        Antilag::stage1($this->storage);

        $this->storage->expects('saveStatCache')
            ->once()
            ->andReturnUsing(function (array $statCache) {
                static::assertArrayHasKey(__FILE__, $statCache);
                $stat = $statCache[__FILE__];
                static::assertSame((int) filesize(__FILE__), $stat['size']);
            });

        $stat = stat(__FILE__);
        Antilag::stage3();

        static::assertIsArray($stat);
    }
}
