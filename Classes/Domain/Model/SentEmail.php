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
    protected string $callingClass = '';

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

    public function getCallingClass(): string
    {
        return $this->callingClass;
    }

    public function setCallingClass(string $callingClass): void
    {
        $this->callingClass = $callingClass;
    }
}
