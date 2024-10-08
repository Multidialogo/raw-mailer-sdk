<?php

namespace multidialogo\RawMailerSdk\Test;

use multidialogo\RawMailerSdk\MailerInterface;

class FakeMailClient implements MailerInterface
{
    public function sendRawEmail(string $headers, string $body): string
    {
        return '250 OK: Message queued for delivery';
    }
}