<?php

namespace multidialogo\RawMailerSdk;

use multidialogo\RawMailerSdk\Model\SmtpMessage;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Email;
use Throwable;


class SwiftMailerClientFacade implements MailerInterface
{
    private Mailer $mailer;

    private TransportInterface $transport;

    public function __construct(string $host, int $port, ?string $username = null, ?string $password = null)
    {
        $identity = '';
        if ($username) {
            $identity = $username;
            if ($password) {
                $identity = "{$identity}:{$password}";
            }
            $identity .= '@';
        }

        $this->transport = Transport::fromDsn("smtp://{$identity}{$host}:{$port}");

        $this->mailer = new Mailer($this->transport);
    }

    public function send(SmtpMessage $message): string
    {
        $email = (new Email())
            ->from($message->getSenderEmailAddress())
            ->to($message->getRecipientEmailAddress())
            ->subject($message->getSubject())
            ->text($message->getPlainTextBody())
            ->html($message->getHtmlTextBody());

        foreach ($message->getAdditionalHeaders() as $additionalHeader) {
            $email->getHeaders()->addTextHeader($additionalHeader->getName(), $additionalHeader->getValue());
        }

        foreach ($message->getAttachmentPaths() as $filePath) {
            $email->attachFromPath($filePath);
        }

        try {
            $this->mailer->send($email);
        } catch (TransportExceptionInterface $te) {
            return "500 5.0.0 Internal server error: {$te->getMessage()}";
        } catch (Throwable $t) {
            // TODO transform response in "original http headers"
            return $t->getMessage();
        }

        return '250 2.0.0 Ok: sent';
    }
}
