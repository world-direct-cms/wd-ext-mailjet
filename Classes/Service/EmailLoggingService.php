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
    private const MAX_SUBJECT_LENGTH = 998;
    private const MAX_ADDRESS_LENGTH = 255;

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
            $subject = $this->extractFromMessageSources($message, 'getSubject');
            if ($subject !== null && $subject !== '') {
                return mb_substr((string)$subject, 0, self::MAX_SUBJECT_LENGTH);
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
            $from = $this->extractFromMessageSources($message, 'getFrom');
            if (!empty($from)) {
                $address = $this->extractFirstAddressFromArray($from);
                if ($address !== '') {
                    return mb_substr($address, 0, self::MAX_ADDRESS_LENGTH);
                }
            }
        } catch (\Exception $e) {
            // If extraction fails, return empty string
        }

        return '';
    }

    /**
     * Extract recipient addresses from a message object
     * Returns a comma-separated string of all recipient addresses
     *
     * @param mixed $message The message object
     * @return string Comma-separated list of recipient addresses
     */
    public function extractRecipients($message): string
    {
        try {
            $to = $this->extractFromMessageSources($message, 'getTo');
            if (!empty($to)) {
                $recipients = $this->extractAllAddressesFromArray($to);
                if (!empty($recipients)) {
                    // Return sorted comma-separated list for consistent matching
                    sort($recipients);
                    return implode(',', $recipients);
                }
            }
        } catch (\Exception $e) {
            // If extraction fails, return empty string
        }

        return '';
    }

    /**
     * Extract data from message by trying multiple message sources
     * Tries direct call, getOriginalMessage(), and getMessage()
     *
     * @param mixed $message The message object to extract from
     * @param string $method The method name to call (e.g., 'getSubject', 'getFrom')
     * @return mixed The extracted value or null if not found
     */
    private function extractFromMessageSources($message, string $method)
    {
        // Define message sources to try in order
        $messageSources = [
            $message,
            method_exists($message, 'getOriginalMessage') ? $message->getOriginalMessage() : null,
            method_exists($message, 'getMessage') ? $message->getMessage() : null,
        ];

        foreach ($messageSources as $source) {
            if ($source !== null && method_exists($source, $method)) {
                $result = $source->$method();
                if ($result !== null && $result !== '') {
                    return $result;
                }
            }
        }

        return null;
    }

    /**
     * Extract the first email address from an address array
     * Handles both Address objects and legacy array formats
     *
     * @param mixed $from The from field (usually an array)
     * @return string The extracted email address or empty string
     */
    private function extractFirstAddressFromArray($from): string
    {
        if (!is_array($from)) {
            return '';
        }

        // Try to get Address object (Symfony\Component\Mime\Address)
        $firstAddress = reset($from);
        if (is_object($firstAddress) && method_exists($firstAddress, 'getAddress')) {
            return $firstAddress->getAddress();
        }

        // Fallback: try array keys for backwards compatibility
        $addresses = array_keys($from);
        if (!empty($addresses[0])) {
            return (string)$addresses[0];
        }

        return '';
    }

    /**
     * Extract all email addresses from an address array
     * Handles both Address objects and legacy array formats
     *
     * @param mixed $addresses The addresses field (usually an array)
     * @return array Array of email addresses
     */
    private function extractAllAddressesFromArray($addresses): array
    {
        if (!is_array($addresses)) {
            return [];
        }

        $result = [];

        foreach ($addresses as $key => $address) {
            // Try to get Address object (Symfony\Component\Mime\Address)
            if (is_object($address) && method_exists($address, 'getAddress')) {
                $result[] = $address->getAddress();
            } elseif (is_string($key) && str_contains($key, '@')) {
                // Fallback: array key is the email address (legacy format)
                $result[] = $key;
            }
        }

        return $result;
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
