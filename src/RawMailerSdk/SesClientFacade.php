<?php

namespace multidialogo\RawMailerSdk;

use Aws\Exception\AwsException;
use Aws\Ses\SesClient;
use multidialogo\RawMailerSdk\Model\SmtpMessage;

class SesClientFacade implements MailerInterface
{

    private $sesClient;

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
                'MessageRejected' => '550 5.1.1 Recipient address rejected: ' . implode(', ', static::extractRecipients($headers)),
                'ThrottlingException' => '421 4.3.2 Service unavailable, try again later.',
                'LimitExceeded' => '452 4.3.1 Insufficient storage.',
                default => '500 5.0.0 Internal server error: ' . $e->getMessage(),
            };
        }
    }

    private static function extractRecipients(string $headers) {
        // Define a regex pattern to capture email addresses in the "To", "Cc", and "Bcc" fields
        $pattern = '/^(?:To|Cc|Bcc):\s*(.+)$/mi';

        preg_match_all($pattern, $headers, $matches);

        $recipients = [];

        foreach ($matches[1] as $recipientLine) {
            $emails = array_map('trim', explode(',', $recipientLine));
            $recipients = array_merge($recipients, $emails);
        }

        return $recipients;
    }
}