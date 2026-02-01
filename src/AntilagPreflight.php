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

use Closure;
use LogicException;
use Nytris\Ignition\Preflight\PreflightInterface;

/**
 * Class AntilagPreflight.
 *
 * Configures the installation of Nytris Antilag.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class AntilagPreflight implements PreflightInterface
{
    public function __construct(
        private readonly Stage $stage
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return 'antilag';
    }

    /**
     * @inheritDoc
     */
    public function getRunCallback(): Closure
    {
        return function () {
            if ($this->stage !== Stage::STAGE_1) {
                throw new LogicException('Preflight callback can only be run for Stage 1');
            }

            Antilag::stage1();
        };
    }

    /**
     * Fetches which stage of Antilag setup to perform.
     */
    public function getStage(): Stage
    {
        return $this->stage;
    }

    /**
     * @inheritDoc
     */
    public function getVendor(): string
    {
        return 'nytris';
    }
}
