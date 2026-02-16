<?php

return [
    'ctrl' => [
        'title' => 'LLL:EXT:mailjet/Resources/Private/Language/locallang_db.xlf:tx_mailjet_domain_model_sentemail',
        'label' => 'sent_at',
        'label_alt' => 'calling_class',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'delete' => 'deleted',
        'rootLevel' => 1,
        'searchFields' => 'calling_class',
        'iconfile' => 'EXT:mailjet/Resources/Public/Icons/SentEmail.svg',
        'hideTable' => false,
        'adminOnly' => true,
        'default_sortby' => 'sent_at DESC',
    ],
    'types' => [
        '1' => ['showitem' => 'sent_at, mailjet_enabled, calling_class'],
    ],
    'columns' => [
        'sent_at' => [
            'exclude' => false,
            'label' => 'LLL:EXT:mailjet/Resources/Private/Language/locallang_db.xlf:tx_mailjet_domain_model_sentemail.sent_at',
            'config' => [
                'type' => 'datetime',
                'format' => 'datetime',
                'readOnly' => true,
            ],
        ],
        'mailjet_enabled' => [
            'exclude' => false,
            'label' => 'LLL:EXT:mailjet/Resources/Private/Language/locallang_db.xlf:tx_mailjet_domain_model_sentemail.mailjet_enabled',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
                'readOnly' => true,
                'items' => [
                    [
                        'label' => '',
                        'invertStateDisplay' => false,
                    ],
                ],
            ],
        ],
        'calling_class' => [
            'exclude' => false,
            'label' => 'LLL:EXT:mailjet/Resources/Private/Language/locallang_db.xlf:tx_mailjet_domain_model_sentemail.calling_class',
            'config' => [
                'type' => 'input',
                'size' => 50,
                'readOnly' => true,
            ],
        ],
    ],
];
