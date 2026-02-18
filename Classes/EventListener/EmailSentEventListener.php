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
                // Extract subject from the email
                $subject = $this->extractSubject($sentMessage);

                // Store email information in database
                $this->logSentEmail($subject);
            }
        }
    }

    /**
     * Log the sent email to the database
     */
    private function logSentEmail(string $subject): void
    {
        try {
            // Check if Mailjet is configured and enabled
            $mailjetEnabled = $this->isMailjetEnabled();

            // Try Extbase persistence first
            try {
                $this->sentEmailRepository->createSentEmailRecord($mailjetEnabled, $subject);
                $this->persistenceManager->persistAll();
            } catch (\Exception $extbaseException) {
                // Fallback to direct database insert if Extbase fails
                // This can happen when emails are sent from Install Tool or other contexts
                // where Extbase persistence is not available
                $this->logEmailDirectly($mailjetEnabled, $subject);
            }
        } catch (\Exception $e) {
            // Silently fail to avoid breaking email sending
        }
    }

    /**
     * Direct database insert as fallback when Extbase persistence is unavailable
     */
    private function logEmailDirectly(bool $mailjetEnabled, string $subject): void
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
                'subject' => $subject,
            ]
        );
    }

    /**
     * Extract subject from the sent message
     */
    private function extractSubject($sentMessage): string
    {
        try {
            // Try to get the original message (Symfony Email object)
            $originalMessage = $sentMessage->getOriginalMessage();
            if ($originalMessage !== null && method_exists($originalMessage, 'getSubject')) {
                $subject = $originalMessage->getSubject();
                if ($subject !== null && $subject !== '') {
                    // Truncate to 998 characters to fit database field
                    return mb_substr((string)$subject, 0, 998);
                }
            }

            // Alternative: Try to get headers directly
            if (method_exists($sentMessage, 'getMessage')) {
                $message = $sentMessage->getMessage();
                if ($message !== null && method_exists($message, 'getSubject')) {
                    $subject = $message->getSubject();
                    if ($subject !== null && $subject !== '') {
                        return mb_substr((string)$subject, 0, 998);
                    }
                }
            }
        } catch (\Exception $e) {
            // If extraction fails, return empty string
        }

        return '';
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
