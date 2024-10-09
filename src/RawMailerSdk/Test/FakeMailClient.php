<?php

namespace multidialogo\RawMailerSdk\Test;

use multidialogo\RawMailerSdk\MailerInterface;
use multidialogo\RawMailerSdk\Model\SmtpMessage;

class FakeMailClient implements MailerInterface
{
    public const TEST_HEADERS = [
        'FAIL' => 'X-test-fail-internal',
        'BUSY' => 'X-test-fail-busy',
    ];

    public function send(SmtpMessage $message): string
    {
        // Sleep for the random duration, resilience testing
        usleep(rand(100000, 1000000));

        if ($message->hasHeader(static::TEST_HEADERS['FAIL'])) {
            return '500 5.0.0 Internal server error: foobar';
        }

        if ($message->hasHeader(static::TEST_HEADERS['BUSY'])) {
            return '421 4.3.2 Service unavailable, try again later.';
        }

        return '250 OK: Message queued for delivery';
    }
}