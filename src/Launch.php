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

namespace Nytris\Antilag;

use Asmblah\PhpCodeShift\CodeShift;
use Asmblah\PhpCodeShift\Shifter\Stream\Native\StreamWrapper as ShiftStreamWrapper;
use Asmblah\PhpCodeShift\Shifter\Stream\StreamWrapperManager;
use InvalidArgumentException;
use LogicException;
use Nytris\Antilag\Stage2\StreamHandler;
use Nytris\Core\Package\PackageContextInterface;
use Nytris\Core\Package\PackageInterface;

/**
 * Class Launch.
 *
 * Defines the public facade API for the library.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class Launch implements LaunchInterface
{
    private static bool $isInstalled = false;

    /**
     * @inheritDoc
     */
    public static function getName(): string
    {
        return 'antilag';
    }

    /**
     * @inheritDoc
     */
    public static function getVendor(): string
    {
        return 'nytris';
    }

    /**
     * @inheritDoc
     */
    public static function install(PackageContextInterface $packageContext, PackageInterface $package): void
    {
        if (!$package instanceof AntilagPackageInterface) {
            throw new InvalidArgumentException(
                sprintf(
                    'Package config must be a %s but it was a %s',
                    AntilagPackageInterface::class,
                    $package::class
                )
            );
        }

        if ($package->getStage() === Stage::STAGE_1) {
            throw new LogicException('Stage 1 should be invoked using Antilag::stage1()');
        }

        if ($package->getStage() === Stage::STAGE_2) {
            self::stage2();
        } elseif ($package->getStage() === Stage::STAGE_3) {
            self::stage3();
        }

        self::$isInstalled = true;
    }

    /**
     * @inheritDoc
     */
    public static function isInstalled(): bool
    {
        return self::$isInstalled;
    }

    /**
     * @inheritDoc
     */
    public static function stage2(): void
    {
        // Note: the order is important here to ensure stats for all relevant class module files are cached.
        $codeShift = new CodeShift();

        $originalStreamHandler = StreamWrapperManager::getStreamHandler();
        $cachingStreamHandler = new StreamHandler($originalStreamHandler);
        StreamWrapperManager::setStreamHandler($cachingStreamHandler);

        if (!ShiftStreamWrapper::isRegistered()) {
            @stream_wrapper_restore('file');
        }

        $codeShift->install();
    }

    /**
     * @inheritDoc
     */
    public static function stage3(): void
    {
        Antilag::stage3();
    }

    /**
     * @inheritDoc
     */
    public static function uninstall(): void
    {
        Antilag::stage3();
        self::$isInstalled = false;
    }
}
