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

namespace Nytris\Antilag\Stage2;

use Asmblah\PhpCodeShift\Shifter\Stream\Handler\AbstractStreamHandlerDecorator;
use Asmblah\PhpCodeShift\Shifter\Stream\Native\StreamWrapperInterface;
use Nytris\Antilag\Antilag;

/**
 * Class StreamHandler.
 *
 * PHP Code Shift stream handler, registered during Antilag Stage 2 to replace Stage 1's stream wrapper.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class StreamHandler extends AbstractStreamHandlerDecorator
{
    /**
     * @inheritDoc
     */
    public function streamStat(StreamWrapperInterface $streamWrapper): array|false
    {
        $stat = Antilag::getCachedStat($streamWrapper->getOpenPath());

        if ($stat !== null) {
            return $stat;
        }

        $stat = parent::streamStat($streamWrapper);

        Antilag::cacheStat($streamWrapper->getOpenPath(), $stat);

        return $stat;
    }

    /**
     * @inheritDoc
     */
    public function urlStat(string $path, int $flags): array|false
    {
        $stat = Antilag::getCachedStat($path);

        if ($stat !== null) {
            return $stat;
        }

        $stat = parent::urlStat($path, $flags);

        Antilag::cacheStat($path, $stat);

        return $stat;
    }
}
