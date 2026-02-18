<?php

declare(strict_types=1);

namespace WorldDirect\Mailjet\EventListener;

use TYPO3\CMS\Core\Mail\Event\BeforeMailerSentMessageEvent;
use TYPO3\CMS\Core\Mail\Mailer;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;
use WorldDirect\Mailjet\Domain\Repository\EmailLogRepository;
use WorldDirect\Mailjet\Service\EmailLoggingService;

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
        private readonly EmailLogRepository $emailLogRepository,
        private readonly PersistenceManagerInterface $persistenceManager,
        private readonly EmailLoggingService $emailLoggingService,
    ) {}

    public function __invoke(BeforeMailerSentMessageEvent $event): void
    {
        $mailer = $event->getMailer();

        // Get the message if the mailer is the TYPO3 Mailer implementation
        if ($mailer instanceof Mailer) {
            $message = $event->getMessage();

            if ($message !== null) {
                // Extract subject from the email
                $subject = $this->emailLoggingService->extractSubject($message);

                // Extract sender address from the email
                $senderAddress = $this->emailLoggingService->extractSenderAddress($message);

                // Generate a unique identifier for this attempt
                $attemptId = $this->generateAttemptId($message, $subject);

                // Store attempt information temporarily
                self::$pendingAttempts[$attemptId] = [
                    'subject' => $subject,
                    'sender_address' => $senderAddress,
                    'timestamp' => time(),
                    'mailjet_enabled' => $this->emailLoggingService->isMailjetEnabled(),
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
                    $exceptionMessage ?? 'Email sending did not complete successfully',
                    $attempt['sender_address'] ?? ''
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
    private function logFailedEmail(bool $mailjetEnabled, string $subject, string $exceptionMessage, string $senderAddress = ''): void
    {
        try {
            // Try Extbase persistence first
            try {
                $this->emailLogRepository->createEmailAttemptRecord(
                    $mailjetEnabled,
                    $subject,
                    'failed',
                    $exceptionMessage,
                    $senderAddress
                );
                $this->persistenceManager->persistAll();
            } catch (\Exception $extbaseException) {
                // Fallback to direct database insert if Extbase fails
                $this->emailLoggingService->logEmailDirectly($mailjetEnabled, $subject, 'failed', $senderAddress, $exceptionMessage);
            }
        } catch (\Exception $e) {
            // Silently fail to avoid breaking email sending
        }
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
