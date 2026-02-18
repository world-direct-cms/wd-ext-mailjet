<?php

declare(strict_types=1);

namespace WorldDirect\Mailjet\Domain\Model;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

/**
 * Model for tracking sent emails
 */
class SentEmail extends AbstractEntity
{
    protected int $sentAt = 0;
    protected bool $mailjetEnabled = false;
    protected string $subject = '';

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

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function setSubject(string $subject): void
    {
        $this->subject = $subject;
    }
}
