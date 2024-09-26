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

namespace Nytris\Antilag\Tests\Functional;

use Nytris\Antilag\Tests\AbstractTestCase;

/**
 * Class AbstractFunctionalTestCase.
 *
 * Base class for all functional test cases.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
abstract class AbstractFunctionalTestCase extends AbstractTestCase
{
    protected function rimrafDescendantsOf(string $path): void
    {
        foreach (glob($path . '/**') as $subPath) {
            if (is_file($subPath) || is_link($subPath)) {
                unlink($subPath);
            } else {
                $this->rimrafDescendantsOf($subPath);

                rmdir($subPath);
            }
        }
    }
}
