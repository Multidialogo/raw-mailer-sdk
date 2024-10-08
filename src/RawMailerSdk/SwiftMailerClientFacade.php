<?php

namespace multidialogo\RawMailerSdk;

use multidialogo\RawMailerSdk\Model\SmtpServerResponse;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mime\RawMessage;

class SwiftMailerClientFacade implements MailerInterface
{
    private Mailer $mailer;

    private Transport $transport;

    public function __construct(string $username, string $password, string $host, int $port)
    {
        $this->transport = Trransport::fromDsn("smtp://{$username}:{$password}@{$host}:{$port}");

        $this->mailer = new Mailer($this->transport);
    }

    public function sendRawEmail(string $headers, string $body): string
    {
        $result = $this->mailer->send(new RawMessage($headers . "\r\n" . $body));

        return $this->transport->getLastResponse();
    }
}
