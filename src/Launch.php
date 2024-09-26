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
        // Another more capable stream wrapper-based cache may now take over, such as Nytris Boost.
        Antilag::turnOff();

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
    public static function turnOff(): void
    {
        Antilag::turnOff();
    }

    /**
     * @inheritDoc
     */
    public static function turnOn(): void
    {
        Antilag::turnOn();
    }

    /**
     * @inheritDoc
     */
    public static function uninstall(): void
    {
        self::$isInstalled = false;
    }
}
