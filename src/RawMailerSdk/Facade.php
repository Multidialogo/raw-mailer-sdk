<?php

namespace multidialogo\RawMailerSdk;

use Aws\Ses\SesClient;
use InvalidArgumentException;
use multidialogo\RawMailerSdk\Test\FakeMailClient;
use multidialogo\RawMailerSdk\Model\Message;
use multidialogo\RawMailerSdk\Model\SmtpServerResponse;
use RuntimeException;

class Facade
{
    public const DRIVERS = [
        'FAKE' => 'FAKE',
        'SES' => 'SES',
        'STD' => 'STD',
    ];

    public const MAX_ATTACHMENT_SIZE_AWS_SES = 6815744; #6.5MB

    public const MAX_ATTACHMENT_SIZE_SMTP = 22020096; #15MB

    public const MAX_PARALLEL_JOBS = 10;

    private MailerInterface $smtpClient;

    private string $senderEmail;

    private string $resultBaseDir;

    private ?string $replyToEmail;

    private ?string $catchallDomain;

    private string $customBoundaryPrefix;

    private int $parallelJobs;

    /**
     * @param string $driver
     * @param array|null $awsConfig
     * @param array|null $simpleSmtpConfig
     * @param string $senderEmail
     * @param string $resultBaseDir
     * @param string|null $replyToEmail
     * @param string|null $catchallDomain
     * @param string $customBoundaryPrefix
     * @param int $parallelJobs
     */
    public function __construct(
        string  $driver,
        ?array  $awsConfig,
        ?array  $simpleSmtpConfig,
        string  $senderEmail,
        string  $resultBaseDir,
        ?string $replyToEmail = null,
        ?string $catchallDomain = null,
        string  $customBoundaryPrefix = 'boundary_',
        int     $parallelJobs = 10
    )
    {
        if (!filter_var($senderEmail, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException("Invalid sender {$senderEmail}");
        }

        if (!is_writable($resultBaseDir)) {
           throw new InvalidArgumentException("{$resultBaseDir} is not writable");
        }

        if ($replyToEmail && !filter_var($replyToEmail, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException("Invalid reply to {$replyToEmail}");
        }

        if ($parallelJobs > static::MAX_PARALLEL_JOBS) {
            throw new InvalidArgumentException('Parallelism cannot exceed: ' . static::MAX_PARALLEL_JOBS . ' jobs');
        }

        switch ($driver) {
            case static::DRIVERS['FAKE']:
                $this->smtpClient = new FakeMailClient();

                break;

            case static::DRIVERS['SES']:
                if (!$awsConfig) {
                    throw new RuntimeException('Missing AWS configuration');
                }

                $this->smtpClient = new SesClientFacade(
                    new SesClient(
                        [
                            'version' => $awsConfig['version'],
                            'region' => $awsConfig['region'],
                            'credentials' => [
                                'key' => $awsConfig['accessKey'],
                                'secret' => $awsConfig['secretKey'],
                            ],
                        ]
                    )
                );

                break;

            case static::DRIVERS['STD']:
                if (!$simpleSmtpConfig) {
                    throw new RuntimeException('Missing SIMPLE SMTP configuration');
                }

                $this->smtpClient = new SwiftMailerClientFacade(
                    $simpleSmtpConfig['username'],
                    $simpleSmtpConfig['password'],
                    $simpleSmtpConfig['host'],
                    $simpleSmtpConfig['port']
                );

                break;

            default:
                throw new RuntimeException("Invalid driver {$driver}");
        }

        $this->senderEmail = $senderEmail;
        $this->resultBaseDir = $resultBaseDir;
        $this->replyToEmail = $replyToEmail;

        $this->catchallDomain = $catchallDomain;
        $this->customBoundaryPrefix = $customBoundaryPrefix;
        $this->parallelJobs = $parallelJobs;
    }

    /**
     * @param Message[] $messages
     * @param int $maxAttempts
     * @return SmtpServerResponse[], results
     */
    public function parallelSend(array $messages, int $maxAttempts = 1): array
    {
        $batches = array_chunk($messages, $this->parallelJobs);

        $resultDirectory = "{$this->resultBaseDir}/" . uniqid('results/', true);
        if (!is_dir($resultDirectory)) {
            if (!mkdir($resultDirectory, 0755, true)) {
                throw new RuntimeException("Failed to create directory {$resultDirectory}");
            }
        }

        $pids = [];
        foreach ($batches as $batch) {
            foreach ($batch as $message) {
                if (!$message instanceof Message) {
                    throw new InvalidArgumentException('All messages must be a ' . Message::class . ' instance');
                }

                $pid = pcntl_fork();

                if ($pid == -1) {
                    // Error handling for fork failure
                    die("Could not fork");
                } elseif ($pid) {
                    // Parent process
                    $pids[] = $pid; // Keep track of child process IDs
                } else {
                    // Child process
                    $attempts = 0;
                    do {
                        $result = SmtpServerResponse::fromResponse(
                            $this->send(
                                $this->senderEmail,
                                $message->getRecipient(),
                                $message->getSubject(),
                                $message->getAdditionalHeaderLines(),
                                $message->getPlainText(),
                                $message->getHtmlText(),
                                $message->getAttachmentPaths()
                            )
                        );

                        file_put_contents("{$resultDirectory}/{$message->getUuid()}.{$attempts}", $result->getRawResponse());

                        if ($attempts) {
                            sleep(2 * $attempts);
                        }
                    } while ($result->isBusy() && $attempts++ <= $maxAttempts);


                    // Terminate child process after sending
                    exit((int)$result->isError());
                }
            }
        }

        // Wait for all child processes to finish
        foreach ($pids as $pid) {
            pcntl_waitpid($pid, $status);
        }

        $results = [];
        $resultFiles = glob("{$resultDirectory}/*");
        foreach ($resultFiles as $resultFile) {
            $results[] = SmtpServerResponse::fromResponseFile($resultFile);
        }

        return $results;
    }

    private function send(
        string $sender,
        string $recipient,
        string $subject,
        array  $additionalHeaderLines,
        string $bodyText,
        string $bodyHtml,
        array  $attachmentPaths
    ): string
    {
        // Create the MIME boundary
        $boundary = uniqid($this->customBoundaryPrefix);

        // Create the email headers
        $headers = "From: $sender\r\n";
        $headers .= "To: {$this->alterEmailDomainIfNonProductionEnvironment($recipient)}\r\n";
        if ($this->replyToEmail) {
            $headers .= "Reply-To: {$this->replyToEmail}\r\n";
        }
        $headers .= "Subject: $subject\r\n";
        $headers .= "MIME-Version: 1.0\r\n";

        foreach ($additionalHeaderLines as $additionalHeaderLine) {
            $headers .= "{$additionalHeaderLine}\r\n";
        }
        $headers .= "Content-Type: multipart/mixed; boundary=\"$boundary\"\r\n";

        // Create the body of the email
        $body = "--$boundary\r\n";
        $body .= "Content-Type: multipart/alternative; charset=UTF-8\r\n";
        $body .= "MIME-Version: 1.0\r\n\r\n";
        $body .= "--$boundary\r\n";
        $body .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $body .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
        $body .= $bodyText . "\r\n\r\n";
        $body .= "--$boundary\r\n";
        $body .= "Content-Type: text/html; charset=UTF-8\r\n";
        $body .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
        $body .= $bodyHtml . "\r\n\r\n";

        $fileAttachmentNames = [];
        foreach ($attachmentPaths as $attachmentPath) {
            if (!file_exists($attachmentPath)) {
                throw new RuntimeException("Attachment path $attachmentPath does not exist");
            }
            $fileName = basename($attachmentPath);

            if (isset($fileAttachmentNames[$fileName])) {
                $fileAttachmentNames[$fileName]++;
                $pathInfo = pathinfo($fileName);
                $fileName = $pathInfo['filename'] . ".{ fileAttachmentNames[$fileName]}." . $pathInfo['extension'];
            } else {
                $fileAttachmentNames[$fileName] = 0;
            }

            $fileType = mime_content_type($attachmentPath);

            $body .= "--$boundary\r\n";
            $body .= "Content-Type: $fileType; name=\"$fileName\"\r\n";
            $body .= "Content-Transfer-Encoding: base64\r\n";
            $body .= "Content-Disposition: attachment; filename=\"$fileName\"\r\n\r\n";

            //FIXME: If possible use a faster and less memory avid solution than file get contents
            $fileContent = file_get_contents($attachmentPath);
            $body .= chunk_split(base64_encode($fileContent)) . "\r\n";
        }

        if ($this->smtpClient instanceof SesClientFacade && strlen($body) > static::MAX_ATTACHMENT_SIZE_AWS_SES) {
            throw new InvalidArgumentException('Attachment size cannot exceed ' . static::MAX_ATTACHMENT_SIZE_AWS_SES . ' bytes, please use the simple mailer driver instead');
        } else if ($this->smtpClient instanceof SwiftMailerClientFacade && strlen($body) > static::MAX_ATTACHMENT_SIZE_SMTP) {
            throw new InvalidArgumentException('Attachment size cannot exceed ' . static::MAX_ATTACHMENT_SIZE_SMTP . ' bytes');
        }

        $body .= "--$boundary--";

        return $this->smtpClient->sendRawEmail($headers, $body);
    }

    private function alterEmailDomainIfNonProductionEnvironment(string $email): string
    {
        if (!$this->catchallDomain) {
            return $email;
        }

        if (str_contains($email, $this->catchallDomain)) {
            return $email;
        }

        return str_replace('@', '_AT_', $email) . "@{$this->catchallDomain}";
    }
}
