<?php

declare(strict_types=1);

namespace WorldDirect\Mailjet\EventListener;

use TYPO3\CMS\Core\Mail\Event\BeforeMailerSentMessageEvent;
use TYPO3\CMS\Core\Mail\Mailer;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;
use WorldDirect\Mailjet\Domain\Repository\EmailLogRepository;
use WorldDirect\Mailjet\Service\EmailLoggingService;
use WorldDirect\Mailjet\Transport\LoggingTransportDecorator;

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

    /**
     * Flag to track if the transport has been wrapped
     */
    private static bool $transportWrapped = false;

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
            // Wrap the transport with our logging decorator (only once)
            if (!self::$transportWrapped) {
                $this->wrapTransportWithDecorator($mailer);
                self::$transportWrapped = true;
            }

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
     * Wrap the mailer's transport with our LoggingTransportDecorator using reflection
     */
    private function wrapTransportWithDecorator(Mailer $mailer): void
    {
        try {
            $reflection = new \ReflectionClass($mailer);
            $transportProperty = $reflection->getProperty('transport');
            $transportProperty->setAccessible(true);

            $originalTransport = $transportProperty->getValue($mailer);

            // Only wrap if not already wrapped
            if (!($originalTransport instanceof LoggingTransportDecorator)) {
                $decoratedTransport = new LoggingTransportDecorator($originalTransport);
                $transportProperty->setValue($mailer, $decoratedTransport);
            }
        } catch (\Exception $e) {
            // If wrapping fails, continue without it (fallback to error_get_last)
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
            // First priority: Get exception directly from LoggingTransportDecorator
            $exceptionMessage = null;
            $lastException = LoggingTransportDecorator::getLastException();

            if ($lastException !== null) {
                // Get the full exception message including the exception class name
                $exceptionMessage = get_class($lastException) . ': ' . $lastException->getMessage();

                // If the message is empty or generic, try to get more details
                if (empty($lastException->getMessage()) || strlen($lastException->getMessage()) < 10) {
                    $exceptionMessage = get_class($lastException) . ' thrown';
                }
            }

            // Second priority: Check for fatal errors from error_get_last()
            if (empty($exceptionMessage)) {
                $error = error_get_last();
                if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_WARNING, E_USER_ERROR])) {
                    $exceptionMessage = sprintf(
                        '%s: %s in %s on line %d',
                        $this->getErrorTypeName($error['type']),
                        $error['message'],
                        basename($error['file']),
                        $error['line']
                    );
                }
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

            // Clear pending attempts and the captured exception
            self::$pendingAttempts = [];
            LoggingTransportDecorator::clear();
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
     * Convert error type constant to readable name
     */
    private function getErrorTypeName(int $type): string
    {
        $errorTypes = [
            E_ERROR => 'E_ERROR',
            E_WARNING => 'E_WARNING',
            E_PARSE => 'E_PARSE',
            E_NOTICE => 'E_NOTICE',
            E_CORE_ERROR => 'E_CORE_ERROR',
            E_CORE_WARNING => 'E_CORE_WARNING',
            E_COMPILE_ERROR => 'E_COMPILE_ERROR',
            E_COMPILE_WARNING => 'E_COMPILE_WARNING',
            E_USER_ERROR => 'E_USER_ERROR',
            E_USER_WARNING => 'E_USER_WARNING',
            E_USER_NOTICE => 'E_USER_NOTICE',
        ];

        return $errorTypes[$type] ?? 'UNKNOWN';
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
