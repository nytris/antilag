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
 * Enum Stage.
 *
 * Defines the multiple stages of Antilag setup.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
enum Stage
{
    case STAGE_1;
    case STAGE_2;
    case STAGE_3;
}
