<?php

namespace multidialogo\RawMailerSdk;

use Aws\Ses\SesClient;
use InvalidArgumentException;
use multidialogo\RawMailerSdk\Test\FakeMailClient;
use multidialogo\RawMailerSdk\Model\BaseMessage;
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

    private string $resultBaseDir;

    private ?string $catchallDomain;

    private string $customBoundaryPrefix;

    private int $parallelJobs;

    /**
     * @param string $driver
     * @param array|null $config
     * @param string $resultBaseDir
     * @param string|null $catchallDomain
     * @param string $customBoundaryPrefix
     * @param int $parallelJobs
     */
    public function __construct(
        string  $driver,
        ?array  $config,
        string  $resultBaseDir,
        ?string $catchallDomain = null,
        string  $customBoundaryPrefix = 'boundary_',
        int     $parallelJobs = 10
    )
    {
        if (!is_writable($resultBaseDir)) {
           throw new InvalidArgumentException("{$resultBaseDir} is not writable");
        }

        if ($parallelJobs > static::MAX_PARALLEL_JOBS) {
            throw new InvalidArgumentException('Parallelism cannot exceed: ' . static::MAX_PARALLEL_JOBS . ' jobs');
        }

        switch ($driver) {
            case static::DRIVERS['FAKE']:
                $this->smtpClient = new FakeMailClient();

                break;

            case static::DRIVERS['SES']:
                if (!$config) {
                    throw new RuntimeException('Missing AWS configuration');
                }

                $this->smtpClient = new SesClientFacade(
                    new SesClient(
                        [
                            'version' => $config['version'],
                            'region' => $config['region'],
                            'credentials' => [
                                'key' => $config['accessKey'],
                                'secret' => $config['secretKey'],
                            ],
                            'endpoint' => "{$config['host']}:{$config['port']}",
                        ]
                    )
                );

                break;

            case static::DRIVERS['STD']:
                if (!$config) {
                    throw new RuntimeException('Missing SIMPLE SMTP configuration');
                }

                $this->smtpClient = new SwiftMailerClientFacade(
                    $config['username'],
                    $config['password'],
                    $config['host'],
                    $config['port']
                );

                break;

            default:
                throw new RuntimeException("Invalid driver {$driver}");
        }

        $this->resultBaseDir = $resultBaseDir;

        $this->catchallDomain = $catchallDomain;
        $this->customBoundaryPrefix = $customBoundaryPrefix;
        $this->parallelJobs = $parallelJobs;
    }

    /**
     * @param BaseMessage[] $messages
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
                if (!$message instanceof BaseMessage) {
                    throw new InvalidArgumentException('All messages must be a ' . BaseMessage::class . ' instance');
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
                                $message
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

    /**
     * @param BaseMessage $message
     * @return string
     */
    private function send(
        BaseMessage $message
    ): string
    {
        if ($this->smtpClient instanceof SesClientFacade && $message->getSize() > static::MAX_ATTACHMENT_SIZE_AWS_SES) {
            throw new InvalidArgumentException('Attachment size cannot exceed ' . static::MAX_ATTACHMENT_SIZE_AWS_SES . ' bytes, please use the simple mailer driver instead');
        } else if ($this->smtpClient instanceof SwiftMailerClientFacade && $message->getSize() > static::MAX_ATTACHMENT_SIZE_SMTP) {
            throw new InvalidArgumentException('Attachment size cannot exceed ' . static::MAX_ATTACHMENT_SIZE_SMTP . ' bytes');
        }

        $headers = $message->getRawHeaders();
        $body = $message->getRawBody();

        return $this->smtpClient->sendRawEmail($headers, $body);
    }
}
