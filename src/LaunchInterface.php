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
     * Moves to the second stage of Antilag, which switches from its own stream wrapper to PHP Code Shift's.
     */
    public static function stage2(): void;

    /**
     * Moves to the third stage of Antilag, which unregisters Antilag's stat caching
     * in favour of another more capable stream wrapper-based cache, such as Nytris Boost.
     */
    public static function stage3(): void;
}
