<?php

namespace multidialogo\RawMailerSdk\Model;


use InvalidArgumentException;

class SmtpServerResponse
{
    private $code;
    private $message;

    /**
     * Constructor to initialize the SMTP response.
     *
     * @param string $response The raw SMTP response string.
     */
    public function __construct(string $response)
    {
        $this->parseResponse($response);
    }

    /**
     * Parse the raw SMTP response string.
     *
     * @param string $response The raw SMTP response string.
     */
    private function parseResponse(string $response): void
    {
        // Split response into code and message
        if (preg_match('/^(\d{3})\s*(.*)$/', $response, $matches)) {
            $this->code = (int)$matches[1];
            $this->message = trim($matches[2]);
        } else {
            throw new InvalidArgumentException("Invalid SMTP response format: '$response'");
        }
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
        return in_array($this->code, [421,450,451,452,]);
    }

    /**
     * Get a string representation of the SMTP response.
     *
     * @return string The formatted SMTP response.
     */
    public function __toString(): string
    {
        return sprintf("SMTP Response: [%d] %s", $this->code, $this->message);
    }
}

