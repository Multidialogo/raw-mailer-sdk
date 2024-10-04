<?php

namespace multidialogo\RawMailerSdk\Model;

use InvalidArgumentException;

class Message
{
    private string $uuid;

    private string $folder;

    private string $recipient;

    private string $subject;

    private array $additionalHeaderLines;

    private string $plainText;

    private string $htmlText;

    private array $attachmentPaths;

    public function __construct(
        string $uuid,
        string $folder,
        string $recipient,
        string $subject,
        array  $additionalHeaderLines,
        string $plainText,
        string $htmlText,
        array  $attachmentPaths
    )
    {
        if (!filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException("Invalid recipient $recipient");
        }

        if (preg_match('/^[\x20-\x7E]*$/', $subject) != 1) {
            throw new InvalidArgumentException("Invalid subject $subject");
        }

        foreach ($additionalHeaderLines as $additionalHeaderLine) {
            if (preg_match('/^[\x20-\x7E]+:\s*.*$/', $additionalHeaderLine) != 1) {
                throw new InvalidArgumentException("Invalid header line $additionalHeaderLine");
            }
        }

        foreach ($attachmentPaths as $attachmentPath) {
            if (!file_exists($attachmentPath)) {
                throw new InvalidArgumentException("Attachment path $attachmentPath does not exist");
            }
        }

        $this->uuid = $uuid;
        $this->folder = $folder;
        $this->recipient = $recipient;
        $this->subject = $subject;
        $this->additionalHeaderLines = $additionalHeaderLines;
        $this->plainText = $plainText;
        $this->htmlText = $htmlText;
        $this->attachmentPaths = $attachmentPaths;
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function getFolder(): string
    {
        return $this->folder;
    }

    public function getRecipient(): string
    {
        return $this->recipient;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function getAdditionalHeaderLines(): array
    {
        return $this->additionalHeaderLines;
    }

    public function getPlainText(): string
    {
        return $this->plainText;
    }

    public function getHtmlText(): string
    {
        return $this->htmlText;
    }

    public function getAttachmentPaths(): array
    {
        return $this->attachmentPaths;
    }
}
