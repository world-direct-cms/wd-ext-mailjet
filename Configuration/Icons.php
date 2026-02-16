<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;

return [
    'mailjet-extension' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:mailjet/Resources/Public/Icons/Extension.svg',
    ],
    'mailjet-sentemail' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:mailjet/Resources/Public/Icons/SentEmail.svg',
    ],
];
