<?php

namespace multidialogo\RawMailerSdk\Test;

use multidialogo\RawMailerSdk\MailerInterface;

class FakeMailClient implements MailerInterface
{
    public function sendRawEmail(string $headers, string $body): string
    {
        // Sleep for the random duration, resilience testing
        usleep(rand(100000, 1000000));

        if (str_contains($headers, 'X-test-fail-internal')) {
            return '500 5.0.0 Internal server error: foobar';
        }


        if (str_contains($headers, 'X-test-fail-busy')) {
            return '421 4.3.2 Service unavailable, try again later.';
        }

        return '250 OK: Message queued for delivery';
    }
}