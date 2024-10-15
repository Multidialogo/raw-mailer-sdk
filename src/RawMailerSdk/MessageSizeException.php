<?php

namespace multidialogo\RawMailerSdk;


use InvalidArgumentException;

class MessageSizeException extends InvalidArgumentException
{
    private int $messageSize;

    private int $maxSize;

    private string $driver;

    private ?string $suggestedDriver;

    public function __construct(int $messageSize, int $maxSize, string $driver, ?string $suggestedDriver)
    {
        parent::__construct("Message size {$messageSize}, not allowed for driver: {$driver}. Suggested driver: {$suggestedDriver}");

        $this->messageSize = $messageSize;
        $this->maxSize = $maxSize;
        $this->driver = $driver;
        $this->suggestedDriver = $suggestedDriver;
    }

    public function getMessageSize(): int
    {
        return $this->messageSize;
    }

    public function getMaxSize(): int
    {
        return $this->maxSize;
    }

    public function getDriver(): string
    {
        return $this->driver;
    }

    public function getSuggestedDriver(): ?string
    {
        return $this->suggestedDriver;
    }
}
