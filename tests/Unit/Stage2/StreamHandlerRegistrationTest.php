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

namespace Nytris\Antilag\Tests\Unit\Stage2;

use Asmblah\PhpCodeShift\Shifter\Stream\Handler\Registration\RegistrationInterface;
use Asmblah\PhpCodeShift\Shifter\Stream\Handler\StreamHandlerInterface;
use Asmblah\PhpCodeShift\Shifter\Stream\StreamWrapperManager;
use Mockery\MockInterface;
use Nytris\Antilag\Stage2\StreamHandlerRegistration;
use Nytris\Antilag\Tests\AbstractTestCase;

/**
 * Class StreamHandlerRegistrationTest.
 *
 * @author Dan Phillimore <dan@ovms.co>
 */
class StreamHandlerRegistrationTest extends AbstractTestCase
{
    private MockInterface&StreamHandlerInterface $previousStreamHandler;
    private StreamHandlerRegistration $registration;
    private MockInterface&StreamHandlerInterface $streamHandler;

    public function setUp(): void
    {
        $this->previousStreamHandler = mock(StreamHandlerInterface::class);
        $this->streamHandler = mock(StreamHandlerInterface::class);

        $this->registration = new StreamHandlerRegistration(
            streamHandler: $this->streamHandler,
            previousStreamHandler: $this->previousStreamHandler
        );
    }

    public function testGetReplacementRegistrationReturnsNullInitially(): void
    {
        $this->registration->register();

        static::assertNull($this->registration->getReplacementRegistration());
    }

    public function testReplaceStoresReplacementRegistration(): void
    {
        $replacementRegistration = mock(RegistrationInterface::class);
        $this->registration->register();

        static::assertSame(
            $replacementRegistration,
            $this->registration->replace($replacementRegistration)
        );
        static::assertSame(
            $replacementRegistration,
            $this->registration->getReplacementRegistration()
        );
    }

    public function testUnregisterRedecoratesReplacementRegistrationWithPreviousHandler(): void
    {
        $replacementRegistration = mock(RegistrationInterface::class);
        $this->registration->replace($replacementRegistration);
        $this->registration->register();

        $replacementRegistration->expects()
            ->redecorate($this->previousStreamHandler)
            ->once();

        $this->registration->unregister();
    }

    public function testUnregisterDoesNotChangeStreamHandlerWhenReplacementWasRegistered(): void
    {
        $replacementRegistration = mock(RegistrationInterface::class, [
            'redecorate' => null,
        ]);
        $this->registration->register();
        $this->registration->replace($replacementRegistration);
        $streamHandler = StreamWrapperManager::getStreamHandler();

        $this->registration->unregister();

        static::assertSame($streamHandler, StreamWrapperManager::getStreamHandler());
    }

    public function testUnregisterRestoresPreviousStreamHandlerWhenNoReplacementWasRegistered(): void
    {
        $this->registration->register();

        $this->registration->unregister();

        static::assertSame($this->previousStreamHandler, StreamWrapperManager::getStreamHandler());
    }
}
