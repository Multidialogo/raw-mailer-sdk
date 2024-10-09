<?php

namespace multidialogo\RawMailerSdk\Model;

use InvalidArgumentException;

class SmtpMessage
{
    private string $uuid;

    private string $senderEmailAddress;

    private ?string $replyToEmailAddress;

    private string $recipientEmailAddress;

    private string $subject;

    /**
     * @var SmtpHeader[]
     */
    private array $additionalHeaders;

    /**
     * @var SmtpHeader[]
     */
    private array $headers;

    private string $plainTextBody;

    private string $htmlTextBody;

    private array $attachmentPaths;

    private string $boundary;

    private string $charset;

    /**
     * @param string $uuid
     * @param string $senderEmailAddress
     * @param string $recipientEmailAddress
     * @param string $subject
     * @param array $additionalHeaders
     * @param string $plainTextBody
     * @param string $htmlTextBody
     * @param array $attachmentPaths
     * @param string|null $replyToEmailAddress
     * @param string|null $boundary
     * @param string $mimeVersion
     * @param string $charset
     */
    public function __construct(
        string  $uuid,
        string  $senderEmailAddress,
        string  $recipientEmailAddress,
        string  $subject,
        array   $additionalHeaders,
        string  $plainTextBody,
        string  $htmlTextBody,
        array   $attachmentPaths = [],
        ?string $replyToEmailAddress = null,
        ?string $boundary = null,
        string  $mimeVersion = '1.0',
        string $charset = 'UTF-8'
    )
    {
        if (!filter_var($senderEmailAddress, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException("Invalid sender $senderEmailAddress");
        }

        if (!filter_var($recipientEmailAddress, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException("Invalid recipient $recipientEmailAddress");
        }

        if (preg_match('/^[\x20-\x7E]*$/', $subject) != 1) {
            throw new InvalidArgumentException("Invalid subject $subject");
        }

        if ($replyToEmailAddress && !filter_var($replyToEmailAddress, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException("Invalid reply-to: {$replyToEmailAddress}");
        }

        foreach ($attachmentPaths as $attachmentPath) {
            if (!file_exists($attachmentPath)) {
                throw new InvalidArgumentException("Attachment path $attachmentPath does not exist");
            }
        }

        $this->uuid = $uuid;
        $this->senderEmailAddress = $senderEmailAddress;
        $this->replyToEmailAddress = $replyToEmailAddress;
        $this->recipientEmailAddress = $recipientEmailAddress;
        $this->subject = $subject;
        $this->plainTextBody = $plainTextBody;
        $this->htmlTextBody = $htmlTextBody;
        $this->attachmentPaths = $attachmentPaths;
        if (null === $boundary) {
            $this->boundary = md5(uniqid(rand(), true));
        } else {
            $this->boundary = $boundary;
        }
        $this->charset = $charset;

        // Create the email headers
        $this
            ->addHeader(new SmtpHeader('From', $senderEmailAddress))
            ->addHeader(new SmtpHeader('To', $recipientEmailAddress));

        if ($replyToEmailAddress) {
            $this->addHeader(new SmtpHeader('Reply-To', $replyToEmailAddress));
        }

        $this
            ->addHeader(new SmtpHeader('Subject', $subject))
            ->addHeader(new SmtpHeader('MIME-Version', $mimeVersion));

        $this->additionalHeaders = $additionalHeaders;
        foreach ($additionalHeaders as $additionalHeader) {
            if (!$additionalHeader instanceof SmtpHeader) {
                throw new InvalidArgumentException("Expected instance of " . SmtpHeader::class . " .  but received " . gettype($additionalHeader));
            }

            $this
                ->addHeader(new SmtpHeader($additionalHeader->getName(), $additionalHeader->getValue()));
        }

        $this->addHeader(new SmtpHeader('Content-Type', "multipart/mixed; boundary=\"{$this->boundary}\""));
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function getSenderEmailAddress(): string
    {
        return $this->senderEmailAddress;
    }

    public function getReplyToEmailAddress(): ?string
    {
        return $this->replyToEmailAddress;
    }

    public function getRecipientEmailAddress(): string
    {
        return $this->recipientEmailAddress;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function getPlainTextBody(): string
    {
        return $this->plainTextBody;
    }

    public function getHtmlTextBody(): string
    {
        return $this->htmlTextBody;
    }

    public function getAttachmentPaths(): array
    {
        return $this->attachmentPaths;
    }

    public function getAdditionalHeaders(): array
    {
        return $this->additionalHeaders;
    }

    public function hasHeader(string $name): bool
    {
        return isset($this->headers[$name]);
    }

    public function getCharset(): string
    {
        return $this->charset;
    }

    public function getRawBody(): string
    {
        $body = "--{$this->boundary}\r\n";
        $body .= "Content-Type: multipart/alternative; charset={$this->charset}\r\n";
        $body .= "MIME-Version: 1.0\r\n\r\n";

        $body .= "--{$this->boundary}\r\n";
        $body .= "Content-Type: text/plain; charset={$this->charset}\r\n";
        $body .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
        $body .= $this->plainTextBody . "\r\n\r\n";

        $body .= "--{$this->boundary}\r\n";
        $body .= "Content-Type: text/html; charset={$this->charset}\r\n";
        $body .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
        $body .= $this->htmlTextBody . "\r\n\r\n";

        $fileAttachmentNames = [];
        foreach ($this->attachmentPaths as $attachmentPath) {
            if (!file_exists($attachmentPath)) {
                throw new InvalidArgumentException("Attachment path $attachmentPath does not exist");
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

            $body .= "--{$this->boundary}\r\n";
            $body .= "Content-Type: $fileType; name=\"$fileName\"\r\n";
            $body .= "Content-Transfer-Encoding: base64\r\n";
            $body .= "Content-Disposition: attachment; filename=\"$fileName\"\r\n\r\n";

            //FIXME: If possible use a faster and less memory avid solution than file get contents
            $fileContent = file_get_contents($attachmentPath);
            $body .= chunk_split(base64_encode($fileContent)) . "\r\n";
        }

        $body .= "--{$this->boundary}--";

        return $body;
    }

    public function getRawHeaders(): string
    {
        $headers = '';
        foreach ($this->headers as $header) {
            $headers .= "{$header->getName()}: {$header->getValue()}\r\n";
        }

        return $headers;
    }

    public function getAttachmentsSize(): int
    {
        $fileSizes = [];

        foreach ($this->attachmentPaths as $attachmentPath) {
            $fileSizes[$attachmentPath] = filesize($attachmentPath);
        }

        return array_sum($fileSizes);
    }

    private function addHeader(SmtpHeader $header): self
    {
        if (isset($this->headers[$header->getName()])) {
            throw new InvalidArgumentException("Header {$header->getName()} already exists}");
        }

        $this->headers[$header->getName()] = $header;

        return $this;
    }
}
