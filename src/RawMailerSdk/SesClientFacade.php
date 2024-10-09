<?php

namespace multidialogo\RawMailerSdk;

use Aws\Exception\AwsException;
use Aws\Ses\SesClient;
use multidialogo\RawMailerSdk\Model\SmtpMessage;

class SesClientFacade implements MailerInterface
{

    private SesClient $sesClient;

    public function __construct(SesClient $sesClient)
    {
        $this->sesClient = $sesClient;
    }

    public function send(SmtpMessage $message): string
    {
        try {
            $result = $this->sesClient->sendRawEmail([
                'RawMessage' => [
                    'Data' => $message->getRawHeaders() . $message->getRawBody(),
                ],
            ]);

            $messageId = $result['MessageId'];

            return sprintf("250 2.0.0 Ok: queued as %s", $messageId);

        } catch (AwsException $e) {
            return match ($e->getAwsErrorCode()) {
                'MessageRejected' => "550 5.1.1 Recipient address rejected: {$message->getRecipientEmailAddress()}",
                'ThrottlingException' => '421 4.3.2 Service unavailable, try again later.',
                'LimitExceeded' => '452 4.3.1 Insufficient storage.',
                default => "500 5.0.0 Internal server error: {$e->getMessage()}",
            };
        }
    }
}
