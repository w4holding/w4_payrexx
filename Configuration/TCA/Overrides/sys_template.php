<?php

defined('TYPO3_MODE') or die();

call_user_func(function () {
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile(
        'w4_payrexx',
        'Configuration/TypoScript',
        'W4 Payrexx gateway'
    );
});
