<?php

namespace multidialogo\RawMailer;

use multidialogo\RawMailerSdk\Model\SmtpServerResponse;

class FakeMailClient implements MailerInterface
{
    public function sendRawEmail(string $headers, string $body): SmtpServerResponse
    {
        return new SmtpServerResponse(json_decode('{}'));
    }
}