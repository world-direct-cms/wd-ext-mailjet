<?php

declare(strict_types=1);

namespace WorldDirect\Mailjet\Domain\Repository;

use TYPO3\CMS\Extbase\Persistence\Repository;
use WorldDirect\Mailjet\Domain\Model\EmailLog;

/**
 * Repository for EmailLog records
 */
class EmailLogRepository extends Repository
{
    public function initializeObject(): void
    {
        // Allow storage at root level (PID 0)
        $querySettings = $this->createQuery()->getQuerySettings();
        $querySettings->setRespectStoragePage(false);
        $this->setDefaultQuerySettings($querySettings);
    }

    /**
     * Create a new email log record
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
        $emailLog = new EmailLog();
        $emailLog->setPid(0); // Store at root level
        $emailLog->setSentAt(time());
        $emailLog->setMailjetEnabled($mailjetEnabled);
        $emailLog->setSenderAddress($senderAddress);
        $emailLog->setSubject($subject);
        $emailLog->setDeliveryStatus($deliveryStatus);
        $emailLog->setExceptionMessage($exceptionMessage);

        $this->add($emailLog);
    }
}
