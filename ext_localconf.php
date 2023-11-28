<?php

defined('TYPO3_MODE') or die();

( function( $_EXTKEY) {

    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
        $_EXTKEY,
        'Cart',
        [
            \W4Services\W4Payrexx\Controller\Order\PaymentController::class => 'success, failed, cancel, notify',
        ],
        // non-cacheable actions
        [
            \W4Services\W4Payrexx\Controller\Order\PaymentController::class => 'success, failed, cancel, notify',
        ]
    );

    $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects']['Extcode\\Cart\\Controller\\Cart\\CartController'] = [
        'className' => 'W4Services\\W4Payrexx\\Controller\\Cart\\CartController'
    ];

    $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects']['Extcode\\Cart\\Controller\\Cart\\ActionController'] = [
        'className' => 'W4Services\\W4Payrexx\\Controller\\Cart\\ActionController'
    ];

    $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects']['Extcode\\Cart\\Controller\\Cart\\PaymentController'] = [
        'className' => 'W4Services\\W4Payrexx\\Controller\\Cart\\PaymentController'
    ];

    $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects']['Extcode\\Cart\\Controller\\Cart\\ShippingController'] = [
        'className' => 'W4Services\\W4Payrexx\\Controller\\Cart\\ShippingController'
    ];

    $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects']['Extcode\\Cart\\Controller\\Cart\\CountryController'] = [
        'className' => 'W4Services\\W4Payrexx\\Controller\\Cart\\CountryController'
    ];

    $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects']['Extcode\\Cart\\Controller\\Cart\\CurrencyController'] = [
        'className' => 'W4Services\\W4Payrexx\\Controller\\Cart\\CurrencyController'
    ];

    /* Configure signal slots */
    $dispatcher = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\SignalSlot\Dispatcher::class);
    $dispatcher->connect(
        \Extcode\Cart\Utility\PaymentUtility::class,
        'handlePayment',
    \W4Services\W4Payrexx\Utility\PaymentUtility::class,
        'handlePayment'
    );

    /* Configure eID dispatcher */
    if (TYPO3_MODE === 'FE') {
        $TYPO3_CONF_VARS['FE']['eID_include']['payrexx-webhook'] = \W4Services\W4Payrexx\Utility\PayrexxWebhook::class . '::process';
    }

    $GLOBALS['TYPO3_CONF_VARS']['SYS']['locallangXMLOverride']['default']['EXT:cart/Resources/Private/Language/locallang.xlf'][] = 'EXT:' . $_EXTKEY . '/Resources/Private/Language/Overrides/cart/Resources/Private/Language/locallang.xlf';
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['locallangXMLOverride']['de']['EXT:cart/Resources/Private/Language/de.locallang.xlf'][] = 'EXT:' . $_EXTKEY . '/Resources/Private/Language/Overrides/cart/Resources/Private/Language/de.locallang.xlf';

})( 'w4_payrexx');
