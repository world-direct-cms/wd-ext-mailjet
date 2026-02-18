<?php

declare(strict_types=1);

namespace WorldDirect\Mailjet\Domain\Repository;

use TYPO3\CMS\Extbase\Persistence\Repository;
use WorldDirect\Mailjet\Domain\Model\SentEmail;

/**
 * Repository for SentEmail records
 */
class SentEmailRepository extends Repository
{
    public function initializeObject(): void
    {
        // Allow storage at root level (PID 0)
        $querySettings = $this->createQuery()->getQuerySettings();
        $querySettings->setRespectStoragePage(false);
        $this->setDefaultQuerySettings($querySettings);
    }

    /**
     * Create a new sent email record
     * @deprecated Use createEmailAttemptRecord instead
     */
    public function createSentEmailRecord(
        bool $mailjetEnabled,
        string $subject = '',
        string $deliveryStatus = 'sent'
    ): void {
        $this->createEmailAttemptRecord($mailjetEnabled, $subject, $deliveryStatus, null, '');
    }

    /**
     * Create a new email attempt record with full details
     */
    public function createEmailAttemptRecord(
        bool $mailjetEnabled,
        string $subject = '',
        string $deliveryStatus = 'sent',
        ?string $exceptionMessage = null,
        string $senderAddress = ''
    ): void {
        $sentEmail = new SentEmail();
        $sentEmail->setPid(0); // Store at root level
        $sentEmail->setSentAt(time());
        $sentEmail->setMailjetEnabled($mailjetEnabled);
        $sentEmail->setSenderAddress($senderAddress);
        $sentEmail->setSubject($subject);
        $sentEmail->setDeliveryStatus($deliveryStatus);
        $sentEmail->setExceptionMessage($exceptionMessage);

        $this->add($sentEmail);
    }
}
