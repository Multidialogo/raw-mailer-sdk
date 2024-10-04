<?php

namespace multidialogo\RawMailer;

use Aws\Exception\AwsException;
use Aws\Ses\SesClient;
use multidialogo\RawMailerSdk\Model\SmtpServerResponse;

class SesClientFacade implements MailerInterface
{

    private $sesClient;

    public function __construct(SesClient $sesClient)
    {
        $this->sesClient = $sesClient;
    }

    public function sendRawEmail(string $headers, string $body): SmtpServerResponse
    {
        try {
            $result = $this->sesClient->sendRawEmail([
                'RawMessage' => [
                    'Data' => $headers . $body,
                ],
            ]);

            $messageId = $result['MessageId'];
            $smtpResponse = sprintf("250 2.0.0 Ok: queued as %s", $messageId);

        } catch (AwsException $e) {
            switch ($e->getAwsErrorCode()) {
                case 'MessageRejected':
                    $smtpResponse = '550 5.1.1 Recipient address rejected: ' . implode(', ', static::extractRecipients($headers));

                    break;
                case 'ThrottlingException':
                    $smtpResponse = '421 4.3.2 Service unavailable, try again later.';

                    break;
                case 'LimitExceeded':
                    $smtpResponse = '452 4.3.1 Insufficient storage.';

                    break;
                default:
                    $smtpResponse = '500 5.0.0 Internal server error: ' . $e->getMessage();

                    break;
            }
        }

        return new SmtpServerResponse($smtpResponse);
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