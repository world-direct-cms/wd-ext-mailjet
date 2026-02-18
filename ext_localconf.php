<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\GeneralUtility;
use WorldDirect\Mailjet\Service\MailConfigurationService;

defined('TYPO3') or die();

// Configure mail settings via service
GeneralUtility::makeInstance(MailConfigurationService::class)->configure();
