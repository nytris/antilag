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

namespace Nytris\Antilag\Tests\Functional\Direct\Stage2;

use Asmblah\PhpCodeShift\Shift;
use Mockery\MockInterface;
use Nytris\Antilag\Antilag;
use Nytris\Antilag\AntilagPackage;
use Nytris\Antilag\Launch;
use Nytris\Antilag\Stage;
use Nytris\Antilag\StorageInterface;
use Nytris\Antilag\Tests\Functional\AbstractFunctionalTestCase;
use Nytris\Boot\BootConfig;
use Nytris\Boot\PlatformConfig;
use Nytris\Nytris;

/**
 * Class StreamHandlerTest.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class StreamHandlerTest extends AbstractFunctionalTestCase
{
    private BootConfig $bootConfig;
    private MockInterface&StorageInterface $storage;
    private string $varPath;

    public function setUp(): void
    {
        $this->varPath = dirname(__DIR__, 4) . '/var/test';
        @mkdir($this->varPath, recursive: true);

        $this->storage = mock(StorageInterface::class, [
            'fetchStatCache' => [
                '/my/first/path' => ['size' => 1234],
            ],
            'isSupported' => true,
            'saveStatCache' => null,
        ]);

        Nytris::uninitialise();
        Nytris::initialise();
        Shift::uninstall();
        Launch::uninstall();

        $this->bootConfig = new BootConfig(
            new PlatformConfig(baseCachePath: $this->varPath)
        );
        $this->bootConfig->installPackage(new AntilagPackage(stage: Stage::STAGE_2));
    }

    public function tearDown(): void
    {
        Shift::uninstall();
        Launch::uninstall();
        Nytris::uninitialise();

        $this->rimrafDescendantsOf($this->varPath);
    }

    public function testStage2ReadsFromStatCacheForStreamStats(): void
    {
        $this->storage->allows()
            ->fetchStatCache()
            ->andReturn([
                __FILE__ => ['size' => 4321],
            ]);
        Antilag::stage1(storage: $this->storage);
        Nytris::boot($this->bootConfig);

        $stream = fopen(__FILE__, 'rb');
        $stat = fstat($stream);

        static::assertEquals(4321, $stat['size']);
        static::assertEquals(4321, $stat[7]);
    }

    public function testStage2ReadsFromStatCacheForUrlStats(): void
    {
        Antilag::stage1(storage: $this->storage);
        Nytris::boot($this->bootConfig);

        $stat = stat('/my/first/path');

        static::assertEquals(1234, $stat['size']);
        static::assertEquals(1234, $stat[7]);
    }

    public function testStage3StoresNewlyCachedStatsFromStage1StreamWrapperStreamStat(): void
    {
        Antilag::stage1(storage: $this->storage);
        Nytris::boot($this->bootConfig);
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
        Antilag::stage1(storage: $this->storage);
        Nytris::boot($this->bootConfig);

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
