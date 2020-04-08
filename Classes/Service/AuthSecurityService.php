<?php

/***
 *
 * This file is part of an "+Pluswerk AG" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * (c) 2018 Markus Hölzle <markus.hoelzle@pluswerk.ag>, +Pluswerk AG
 *
 ***/

namespace Pluswerk\SecureLogin\Service;

use Pluswerk\SecureLogin\Configuration\BlockingConfiguration;
use Pluswerk\SecureLogin\Persistence\DatabaseHandler;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * Class AuthSecurityService
 *
 * @author Markus Hölzle <markus.hoelzle@pluswerk.ag>
 * @copyright 2018 +Pluswerk AG
 * @license GPL, version 2
 * @package Pluswerk\SecureLogin\Service
 */
class AuthSecurityService implements SingletonInterface
{
    const TYPE_USER = 'user';
    const TYPE_IP = 'ip';

    /**
     * @var DatabaseHandler
     */
    protected $databaseHandler = null;

    /**
     * AuthSecurityService constructor.
     */
    public function __construct()
    {
        $this->databaseHandler = GeneralUtility::makeInstance(DatabaseHandler::class);
    }

    /**
     * @param string $username
     * @param string $password
     * @param string $authKey
     * @param string[] $types
     * @param int $timestamp
     * @param bool $createBlockade
     * @param BlockingConfiguration $configuration
     * @return void
     * @throws \Exception
     */
    public function logUserPasswordAuthenticationFailed(
        $username,
        $password,
        $authKey = '',
        array $types = null,
        $timestamp = null,
        $createBlockade = true,
        BlockingConfiguration $configuration = null
    ) {
        if ($types === null) {
            $types = [static::TYPE_USER, static::TYPE_IP];
        }
        if ($configuration === null) {
            $configuration = $this->getDefaultBlockingConfiguration();
        }

        foreach ($types as $type) {
            if ($type === static::TYPE_IP) {
                $authIdentifier = $this->getHashedRequestIp();
                $hashedFailureValue = $username;
            } elseif ($type === static::TYPE_USER) {
                $authIdentifier = $username;
                $hashedFailureValue = $this->getHashedFailureValue($password);
            } else {
                throw new \Exception('Unknown log type "' . $type . '"!');
            }
            $this->databaseHandler->logFailedAttempt($authIdentifier, $hashedFailureValue, $type, $authKey, $timestamp);

            if ($createBlockade) {
                $this->addBlockadeIfRequired($authIdentifier, $authKey, $type, $configuration);
            }
        }
    }

    /**
     * @param string $username
     * @param string $authKey
     * @param int $timestamp
     * @param bool $createBlockade
     * @param BlockingConfiguration $configuration
     * @return void
     * @throws \Exception
     */
    public function logUserAuthenticationFailed(
        $username,
        $authKey = '',
        $timestamp = null,
        $createBlockade = true,
        BlockingConfiguration $configuration = null
    ) {
        if ($configuration === null) {
            $configuration = $this->getDefaultBlockingConfiguration();
        }
        $ip = $this->getHashedRequestIp();
        $this->databaseHandler->logFailedAttempt($ip, $username, static::TYPE_IP, $authKey, $timestamp);
        if ($createBlockade) {
            $this->addBlockadeIfRequired($ip, $authKey, static::TYPE_IP, $configuration);
        }
    }

    /**
     * @param string $username
     * @param string $authKey
     * @param array $types
     * @param int $timestamp
     * @param bool $setPublicBlockade
     * @return array
     * @throws \Exception
     */
    public function getBlockade($username, $authKey = '', array $types = null, $timestamp = null, $setPublicBlockade = true)
    {
        $blockade = null;
        if ($types === null) {
            $types = [static::TYPE_USER, static::TYPE_IP];
        }
        foreach ($types as $type) {
            if ($type === static::TYPE_IP) {
                $authIdentifier = $this->getHashedRequestIp();
            } elseif ($type === static::TYPE_USER) {
                $authIdentifier = $username;
            } else {
                throw new \Exception('Unknown log type "' . $type . '"!');
            }
            $blockadeResult = $this->databaseHandler->getBlockade($type, $authKey, $authIdentifier, $timestamp);
            if (is_array($blockadeResult) && !empty($blockadeResult)) {
                $blockade = $blockadeResult;
                break;
            }
        }
        
        if ($setPublicBlockade && $blockade !== null) {
            $this->setPublicBlockade($blockade);
        }
        return $blockade;
    }

