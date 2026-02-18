<?php

declare(strict_types=1);

namespace WorldDirect\Mailjet\Service;

use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Service for email logging operations
 * Provides shared functionality for email event listeners
 */
class EmailLoggingService implements SingletonInterface
{
    public function __construct(
        private readonly ExtensionConfiguration $extensionConfiguration
    ) {}

    /**
     * Extract subject from a message object
     * Works with both sent messages and regular messages
     */
    public function extractSubject($message): string
    {
        try {
            // Try direct getSubject method (works with Email objects)
            if (method_exists($message, 'getSubject')) {
                $subject = $message->getSubject();
                if ($subject !== null && $subject !== '') {
                    // Truncate to 998 characters to fit database field
                    return mb_substr((string)$subject, 0, 998);
                }
            }

            // Try to get the original message (for SentMessage objects)
            if (method_exists($message, 'getOriginalMessage')) {
                $originalMessage = $message->getOriginalMessage();
                if ($originalMessage !== null && method_exists($originalMessage, 'getSubject')) {
                    $subject = $originalMessage->getSubject();
                    if ($subject !== null && $subject !== '') {
                        return mb_substr((string)$subject, 0, 998);
                    }
                }
            }

            // Alternative: Try to get via getMessage
            if (method_exists($message, 'getMessage')) {
                $innerMessage = $message->getMessage();
                if ($innerMessage !== null && method_exists($innerMessage, 'getSubject')) {
                    $subject = $innerMessage->getSubject();
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
     * Extract sender address from a message object
     * Works with both sent messages and regular messages
     */
    public function extractSenderAddress($message): string
    {
        try {
            // Try direct getFrom method (works with Email objects)
            if (method_exists($message, 'getFrom')) {
                $from = $message->getFrom();
                if (!empty($from)) {
                    // Handle Address objects (Symfony\Component\Mime\Address)
                    if (is_array($from)) {
                        $firstAddress = reset($from);
                        if (is_object($firstAddress) && method_exists($firstAddress, 'getAddress')) {
                            return mb_substr($firstAddress->getAddress(), 0, 255);
                        }
                        // Fallback: try array keys for backwards compatibility
                        $addresses = array_keys($from);
                        if (!empty($addresses[0])) {
                            return mb_substr((string)$addresses[0], 0, 255);
                        }
                    }
                }
            }

            // Try to get the original message (for SentMessage objects)
            if (method_exists($message, 'getOriginalMessage')) {
                $originalMessage = $message->getOriginalMessage();
                if ($originalMessage !== null && method_exists($originalMessage, 'getFrom')) {
                    $from = $originalMessage->getFrom();
                    if (!empty($from)) {
                        if (is_array($from)) {
                            $firstAddress = reset($from);
                            if (is_object($firstAddress) && method_exists($firstAddress, 'getAddress')) {
                                return mb_substr($firstAddress->getAddress(), 0, 255);
                            }
                            $addresses = array_keys($from);
                            if (!empty($addresses[0])) {
                                return mb_substr((string)$addresses[0], 0, 255);
                            }
                        }
                    }
                }
            }

            // Alternative: Try to get via getMessage
            if (method_exists($message, 'getMessage')) {
                $innerMessage = $message->getMessage();
                if ($innerMessage !== null && method_exists($innerMessage, 'getFrom')) {
                    $from = $innerMessage->getFrom();
                    if (!empty($from)) {
                        if (is_array($from)) {
                            $firstAddress = reset($from);
                            if (is_object($firstAddress) && method_exists($firstAddress, 'getAddress')) {
                                return mb_substr($firstAddress->getAddress(), 0, 255);
                            }
                            $addresses = array_keys($from);
                            if (!empty($addresses[0])) {
                                return mb_substr((string)$addresses[0], 0, 255);
                            }
                        }
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
    public function isMailjetEnabled(): bool
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
     * Direct database insert for email logging
     * Used as fallback when Extbase persistence is unavailable
     */
    public function logEmailDirectly(
        bool $mailjetEnabled,
        string $subject,
        string $deliveryStatus,
        string $senderAddress = '',
        ?string $exceptionMessage = null
    ): void {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tx_mailjet_domain_model_emaillog');

        $timestamp = time();

        $connection->insert(
            'tx_mailjet_domain_model_emaillog',
            [
                'pid' => 0,
                'tstamp' => $timestamp,
                'crdate' => $timestamp,
                'sent_at' => $timestamp,
                'mailjet_enabled' => $mailjetEnabled ? 1 : 0,
                'sender_address' => $senderAddress,
                'subject' => $subject,
                'delivery_status' => $deliveryStatus,
                'exception_message' => $exceptionMessage,
            ]
        );
    }
}
