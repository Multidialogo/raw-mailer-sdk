<?php

namespace multidialogo\RawMailerSdk\Model;

use InvalidArgumentException;

class SmtpHeader
{
    private string $name;

    private string $value;

    public function __construct(string $name, string $value)
    {
        if (!preg_match('/^[A-Za-z0-9-]+$/', $name)) {
            throw new InvalidArgumentException("Invalid SMTP header name: {$name}");
        }

        if (!preg_match('/^[\x20-\x7E]+$/', $value)) {
            throw new InvalidArgumentException("Invalid SMTP header value: {$value}");
        }

        $this->name = $name;
        $this->value = $value;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getValue(): string
    {
        return $this->value;
    }
}