<?php

namespace Tests\RawMailerSdk;

use multidialogo\RawMailerSdk\Facade;
use multidialogo\RawMailerSdk\Model\SmtpMessage;
use multidialogo\RawMailerSdk\Model\SmtpHeader;
use multidialogo\RawMailerSdk\Test\FakeMailClient;
use PHPUnit\Framework\TestCase;

class FacadeTest extends TestCase
{
    /**
     * @dataProvider provideData
     */
    public function testParallelSend(string $case, string $driver, ?array $config, array $messages)
    {
        $responses = (new Facade(
            $driver,
            $config,
            __DIR__ . '/../../'
        ))->parallelSend($messages);

        $parsedResponse = preg_replace('/("completedAt":\s*")[^"]*(")/', '$1' . '{{date}}' . '$2', json_encode($responses));
        $parsedResponse = preg_replace('/queued as ([a-z0-9-]+)/', 'queued as {{aws-message-id}}', $parsedResponse);

        static::assertJsonStringEqualsJsonFile(
            __DIR__ . "/expectations/testParallelSend.{$case}.json",
            $parsedResponse
        );
    }

    public static function provideData(): array
    {
        return [
            [
                'fake.client',
                Facade::DRIVERS['FAKE'],
                null,
                [
                    new SmtpMessage(
                        '4f41efd7-38ce-4d30-8a32-155a6ec8001b',
                        getenv('MAIL_FROM_ADDRESS'),
                        'test@recipient.multidialogo.it',
                        'Test subject',
                        [new SmtpHeader(FakeMailClient::TEST_HEADERS['FAIL'], 'fail'),],
                        'Plain text content',
                        '<html lang="en"><body>Html content</body></html>',
                        [
                            __DIR__ . '/fixtures/testParallelSend/01.pdf',
                        ]
                    ),
                    new SmtpMessage(
                        '70058d57-e4cd-491e-9300-f8b89c3cd05f',
                        getenv('MAIL_FROM_ADDRESS'),
                        'test@recipient2.multidialogo.it',
                        'Test subject',
                        [new SmtpHeader('X-foobar', 'fo bar baz'),],
                        'Plain text content',
                        '<html lang="en"><body>Html content</body></html>',
                        [
                            __DIR__ . '/fixtures/testParallelSend/01.pdf',
                        ]
                    ),
                    new SmtpMessage(
                        '8a26886e-37ce-4569-9541-bbeb67a57c66',
                        getenv('MAIL_FROM_ADDRESS'),
                        'test@recipient3.multidialogo.it',
                        'Test subject',
                        [new SmtpHeader(FakeMailClient::TEST_HEADERS['BUSY'], 'busy'),],
                        'Plain text content',
                        '<html lang="en"><body>Html content</body></html>',
                        [
                            __DIR__ . '/fixtures/testParallelSend/01.pdf',
                        ]
                    ),
                ],
            ],
            [
                'std.client',
                Facade::DRIVERS['STD'],
                [
                    'host' => getenv('SMTP_HOST'),
                    'port' => (int)getenv('SMTP_PORT'),
                    'username' => getenv('SMTP_USERNAME'),
                    'password' => getenv('SMTP_PASSWORD'),
                ],
                [
                    new SmtpMessage(
                        '4f41efd7-38ce-4d30-8a32-155a6ec8001b',
                        getenv('MAIL_FROM_ADDRESS'),
                        'test@recipient.multidialogo.it',
                        'Test subject',
                        [new SmtpHeader('X-foobar', 'fo bar baz'),],
                        'Plain text content',
                        '<html lang="en"><body>Html content</body></html>',
                        [
                            __DIR__ . '/fixtures/testParallelSend/01.pdf',
                        ]
                    ),
                    new SmtpMessage(
                        '70058d57-e4cd-491e-9300-f8b89c3cd05f',
                        getenv('MAIL_FROM_ADDRESS'),
                        'test@recipient2.multidialogo.it',
                        'Test subject',
                        [new SmtpHeader('X-foobar', 'fo bar baz'),],
                        'Plain text content',
                        '<html lang="en"><body>Html content</body></html>',
                        [
                            __DIR__ . '/fixtures/testParallelSend/01.pdf',
                        ]
                    ),
                ],
            ],
            [
                'ses.client',
                Facade::DRIVERS['SES'],
                [
                    'version' => 'latest',
                    'region' => getenv('AWS_DEFAULT_REGION'),
                    'accessKey' => getenv('AWS_ACCESS_KEY_ID'),
                    'secretKey' => getenv('AWS_SECRET_ACCESS_KEY'),
                    'host' => 'http://' . getenv('LOCALSTACK_HOST'),
                    'port' => (int)getenv('LOCALSTACK_PORT'),
                ],
                [
                    new SmtpMessage(
                        '4f41efd7-38ce-4d30-8a32-155a6ec8001b',
                        getenv('MAIL_FROM_ADDRESS'),
                        'test@recipient.multidialogo.it',
                        'Test subject',
                        [new SmtpHeader('X-foobar', 'fo bar baz'),],
                        'Plain text content',
                        '<html lang="en"><body>Html content</body></html>',
                        [
                            __DIR__ . '/fixtures/testParallelSend/01.pdf',
                        ]
                    ),
                    new SmtpMessage(
                        '70058d57-e4cd-491e-9300-f8b89c3cd05f',
                        getenv('MAIL_FROM_ADDRESS'),
                        'test@recipient2.multidialogo.it',
                        'Test subject',
                        [new SmtpHeader('X-foobar', 'fo bar baz'),],
                        'Plain text content',
                        '<html lang="en"><body>Html content</body></html>',
                        [
                            __DIR__ . '/fixtures/testParallelSend/01.pdf',
                        ]
                    ),
                ],
            ],
        ];
    }
}
