[![Packagist Release](https://img.shields.io/packagist/v/pluswerk/secure-login.svg?style=flat-square)](https://packagist.org/packages/pluswerk/secure-login)
[![Travis](https://img.shields.io/travis/pluswerk/secure_login.svg?style=flat-square)](https://travis-ci.org/pluswerk/secure_login)
[![GitHub License](https://img.shields.io/github/license/pluswerk/secure_login.svg?style=flat-square)](https://github.com/pluswerk/secure_login/blob/master/LICENSE.txt)
[![Code Climate](https://img.shields.io/codeclimate/github/pluswerk/secure_login.svg?style=flat-square)](https://codeclimate.com/github/pluswerk/secure_login)

#  Auth Security validation
This extension checks frontend and backend logins for brute-force attacks. 

#### Advantages
* extendable 
* small
* security improvement
* just install and use preset configuration

#### Identification of brute-force attacks
A brute-force attack is identified in accordance with the following rules:
1. An IP tries out lots of different users
2. An user tries out lots of different passwords

Is a brute-force attack identified, the attacking IP (in the first case) or user (in the second case) will be blocked 
over a specific period.


## Installation
Install the TYPO3 extension via composer (recommended) or install the extension via TER (not recommended anymore).

> Composer installation:
>
> ```bash
> composer require pluswerk/secure-login
> ```


## Default configuration
If no settings are made, the extension blocks users or IPs for two hours if they have more than 5 failed attempts 
in one hour.


## Configuration (optional)

```php
// Default configuration: overwrite this in you own localconf.php
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['secure_login'] = [
    'defaultBlockingConfiguration' => [
        'FE' => \Pluswerk\SecureLogin\Configuration\BlockingConfiguration::createConfig(),
        'BE' => \Pluswerk\SecureLogin\Configuration\BlockingConfiguration::createConfig(),
    ],
];
```

#### Add the following configurations:

* maxFailedAttempts: Max amount of failed logins over specified time period 'timeRangeInSeconds'.
* timeRangeInSeconds: Time period (in seconds) over which 'maxFailedAttempts' are counted.
* blockingPeriodInSeconds: Time period (in seconds) over which the user or IP are blocked.

> ###### Example:
> one user gets blocked for 'blockingPeriodInSeconds' seconds if he tries out 'maxFailedAttempts' wrong passwords 
> in the time period of 'timeRangeInSeconds' seconds.


## Extend Extension

#### Display security messages
The configured blocking is always active. To show security messages in the frontend add the following lines to your template:

```html 
<!-- use namespace -->
<div xmlns:sl="http://typo3.org/ns/Pluswerk/SecureLogin/ViewHelpers"> 
  <!-- content goes here -->
  
  <f:if condition="{sl:securityMessage()}">
    <!-- fluid placeholder for security messages -->
    <p><sl:securityMessage/></p>
  </f:if>
  
  <!-- content goes here -->
</div> 
```

#### Log fail attempts
This sample logs failed logins:

```php 
$formInDatabase = $this->formRepository->findBySerialNumber($form->getSerialNumber()); 
if (count($formInDatabase) > 0) { 
  /** @var \AUS\AusAuthSecurity\Configuration\BlockingConfiguration $blockingConfiguration */ 
  $blockingConfiguration = \Pluswerk\SecureLogin\Configuration\BlockingConfiguration::createConfig();
  
  /** @var AuthSecurityService $authSecurityService */
  $authSecurityService = GeneralUtility::makeInstance(AuthSecurityService::class);
  $authSecurityService->logUserPasswordAuthenticationFailed($username, $password);
} 
```
