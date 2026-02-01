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

use LogicException;
use Nytris\Ignition\Ignition;

/**
 * Class Antilag.
 *
 * Initial entrypoint for the library.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class Antilag
{
    private static bool $isOn = false;

    /**
     * Fetches whether antilag is currently on.
     */
    public static function isOn(): bool
    {
        return self::$isOn;
    }

    /**
     * Turns on antilag.
     */
    public static function stage1(): void
    {
        if (self::$isOn) {
            throw new LogicException('Nytris Antilag already turned on');
        }

        // Keep Ignition's filesystem caching around while Boost starts up (if configured).
        // Stage 3 will later perform the handoff explicitly.
        Ignition::disableAutoHandoff();

        self::$isOn = true;
    }

    /**
     * Turns off antilag.
     */
    public static function stage3(): void
    {
        if (!self::$isOn) {
            return;
        }

        if (Ignition::isChokeOn()) {
            Ignition::handOff();
        }

        self::$isOn = false;
    }
}
