<?php

declare(strict_types=1);

namespace WorldDirect\Mailjet\EventListener;

use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Mail\Event\BeforeMailerSentMessageEvent;
use TYPO3\CMS\Core\Mail\Mailer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;
use WorldDirect\Mailjet\Domain\Repository\SentEmailRepository;

/**
 * Event listener that is triggered before an email is sent
 * and tracks the attempt in the database
 */
final class EmailAttemptEventListener
{
    /**
     * Temporary storage for pending email attempts
     * @var array<string, array<string, mixed>>
     */
    private static array $pendingAttempts = [];

    /**
     * Flag to ensure shutdown handler is registered only once
     */
    private static bool $shutdownRegistered = false;

    public function __construct(
        private readonly SentEmailRepository $sentEmailRepository,
        private readonly PersistenceManagerInterface $persistenceManager,
        private readonly ExtensionConfiguration $extensionConfiguration,
    ) {}

    public function __invoke(BeforeMailerSentMessageEvent $event): void
    {
        $mailer = $event->getMailer();

        // Get the message if the mailer is the TYPO3 Mailer implementation
        if ($mailer instanceof Mailer) {
            $message = $event->getMessage();

            if ($message !== null) {
                // Extract subject from the email
                $subject = $this->extractSubject($message);

                // Generate a unique identifier for this attempt
                $attemptId = $this->generateAttemptId($message, $subject);

                // Store attempt information temporarily
                self::$pendingAttempts[$attemptId] = [
                    'subject' => $subject,
                    'timestamp' => time(),
                    'mailjet_enabled' => $this->isMailjetEnabled(),
                ];

                // Register shutdown handler to catch failed attempts (only once)
                if (!self::$shutdownRegistered) {
                    register_shutdown_function([$this, 'handlePendingAttempts']);
                    self::$shutdownRegistered = true;
                }
            }
        }
    }

    /**
     * Generate a unique identifier for this email attempt
     */
    private function generateAttemptId($message, string $subject): string
    {
        // Use a combination of timestamp, subject, and object hash to create unique ID
        return md5(
            time() .
                $subject .
                spl_object_hash($message) .
                microtime(true)
        );
    }

    /**
     * Extract subject from the message
     */
    private function extractSubject($message): string
    {
        try {
            if (method_exists($message, 'getSubject')) {
                $subject = $message->getSubject();
                if ($subject !== null && $subject !== '') {
                    // Truncate to 998 characters to fit database field
                    return mb_substr((string)$subject, 0, 998);
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

    /**
     * Shutdown handler that logs any remaining pending attempts as failed
     * This is called at the end of script execution
     */
    public function handlePendingAttempts(): void
    {
        if (empty(self::$pendingAttempts)) {
            return;
        }

        try {
            // Check for fatal errors that might have caused the failure
            $error = error_get_last();
            $exceptionMessage = null;

            if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
                $exceptionMessage = sprintf(
                    'Fatal error: %s in %s on line %d',
                    $error['message'],
                    $error['file'],
                    $error['line']
                );
            }

            // Log all remaining pending attempts as failed
            foreach (self::$pendingAttempts as $attemptId => $attempt) {
                $this->logFailedEmail(
                    $attempt['mailjet_enabled'],
                    $attempt['subject'],
                    $exceptionMessage ?? 'Email sending did not complete successfully'
                );
            }

            // Clear pending attempts
            self::$pendingAttempts = [];
        } catch (\Exception $e) {
            // Silently fail to avoid breaking on shutdown
        }
    }

    /**
     * Log a failed email attempt to the database
     */
    private function logFailedEmail(bool $mailjetEnabled, string $subject, string $exceptionMessage): void
    {
        try {
            // Try Extbase persistence first
            try {
                $this->sentEmailRepository->createEmailAttemptRecord(
                    $mailjetEnabled,
                    $subject,
                    'failed',
                    $exceptionMessage
                );
                $this->persistenceManager->persistAll();
            } catch (\Exception $extbaseException) {
                // Fallback to direct database insert if Extbase fails
                $this->logEmailDirectly($mailjetEnabled, $subject, 'failed', $exceptionMessage);
            }
        } catch (\Exception $e) {
            // Silently fail to avoid breaking email sending
        }
    }

    /**
     * Direct database insert as fallback when Extbase persistence is unavailable
     */
    private function logEmailDirectly(
        bool $mailjetEnabled,
        string $subject,
        string $deliveryStatus,
        ?string $exceptionMessage
    ): void {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tx_mailjet_domain_model_sentemail');

        $timestamp = time();

        $connection->insert(
            'tx_mailjet_domain_model_sentemail',
            [
                'pid' => 0,
                'tstamp' => $timestamp,
                'crdate' => $timestamp,
                'sent_at' => $timestamp,
                'mailjet_enabled' => $mailjetEnabled ? 1 : 0,
                'subject' => $subject,
                'delivery_status' => $deliveryStatus,
                'exception_message' => $exceptionMessage,
            ]
        );
    }

    /**
     * Mark an attempt as successful (called by EmailSentEventListener)
     */
    public static function markAttemptSuccessful(string $subject): void
    {
        // Find and remove matching attempts from pending list
        foreach (self::$pendingAttempts as $attemptId => $attempt) {
            if (
                $attempt['subject'] === $subject &&
                abs($attempt['timestamp'] - time()) <= 5
            ) { // Match within 5 seconds
                unset(self::$pendingAttempts[$attemptId]);
                break;
            }
        }
    }
}
