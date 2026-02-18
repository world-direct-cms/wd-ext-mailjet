<?php

declare(strict_types=1);

namespace WorldDirect\Mailjet\Service;

use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\SingletonInterface;

/**
 * Service for configuring TYPO3 mail settings based on Mailjet extension configuration
 */
class MailConfigurationService implements SingletonInterface
{
    public function __construct(
        private readonly ExtensionConfiguration $extensionConfiguration
    ) {}

    /**
     * Configure TYPO3 mail settings based on extension configuration
     */
    public function configure(): void
    {
        try {
            $extConf = $this->extensionConfiguration->get('mailjet');

            // Check if Mailjet is enabled
            if (empty($extConf['enabled'])) {
                return;
            }

            // Only configure if SMTP server is set
            if (empty($extConf['smtpServer'])) {
                return;
            }

            // Set transport to SMTP
            $GLOBALS['TYPO3_CONF_VARS']['MAIL']['transport'] = 'smtp';

            // Configure SMTP server
            $GLOBALS['TYPO3_CONF_VARS']['MAIL']['transport_smtp_server'] = $extConf['smtpServer'];

            // Configure authentication
            $this->configureAuthentication($extConf);

            // Configure encryption
            $this->configureEncryption($extConf['smtpServer']);
        } catch (\Exception $e) {
            // Extension configuration not yet set, skip mail configuration
        }
    }

    /**
     * Configure SMTP authentication credentials
     */
    private function configureAuthentication(array $extConf): void
    {
        if (!empty($extConf['smtpUsername'])) {
            $GLOBALS['TYPO3_CONF_VARS']['MAIL']['transport_smtp_username'] = $extConf['smtpUsername'];
        }

        if (!empty($extConf['smtpPassword'])) {
            $GLOBALS['TYPO3_CONF_VARS']['MAIL']['transport_smtp_password'] = $extConf['smtpPassword'];
        }
    }

    /**
     * Configure SMTP encryption based on port
     * Port 465 typically uses SSL, port 587 uses TLS
     */
    private function configureEncryption(string $smtpServer): void
    {
        $serverParts = explode(':', $smtpServer);

        if (isset($serverParts[1])) {
            $port = (int)$serverParts[1];
            if ($port === 465) {
                $GLOBALS['TYPO3_CONF_VARS']['MAIL']['transport_smtp_encrypt'] = 'ssl';
            } elseif ($port === 587) {
                $GLOBALS['TYPO3_CONF_VARS']['MAIL']['transport_smtp_encrypt'] = 'tls';
            }
        } else {
            // Default to TLS if no port specified
            $GLOBALS['TYPO3_CONF_VARS']['MAIL']['transport_smtp_encrypt'] = 'tls';
        }
    }
}
