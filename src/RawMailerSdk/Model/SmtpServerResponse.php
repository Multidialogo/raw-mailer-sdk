<?php

namespace multidialogo\RawMailerSdk\Model;


use InvalidArgumentException;
use JsonSerializable;

class SmtpServerResponse implements JsonSerializable
{
    private int $code;

    private string $message;

    private string $rawResponse;

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

        throw new InvalidArgumentException("Invalid SMTP response format: '{$rawResponse}'");
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
        return ($this->code >= 400 && $this->code < 600);
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
        return [
            'code' => $this->getCode(),
            'message' => $this->getMessage(),
            'rawResponse' => $this->getRawResponse()
        ];
    }
}

