<?php

namespace Tests\RawMailerSdk;

use multidialogo\RawMailerSdk\Facade;
use multidialogo\RawMailerSdk\Model\Message;
use PHPUnit\Framework\TestCase;

class FacadeTest extends TestCase
{
    public function testParallelSend()
    {
        $responses = (new Facade(
            Facade::DRIVERS['FAKE'],
            null,
            null,
            'test@sender.multidialogo.it'
        ))->parallelSend(
            [
                new Message(
                    '70058d57-e4cd-491e-9300-f8b89c3cd05f',
                    'test@recipient.multidialogo.it',
                    'Test subject',
                    ['X-foobar: fo bar baz',],
                    'Plain text content',
                    '<html lang="en"><body>Html content</body></html>',
                    [
                        __DIR__ . '/fixtures/testParallelSend/01.pdf',
                    ]
                ),
                new Message(
                    '4f41efd7-38ce-4d30-8a32-155a6ec8001b',
                    'test@recipient.multidialogo.it',
                    'Test subject',
                    ['X-foobar: fo bar baz',],
                    'Plain text content',
                    '<html lang="en"><body>Html content</body></html>',
                    [
                        __DIR__ . '/fixtures/testParallelSend/01.pdf',
                    ]
                ),
            ]
        );

        static::assertEquals('SMTP Response: [250] OK: Message queued for delivery', $responses[0]->__toString());
    }
}
