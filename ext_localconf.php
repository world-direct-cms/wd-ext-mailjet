<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;

defined('TYPO3') or die();

(function () {
    // Get extension configuration
    $extensionConfiguration = GeneralUtility::makeInstance(
        ExtensionConfiguration::class
    );

    try {
        $extConf = $extensionConfiguration->get('mailjet');

        // Configure TYPO3 mail settings if extension is configured
        if (!empty($extConf['smtpServer'])) {
            // Set transport to SMTP
            $GLOBALS['TYPO3_CONF_VARS']['MAIL']['transport'] = 'smtp';

            // Configure SMTP server (parse host and port if provided)
            $serverParts = explode(':', $extConf['smtpServer']);
            $GLOBALS['TYPO3_CONF_VARS']['MAIL']['transport_smtp_server'] = $extConf['smtpServer'];

            // Configure authentication
            if (!empty($extConf['smtpUsername'])) {
                $GLOBALS['TYPO3_CONF_VARS']['MAIL']['transport_smtp_username'] = $extConf['smtpUsername'];
            }

            if (!empty($extConf['smtpPassword'])) {
                $GLOBALS['TYPO3_CONF_VARS']['MAIL']['transport_smtp_password'] = $extConf['smtpPassword'];
            }

            // Set encryption based on port or configure default
            // Port 465 typically uses SSL, port 587 uses TLS
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
    } catch (\Exception $e) {
        // Extension configuration not yet set, skip mail configuration
    }
})();
