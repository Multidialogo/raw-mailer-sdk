<?php

namespace multidialogo\RawMailerSdk;

use multidialogo\RawMailerSdk\Model\SmtpMessage;

interface MailerInterface
{
    public function send(SmtpMessage $message): string;
}