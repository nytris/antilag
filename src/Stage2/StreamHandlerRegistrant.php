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

use Asmblah\PhpCodeShift\Shifter\Stream\Handler\Registration\RegistrantInterface;
use Asmblah\PhpCodeShift\Shifter\Stream\Handler\Registration\RegistrationInterface;
use Asmblah\PhpCodeShift\Shifter\Stream\Handler\StreamHandlerInterface;

/**
 * Class StreamHandlerRegistrant.
 *
 * Handles the registration of the stream handler.
 *
 * @phpstan-implements RegistrantInterface<StreamHandler>
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class StreamHandlerRegistrant implements RegistrantInterface
{
    /**
     * @inheritDoc
     */
    public function registerStreamHandler(
        StreamHandlerInterface $currentStreamHandler,
        ?StreamHandlerInterface $previousStreamHandler
    ): RegistrationInterface {
        return new StreamHandlerRegistration(
            new StreamHandler($currentStreamHandler),
            $currentStreamHandler
        );
    }
}
