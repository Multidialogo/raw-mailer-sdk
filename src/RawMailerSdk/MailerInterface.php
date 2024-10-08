<?php

namespace multidialogo\RawMailerSdk;

use multidialogo\RawMailerSdk\Model\SmtpServerResponse;

interface MailerInterface
{
    public function sendRawEmail(string $headers, string $body): string;
}