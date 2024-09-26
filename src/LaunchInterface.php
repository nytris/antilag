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

use Nytris\Core\Package\PackageFacadeInterface;

/**
 * Interface LaunchInterface.
 *
 * Defines the public facade API for the library.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
interface LaunchInterface extends PackageFacadeInterface
{
    /**
     * Turns off antilag.
     */
    public static function turnOff(): void;

    /**
     * Turns on antilag.
     */
    public static function turnOn(): void;
}
