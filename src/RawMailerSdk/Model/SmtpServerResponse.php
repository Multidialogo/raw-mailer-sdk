<?php

namespace multidialogo\RawMailerSdk\Model;


use DateTimeImmutable;
use DateTimeZone;
use JsonSerializable;

class SmtpServerResponse implements JsonSerializable
{
    private int $code;

    private string $message;

    private string $rawResponse;

    private ?string $messageUuid;

    private ?int $attempt;

    private ?DateTimeImmutable $completedAt;


    private function __construct(int $code, string $message, string $rawResponse)
    {
        $this->code = $code;
        $this->message = $message;
        $this->rawResponse = $rawResponse;
    }

    public static function fromResponse(string $rawResponse): self
    {
        // Split response into code and message
        if (preg_match('/^(\d{3})\s*(.*)$/', $rawResponse, $matches)) {
            return new self((int)$matches[1], trim($matches[2]), $rawResponse);
        }

        return new self(666, "Brutal SMTP response format!", $rawResponse);
    }

    public static function fromResponseFile(string $responseFilePath): self
    {
        $instance =  static::fromResponse(file_get_contents($responseFilePath));

        $pathInfo = pathinfo($responseFilePath);

        $instance->messageUuid = $pathInfo['filename'];
        $instance->attempt = (int) $pathInfo['extension'];
        $instance->completedAt = DateTimeImmutable::createFromFormat('U', filectime($responseFilePath), new DateTimeZone('UTC'));

        return $instance;
    }

    /**
     * Get the SMTP response code.
     *
     * @return int The SMTP response code.
     */
    public function getCode(): int
    {
        return $this->code;
    }

    /**
     * Get the SMTP response message.
     *
     * @return string The SMTP response message.
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * Check if the response indicates success.
     *
     * @return bool True if the response code is 250, false otherwise.
     */
    public function isSuccess(): bool
    {
        return $this->code === 250;
    }

    /**
     * Check if the response indicates an error.
     *
     * @return bool True if the response code is in the 400 or 500 range, false otherwise.
     */
    public function isError(): bool
    {
        return ($this->code >= 400 && $this->code < 600) || $this->code === 666;
    }

    public function isBusy(): bool
    {
        return in_array($this->code, [421, 450, 451, 452,]);
    }

    public function getRawResponse(): string
    {
        return $this->rawResponse;
    }

    public function jsonSerialize() {
        $serialization = [
            'code' => $this->getCode(),
            'message' => $this->getMessage(),
            'rawResponse' => $this->getRawResponse()
        ];

        if ($this->messageUuid) {
            $serialization = array_merge(
                [
                    'messageUuid' => $this->messageUuid,
                    'attempt' => $this->attempt,
                    'completedAt' => $this->completedAt->format('Y-m-d H:i:s'),
                ],
                $serialization
            );
        }

        return $serialization;
    }
}

