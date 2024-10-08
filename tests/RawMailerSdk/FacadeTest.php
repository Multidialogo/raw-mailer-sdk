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
            'test@sender.multidialogo.it',
            __DIR__ . '/../../'
        ))->parallelSend(
            [
                new Message(
                    '4f41efd7-38ce-4d30-8a32-155a6ec8001b',
                    'test@recipient.multidialogo.it',
                    'Test subject',
                    ['X-test-fail-internal: fail',],
                    'Plain text content',
                    '<html lang="en"><body>Html content</body></html>',
                    [
                        __DIR__ . '/fixtures/testParallelSend/01.pdf',
                    ]
                ),
                new Message(
                    '70058d57-e4cd-491e-9300-f8b89c3cd05f',
                    'test@recipient2.multidialogo.it',
                    'Test subject',
                    ['X-foobar: fo bar baz',],
                    'Plain text content',
                    '<html lang="en"><body>Html content</body></html>',
                    [
                        __DIR__ . '/fixtures/testParallelSend/01.pdf',
                    ]
                ),
                new Message(
                    '8a26886e-37ce-4569-9541-bbeb67a57c66',
                    'test@recipient3.multidialogo.it',
                    'Test subject',
                    ['X-test-fail-busy: busy',],
                    'Plain text content',
                    '<html lang="en"><body>Html content</body></html>',
                    [
                        __DIR__ . '/fixtures/testParallelSend/01.pdf',
                    ]
                ),
            ]
        );

        static::assertJsonStringEqualsJsonFile(
            __DIR__ . '/expectations/testParallelSend.json',
            preg_replace('/("completedAt":\s*")[^"]*(")/', '$1' . '{{date}}' . '$2', json_encode($responses))
        );
    }
}
