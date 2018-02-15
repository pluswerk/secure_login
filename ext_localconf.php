<?php
defined('TYPO3_MODE') || die('Access denied.');

// Add a login service to deny blocked users
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addService(
    'secure_login',
    'auth',
    \Pluswerk\SecureLogin\Service\AuthenticationServices\DenyAuthenticationService::class,
    [
        'title' => 'pluswerk: Security check - deny access for banned users',
        'description' => 'Disable the authentication if the user or the used IP is banned for some security reasons.',
        'subtype' => 'authUserFE,authUserBE',
        'available' => true,
        'priority' => 99,
        'quality' => 99,
        'os' => '',
        'exec' => '',
        'className' => \Pluswerk\SecureLogin\Service\AuthenticationServices\DenyAuthenticationService::class
    ]
);

// XClass existing services for subType "getUser" and "authUser" to log login failures
$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\TYPO3\CMS\Saltedpasswords\SaltedPasswordService::class] = [
    'className' => \Pluswerk\SecureLogin\Service\AuthenticationServices\SaltedPasswordService::class,
];
$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\TYPO3\CMS\Sv\AuthenticationService::class] = [
    'className' => \Pluswerk\SecureLogin\Service\AuthenticationServices\AuthenticationService::class,
];

// XClass the login provider to display blocking messages
$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\TYPO3\CMS\Backend\LoginProvider\UsernamePasswordLoginProvider::class] = [
    'className' => \Pluswerk\SecureLogin\LoginProvider\UsernamePasswordLoginProvider::class,
];

// Default configuration: overwrite this in you own localconf.php
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['secure_login'] = [
    'defaultBlockingConfiguration' => [
        'FE' => \Pluswerk\SecureLogin\Configuration\BlockingConfiguration::createConfig(),
        'BE' => \Pluswerk\SecureLogin\Configuration\BlockingConfiguration::createConfig(),
    ],
];