    /**
     * @return string
     */
    public function getPublicErrorMessage()
    {
        $message = '';
        if (
            isset($GLOBALS['TYPO3_CONF_VARS ']['secure_login']['currentBlockade'])
            && is_array($GLOBALS['TYPO3_CONF_VARS ']['secure_login']['currentBlockade'])
        ) {
            $blockade = &$GLOBALS['TYPO3_CONF_VARS ']['secure_login']['currentBlockade'];

            // We try to find a localized message
            $message = LocalizationUtility::translate(
                'banned.' . $blockade['type'],
                'SecureLogin',
                [strftime('%c', $blockade['expired'])]
            );

            // If not beautiful message was found, we use a default message
            if (empty($message)) {
                $message = 'Your account has been banned until ' .
                    strftime('%c', $blockade['expired']) .
                    ' for some security reasons.';
            }
        }
        return $message;
    }

    /**
     * @param array $blockade
     * @return void
     */
    public function setPublicBlockade(array $blockade)
    {
        if (!isset($GLOBALS['TYPO3_CONF_VARS ']['secure_login'])) {
            $GLOBALS['TYPO3_CONF_VARS ']['secure_login'] = [];
        }
        $GLOBALS['TYPO3_CONF_VARS ']['secure_login']['currentBlockade'] = $blockade;
    }

    /**
     * @param string $authIdentifier
     * @param string $authKey
     * @param string $type
     * @param BlockingConfiguration $configuration
     * @return void
     */
    protected function addBlockadeIfRequired($authIdentifier, $authKey, $type, BlockingConfiguration $configuration)
    {
        if ($this->isBlockadeRequired($authIdentifier, $authKey, $type, $configuration)) {
            $reason = $this->getReasonText($authIdentifier, $type, $configuration);
            $this->databaseHandler->addBlockade(
                $type,
                $authKey,
                $authIdentifier,
                $reason,
                $configuration->getBlockingPeriodInSeconds()
            );
        }
    }

    /**
     * @param string $authIdentifier
     * @param string $authKey
     * @param string $type
     * @param BlockingConfiguration $configuration
     * @return bool
     */
    protected function isBlockadeRequired($authIdentifier, $authKey, $type, BlockingConfiguration $configuration)
    {
        $required = false;
        if (!is_array($this->databaseHandler->getBlockade($type, $authKey, $authIdentifier))) {
            $timestamp = time() - $configuration->getTimeRangeInSeconds();
            $failedAttempts = $this->databaseHandler->countFailedAttempts($authIdentifier, $authKey, $type, $timestamp);
            $required = $failedAttempts > $configuration->getMaxFailedAttempts();
        }
        return $required;
    }

    /**
     * @return string
     */
    protected function getHashedRequestIp()
    {
        return md5(GeneralUtility::getIndpEnv('REMOTE_ADDR'));
    }

    /**
     * @param string $failureValue
     * @return string
     */
    protected function getHashedFailureValue($failureValue)
    {
        // Three-way hashing
        return hash('sha256', sha1(md5('42' . $failureValue) . $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey']));
    }

    /**
     * @param string $authIdentifier
     * @param string $type
     * @param BlockingConfiguration $configuration
     * @return string
     */
    protected function getReasonText($authIdentifier, $type, BlockingConfiguration $configuration)
    {
        $reason = '';
        if ($type === static::TYPE_USER) {
            $reason = 'The user "' . $authIdentifier . '" has tried more than ' .
                $configuration->getMaxFailedAttempts() . ' different passwords in the last ' .
                $configuration->getTimeRangeInSeconds() . ' seconds';
        } elseif ($type === static::TYPE_IP) {
            $reason = 'The ip (hashed) "' . $authIdentifier . '" has tried more than ' .
                $configuration->getMaxFailedAttempts() . ' different users in the last ' .
                $configuration->getTimeRangeInSeconds() . ' seconds';
        }
        return $reason;
    }

    /**
     * @return BlockingConfiguration
     */
    protected function getDefaultBlockingConfiguration()
    {
        $config = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['secure_login']['defaultBlockingConfiguration'][TYPO3_MODE];
        if (!is_object($config) || !is_a($config, BlockingConfiguration::class)) {
            $config = BlockingConfiguration::createConfig();
        }
        return $config;
    }
}
