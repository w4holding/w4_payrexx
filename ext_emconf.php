<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'W4 Payrexx',
    'description' => 'W4 Payrexx payment gateway for TYPO3.',
    'version' => '1.0.1',
    'category' => 'fe',
    'constraints' => [
        'depends' => [
            'typo3' => '11.5.0-11.5.99',
            'cart' => '8.2.0-8.2.99'
        ],
    ],
    'state' => 'stable',
    'clearCacheOnLoad' => true,
    'author' => 'W4 Services GmbH',
    'author_email' => 'info@w-4.ch',
    'author_company' => 'W4 Services GmbH',
    'autoload' => [
        'psr-4' => [
            'W4Services\\W4Payrexx\\' => 'Classes'
        ],
    ],
];
