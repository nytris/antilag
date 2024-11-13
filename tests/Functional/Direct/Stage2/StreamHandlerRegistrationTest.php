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
use Asmblah\PhpCodeShift\Shifter\Stream\Handler\Registration\RegistrantInterface;
use Asmblah\PhpCodeShift\Shifter\Stream\Handler\Registration\RegistrationInterface;
use Asmblah\PhpCodeShift\Shifter\Stream\Handler\StreamHandler;
use Asmblah\PhpCodeShift\Shifter\Stream\Handler\StreamHandlerInterface;
use Asmblah\PhpCodeShift\Shifter\Stream\StreamWrapperManager;
use Mockery;
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
 * Class StreamHandlerRegistrationTest.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class StreamHandlerRegistrationTest extends AbstractFunctionalTestCase
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

    public function testLaunchStage3RedecoratesTheSubsequentStreamHandlerToOurParent(): void
    {
        $this->storage->allows()
            ->fetchStatCache()
            ->andReturn([
                __FILE__ => ['size' => 4321],
            ]);
        Antilag::stage1(storage: $this->storage);
        Nytris::boot($this->bootConfig);
        /** @var MockInterface&RegistrantInterface<StreamHandlerInterface> $subsequentRegistrant */
        $subsequentRegistrant = mock(RegistrantInterface::class);
        /** @var MockInterface&RegistrationInterface<StreamHandlerInterface> $subsequentRegistration */
        $subsequentRegistration = mock(RegistrationInterface::class, [
            'register' => null,
        ]);
        $subsequentRegistrant->allows('registerStreamHandler')
            ->andReturn($subsequentRegistration);

        $subsequentRegistration->expects()
            ->register()
            ->once()
            ->globally()->ordered();
        $subsequentRegistration->expects()
            ->redecorate(Mockery::type(StreamHandler::class))
            ->once()
            ->globally()->ordered();

        StreamWrapperManager::registerStreamHandler($subsequentRegistrant);
        Launch::stage3();
    }
}
