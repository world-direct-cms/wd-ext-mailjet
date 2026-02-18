<?php

declare(strict_types=1);

namespace WorldDirect\Mailjet\Domain\Model;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

/**
 * Model for tracking email logs
 */
class EmailLog extends AbstractEntity
{
    protected int $sentAt = 0;
    protected bool $mailjetEnabled = false;
    protected string $senderAddress = '';
    protected string $subject = '';
    protected string $deliveryStatus = '';
    protected ?string $exceptionMessage = null;

    public function getSentAt(): int
    {
        return $this->sentAt;
    }

    public function setSentAt(int $sentAt): void
    {
        $this->sentAt = $sentAt;
    }

    public function isMailjetEnabled(): bool
    {
        return $this->mailjetEnabled;
    }

    public function setMailjetEnabled(bool $mailjetEnabled): void
    {
        $this->mailjetEnabled = $mailjetEnabled;
    }

    public function getSenderAddress(): string
    {
        return $this->senderAddress;
    }

    public function setSenderAddress(string $senderAddress): void
    {
        $this->senderAddress = $senderAddress;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function setSubject(string $subject): void
    {
        $this->subject = $subject;
    }

    public function getDeliveryStatus(): string
    {
        return $this->deliveryStatus;
    }

    public function setDeliveryStatus(string $deliveryStatus): void
    {
        $this->deliveryStatus = $deliveryStatus;
    }

    public function getExceptionMessage(): ?string
    {
        return $this->exceptionMessage;
    }

    public function setExceptionMessage(?string $exceptionMessage): void
    {
        $this->exceptionMessage = $exceptionMessage;
    }
}
