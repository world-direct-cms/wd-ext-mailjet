<?php

declare(strict_types=1);

namespace WorldDirect\Mailjet\EventListener;

use TYPO3\CMS\Core\Mail\Event\AfterMailerSentMessageEvent;
use TYPO3\CMS\Core\Mail\Mailer;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;
use WorldDirect\Mailjet\Domain\Repository\EmailLogRepository;
use WorldDirect\Mailjet\Service\EmailLoggingService;

/**
 * Event listener that is triggered after an email has been sent
 * and logs it to the database
 */
final class EmailSentEventListener
{
    public function __construct(
        private readonly EmailLogRepository $emailLogRepository,
        private readonly PersistenceManagerInterface $persistenceManager,
        private readonly EmailLoggingService $emailLoggingService,
    ) {}

    public function __invoke(AfterMailerSentMessageEvent $event): void
    {
        $mailer = $event->getMailer();

        // Get the sent message if the mailer is the TYPO3 Mailer implementation
        if ($mailer instanceof Mailer) {
            $sentMessage = $mailer->getSentMessage();

            if ($sentMessage !== null) {
                // Extract subject from the email
                $subject = $this->emailLoggingService->extractSubject($sentMessage);

                // Extract sender address from the email
                $senderAddress = $this->emailLoggingService->extractSenderAddress($sentMessage);

                // Store email information in database with "sent" status
                $this->logSentEmail($subject, $senderAddress);

                // Mark the attempt as successful (remove from pending list)
                EmailAttemptEventListener::markAttemptSuccessful($subject);
            }
        }
    }

    /**
     * Log the sent email to the database
     */
    private function logSentEmail(string $subject, string $senderAddress = ''): void
    {
        try {
            // Check if Mailjet is configured and enabled
            $mailjetEnabled = $this->emailLoggingService->isMailjetEnabled();

            // Try Extbase persistence first
            try {
                $this->emailLogRepository->createEmailAttemptRecord($mailjetEnabled, $subject, 'sent', null, $senderAddress);
                $this->persistenceManager->persistAll();
            } catch (\Exception $extbaseException) {
                // Fallback to direct database insert if Extbase fails
                // This can happen when emails are sent from Install Tool or other contexts
                // where Extbase persistence is not available
                $this->emailLoggingService->logEmailDirectly($mailjetEnabled, $subject, 'sent', $senderAddress);
            }
        } catch (\Exception $e) {
            // Silently fail to avoid breaking email sending
        }
    }
}
