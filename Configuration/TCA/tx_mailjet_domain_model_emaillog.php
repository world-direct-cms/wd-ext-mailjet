<?php

return [
    'ctrl' => [
        'title' => 'LLL:EXT:mailjet/Resources/Private/Language/locallang_db.xlf:tx_mailjet_domain_model_emaillog',
        'label' => 'sent_at',
        'label_alt' => 'subject',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'delete' => 'deleted',
        'rootLevel' => 1,
        'searchFields' => 'sender_address,subject,delivery_status,exception_message',
        'iconfile' => 'EXT:mailjet/Resources/Public/Icons/EmailLog.svg',
        'hideTable' => false,
        'adminOnly' => true,
        'default_sortby' => 'sent_at DESC',
    ],
    'types' => [
        '1' => ['showitem' => 'sent_at, sender_address, subject, mailjet_enabled, delivery_status, exception_message'],
    ],
    'columns' => [
        'sent_at' => [
            'exclude' => false,
            'label' => 'LLL:EXT:mailjet/Resources/Private/Language/locallang_db.xlf:tx_mailjet_domain_model_emaillog.sent_at',
            'config' => [
                'type' => 'datetime',
                'format' => 'datetime',
                'readOnly' => true,
            ],
        ],
        'mailjet_enabled' => [
            'exclude' => false,
            'label' => 'LLL:EXT:mailjet/Resources/Private/Language/locallang_db.xlf:tx_mailjet_domain_model_emaillog.mailjet_enabled',
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
        'sender_address' => [
            'exclude' => false,
            'label' => 'LLL:EXT:mailjet/Resources/Private/Language/locallang_db.xlf:tx_mailjet_domain_model_emaillog.sender_address',
            'config' => [
                'type' => 'input',
                'size' => 50,
                'max' => 255,
                'readOnly' => true,
            ],
        ],
        'subject' => [
            'exclude' => false,
            'label' => 'LLL:EXT:mailjet/Resources/Private/Language/locallang_db.xlf:tx_mailjet_domain_model_emaillog.subject',
            'config' => [
                'type' => 'input',
                'size' => 50,
                'max' => 998,
                'readOnly' => true,
            ],
        ],
        'delivery_status' => [
            'exclude' => false,
            'label' => 'LLL:EXT:mailjet/Resources/Private/Language/locallang_db.xlf:tx_mailjet_domain_model_emaillog.delivery_status',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'readOnly' => true,
                'items' => [
                    ['label' => 'LLL:EXT:mailjet/Resources/Private/Language/locallang_db.xlf:tx_mailjet_domain_model_emaillog.delivery_status.sent', 'value' => 'sent'],
                    ['label' => 'LLL:EXT:mailjet/Resources/Private/Language/locallang_db.xlf:tx_mailjet_domain_model_emaillog.delivery_status.failed', 'value' => 'failed'],
                    ['label' => 'LLL:EXT:mailjet/Resources/Private/Language/locallang_db.xlf:tx_mailjet_domain_model_emaillog.delivery_status.pending', 'value' => 'pending'],
                ],
            ],
        ],
        'exception_message' => [
            'exclude' => false,
            'label' => 'LLL:EXT:mailjet/Resources/Private/Language/locallang_db.xlf:tx_mailjet_domain_model_emaillog.exception_message',
            'config' => [
                'type' => 'text',
                'rows' => 5,
                'cols' => 50,
                'readOnly' => true,
            ],
        ],
    ],
];
