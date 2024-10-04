<?php

namespace multidialogo\RawMailer;

use multidialogo\RawMailerSdk\Model\SmtpServerResponse;

interface MailerInterface
{
    public function sendRawEmail(string $headers, string $body): SmtpServerResponse;
}