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
use Nytris\Antilag\Tests\Functional\AbstractFunctionalTestCase;
use Nytris\Ignition\Ignition;
use Nytris\Ignition\Storage\StorageInterface;

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
        Ignition::switchOff();
    }

    public function testStage1PreservesStatCacheWhenSupported(): void
    {
        Ignition::start(
            rootProjectPath: dirname(__DIR__) . '/Fixtures/Direct/WithAutoHandoffDisablingPreflight',
            storage: $this->storage
        );

        Antilag::stage1();

        static::assertEquals(['size' => 1234], Ignition::getStatCache()['/my/first/path']);
    }

    public function testStage1DoesNotAffectStatCacheWhenNotSupported(): void
    {
        $this->storage->allows()
            ->isSupported()
            ->andReturnFalse();
        Ignition::start(
            rootProjectPath: dirname(__DIR__) . '/Fixtures/Direct/WithAutoHandoffDisablingPreflight',
            storage: $this->storage
        );

        $this->storage->expects()
            ->fetchStatCache()
            ->never();

        Antilag::stage1();

        static::assertFalse(Ignition::isChokeOn());
    }

    public function testCachedStatIsReturnedForIgnitionStreamWrapperStreamStat(): void
    {
        $this->storage->allows()
            ->fetchStatCache()
            ->andReturn([
                __FILE__ => ['size' => 4321],
            ]);
        Ignition::start(
            rootProjectPath: dirname(__DIR__) . '/Fixtures/Direct/WithAutoHandoffDisablingPreflight',
            storage: $this->storage
        );
        Antilag::stage1();

        $stream = fopen(__FILE__, 'rb');
        $stat = fstat($stream);

        static::assertEquals(4321, $stat['size']);
        static::assertEquals(4321, $stat[7]);
    }

    public function testCachedStatIsReturnedForIgnitionStreamWrapperUrlStat(): void
    {
        Ignition::start(
            rootProjectPath: dirname(__DIR__) . '/Fixtures/Direct/WithAutoHandoffDisablingPreflight',
            storage: $this->storage
        );
        Antilag::stage1();

        $stat = stat('/my/first/path');

        static::assertEquals(1234, $stat['size']);
        static::assertEquals(1234, $stat[7]);
    }

    public function testStage3DisablesStatCache(): void
    {
        Ignition::start(
            rootProjectPath: dirname(__DIR__) . '/Fixtures/Direct/WithAutoHandoffDisablingPreflight',
            storage: $this->storage
        );
        Antilag::stage1();

        Antilag::stage3();

        static::assertFalse(Ignition::isChokeOn());
    }

    public function testStage3StoresNewlyCachedStatsFromIgnitionStreamWrapperStreamStat(): void
    {
        Ignition::start(
            rootProjectPath: dirname(__DIR__) . '/Fixtures/Direct/WithAutoHandoffDisablingPreflight',
            storage: $this->storage
        );
        Antilag::stage1();
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

    public function testStage3StoresNewlyCachedStatsFromIgnitionStreamWrapperUrlStat(): void
    {
        Ignition::start(
            rootProjectPath: dirname(__DIR__) . '/Fixtures/Direct/WithAutoHandoffDisablingPreflight',
            storage: $this->storage
        );
        Antilag::stage1();

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
