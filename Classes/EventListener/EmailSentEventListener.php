<?php

declare(strict_types=1);

namespace WorldDirect\Mailjet\EventListener;

use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Mail\Event\AfterMailerSentMessageEvent;
use TYPO3\CMS\Core\Mail\Mailer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;
use WorldDirect\Mailjet\Domain\Repository\SentEmailRepository;
use WorldDirect\Mailjet\Utility\CallerUtility;

/**
 * Event listener that is triggered after an email has been sent
 * and logs it to the database
 */
final class EmailSentEventListener
{
    public function __construct(
        private readonly SentEmailRepository $sentEmailRepository,
        private readonly PersistenceManagerInterface $persistenceManager,
        private readonly ExtensionConfiguration $extensionConfiguration,
    ) {}

    public function __invoke(AfterMailerSentMessageEvent $event): void
    {
        $mailer = $event->getMailer();

        // Get the sent message if the mailer is the TYPO3 Mailer implementation
        if ($mailer instanceof Mailer) {
            $sentMessage = $mailer->getSentMessage();

            if ($sentMessage !== null) {
                // Store email information in database
                $this->logSentEmail();
            }
        }
    }

    /**
     * Log the sent email to the database
     */
    private function logSentEmail(): void
    {
        try {
            // Check if Mailjet is configured and enabled
            $mailjetEnabled = $this->isMailjetEnabled();

            // Get the calling class
            $callingClass = CallerUtility::getCallingClass();

            // Try Extbase persistence first
            try {
                $this->sentEmailRepository->createSentEmailRecord($mailjetEnabled, $callingClass);
                $this->persistenceManager->persistAll();
            } catch (\Exception $extbaseException) {
                // Fallback to direct database insert if Extbase fails
                // This can happen when emails are sent from Install Tool or other contexts
                // where Extbase persistence is not available
                $this->logEmailDirectly($mailjetEnabled, $callingClass);
            }
        } catch (\Exception $e) {
            // Silently fail to avoid breaking email sending
        }
    }

    /**
     * Direct database insert as fallback when Extbase persistence is unavailable
     */
    private function logEmailDirectly(bool $mailjetEnabled, string $callingClass): void
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tx_mailjet_domain_model_sent_email');

        $timestamp = time();

        $connection->insert(
            'tx_mailjet_domain_model_sent_email',
            [
                'pid' => 0,
                'tstamp' => $timestamp,
                'crdate' => $timestamp,
                'sent_at' => $timestamp,
                'mailjet_enabled' => $mailjetEnabled ? 1 : 0,
                'calling_class' => $callingClass,
            ]
        );
    }

    /**
     * Check if Mailjet configuration is enabled
     */
    private function isMailjetEnabled(): bool
    {
        try {
            $extConf = $this->extensionConfiguration->get('mailjet');

            // Check if all required Mailjet settings are configured
            return !empty($extConf['smtpServer'])
                && !empty($extConf['smtpUsername'])
                && !empty($extConf['smtpPassword']);
        } catch (\Exception $e) {
            return false;
        }
    }
}
