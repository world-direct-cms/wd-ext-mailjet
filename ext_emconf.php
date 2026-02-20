<?php

/***************************************************************
 * Extension Manager/Repository config file for ext: "mailjet"
 ***************************************************************/

$EM_CONF[$_EXTKEY] = [
    'title' => 'Mailjet E-Mail Versand',
    'description' => 'Diese Erweiterung konfiguriert das System zur Verwendung mit Mailjet. Ausserdem wird eine Datenbank Tabelle erstellt, welche alle versendeten E-Mails speichert. Jedoch ohne Datenschutz relevante Informationen.',
    'category' => 'plugin',
    'author' => 'Klaus Hörmann-Engl',
    'author_email' => 'kho@world-direct.at',
    'state' => 'stable',
    'uploadfolder' => 0,
    'createDirs' => '',
    'clearCacheOnLoad' => 0,
    'version' => '1.0.2',
    'constraints' => [
        'depends' => [
            'typo3' => '11.0.0-14.9.99'
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
