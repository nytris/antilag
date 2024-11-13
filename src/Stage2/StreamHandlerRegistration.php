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

use Asmblah\PhpCodeShift\Shifter\Stream\Handler\Registration\AbstractRegistration;
use Asmblah\PhpCodeShift\Shifter\Stream\Handler\Registration\RegistrationInterface;
use Asmblah\PhpCodeShift\Shifter\Stream\Handler\StreamHandlerInterface;

/**
 * Class StreamHandlerRegistration.
 *
 * Defines how the stream handler is registered,
 * hooking into the process to ensure any replacement stream handler
 * is redecorated to point to the one registered before Antilag on stage 3.
 *
 * This ensures that Antilag is completely removed from the decoration chain
 * after that point for maximum efficiency.
 *
 * @phpstan-extends AbstractRegistration<StreamHandler>
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class StreamHandlerRegistration extends AbstractRegistration
{
    /**
     * @var RegistrationInterface<StreamHandlerInterface>|null
     */
    private ?RegistrationInterface $replacementRegistration = null;

    /**
     * Fetches the registration that has replaced this one, if any.
     *
     * @return RegistrationInterface<StreamHandlerInterface>|null
     */
    public function getReplacementRegistration(): ?RegistrationInterface
    {
        return $this->replacementRegistration;
    }

    /**
     * @inheritDoc
     */
    public function replace(RegistrationInterface $replacementRegistration): RegistrationInterface
    {
        // Record the registration that replaces this one, so that we can redecorate it
        // during stage 3.
        $this->replacementRegistration = $replacementRegistration;

        return parent::replace($replacementRegistration);
    }

    /**
     * @inheritDoc
     */
    public function unregister(): void
    {
        if ($this->replacementRegistration) {
            // Re-point the replacement for this stream handler that was installed
            // to the one registered before Antilag, to take Antilag out of the chain completely.
            $this->replacementRegistration->redecorate($this->previousStreamHandler);
        } else {
            parent::unregister();
        }
    }
}
