<?php

namespace multidialogo\RawMailerSdk\Model;

use InvalidArgumentException;

class BaseMessage
{
    private string $uuid;

    /**
     * @var SmtpHeader[]
     */
    private array $headers;

    private string $plainText;

    private string $htmlText;

    private array $attachmentPaths;

    private string $boundary;

    /**
     * @param string $uuid
     * @param string $senderEmailAddress
     * @param string $recipientEmailAddress
     * @param string $subject
     * @param array $additionalHeaders
     * @param string $plainText
     * @param string $htmlText
     * @param array $attachmentPaths
     * @param string|null $replyToEmailAddress
     * @param string|null $boundary
     * @param string $mimeVersion
     */
    public function __construct(
        string  $uuid,
        string  $senderEmailAddress,
        string  $recipientEmailAddress,
        string  $subject,
        array   $additionalHeaders,
        string  $plainText,
        string  $htmlText,
        array   $attachmentPaths = [],
        ?string $replyToEmailAddress = null,
        ?string $boundary = null,
        string  $mimeVersion = '1.0'
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
        $this->plainText = $plainText;
        $this->htmlText = $htmlText;
        $this->attachmentPaths = $attachmentPaths;
        if (null === $boundary) {
            $this->boundary = md5(uniqid(rand(), true));
        } else {
            $this->boundary = $boundary;
        }

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

    public function getRawBody(): string
    {
        $body = "--{$this->boundary}\r\n";
        $body .= "Content-Type: multipart/alternative; charset=UTF-8\r\n";
        $body .= "MIME-Version: 1.0\r\n\r\n";

        $body .= "--{$this->boundary}\r\n";
        $body .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $body .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
        $body .= $this->plainText . "\r\n\r\n";

        $body .= "--{$this->boundary}\r\n";
        $body .= "Content-Type: text/html; charset=UTF-8\r\n";
        $body .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
        $body .= $this->htmlText . "\r\n\r\n";

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

    public function getSize(): int
    {
        return strlen("{$this->getRawHeaders()}{$this->getRawBody()}");
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
