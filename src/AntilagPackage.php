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

/**
 * Class AntilagPackage.
 *
 * Configures the installation of Nytris Antilag.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class AntilagPackage implements AntilagPackageInterface
{
    public function __construct(
        private readonly Stage $stage
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getPackageFacadeFqcn(): string
    {
        return Launch::class;
    }

    /**
     * @inheritDoc
     */
    public function getStage(): Stage
    {
        return $this->stage;
    }
}
