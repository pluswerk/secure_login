<?php
namespace Pluswerk\SecureLogin\Persistence;

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

use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\SingletonInterface;

/**
 * Class DatabaseHandler
 *
 * @author Markus Hölzle <markus.hoelzle@pluswerk.ag>
 * @copyright 2018 +Pluswerk AG
 * @license GPL, version 2
 * @package Pluswerk\SecureLogin\Persistence
 */
class DatabaseHandler implements SingletonInterface
{
    const TABLE_FAILED_ATTEMPT = 'tx_securelogin_failed_attempt';
    const TABLE_BLOCKADE = 'tx_securelogin_blockade';

    /**
     * @var DatabaseConnection
     */
    protected $databaseConnection = null;

    /**
     * AuthSecurityService constructor.
     */
    public function __construct()
    {
        $this->databaseConnection = $GLOBALS['TYPO3_DB'];
    }

    /**
     * @param string $authIdentifier
     * @param string $hashedFailureValue
     * @param string $type
     * @param string $authKey
     * @param int $timestamp
     * @return void
     */
    public function logFailedAttempt($authIdentifier, $hashedFailureValue, $type, $authKey, $timestamp = null)
    {
        if ($timestamp === null) {
            $timestamp = time();
        }

        $whereClause = $this->getFailedAttemptWhereClause($type, $authKey, $authIdentifier, $hashedFailureValue);
        $failedAttempt = $this->databaseConnection->exec_SELECTgetSingleRow(
            '*',
            static::TABLE_FAILED_ATTEMPT,
            $whereClause
        );

        if (is_array($failedAttempt)) {
            $this->databaseConnection->exec_UPDATEquery(
                static::TABLE_FAILED_ATTEMPT,
                $whereClause,
                ['timestamp' => $timestamp]
            );
        } else {
            $this->databaseConnection->exec_INSERTquery(
                static::TABLE_FAILED_ATTEMPT,
                [
                    'type' => (string)$type,
                    'auth_key' => (string)$authKey,
                    'auth_identifier' => (string)$authIdentifier,
                    'hashed_failure_value' => (string)$hashedFailureValue,
                    'timestamp' => (int)$timestamp,
                ]
            );
        }
    }

    /**
     * @param string $authIdentifier
     * @param string $authKey
     * @param string $type
     * @param int $greaterThanTimestamp
     * @return int
     */
    public function countFailedAttempts($authIdentifier, $authKey, $type, $greaterThanTimestamp)
    {
        $whereClause = $this->getFailedAttemptWhereClause($type, $authKey, $authIdentifier);
        $whereClause .= ' AND timestamp >= ' . $greaterThanTimestamp;
        return $this->databaseConnection->exec_SELECTcountRows('*', static::TABLE_FAILED_ATTEMPT, $whereClause);
    }

    /**
     * @param string $type
     * @param string $authKey
     * @param string $authIdentifier
     * @param string $reason
     * @param int $blockingPeriodInSeconds
     * @param int $timestamp
     * @return void
     */
    public function addBlockade($type, $authKey, $authIdentifier, $reason, $blockingPeriodInSeconds, $timestamp = null)
    {
        if ($timestamp === null) {
            $timestamp = time();
        }
        $expired = $timestamp + $blockingPeriodInSeconds;
        $this->databaseConnection->exec_INSERTquery(
            static::TABLE_BLOCKADE,
            [
                'type' => (string)$type,
                'auth_key' => (string)$authKey,
                'auth_identifier' => (string)$authIdentifier,
                'reason' => (string)$reason,
                'expired' => (int)$expired,
                'timestamp' => (int)$timestamp,
            ]
        );
    }

    /**
     * @param string $type
     * @param string $authKey
     * @param string $authIdentifier
     * @param int $timestamp
     * @return array
     */
    public function getBlockade($type, $authKey, $authIdentifier, $timestamp = null)
    {
        if ($timestamp === null) {
            $timestamp = time();
        }
        return $this->databaseConnection->exec_SELECTgetSingleRow(
            '*',
            static::TABLE_BLOCKADE,
            'type = ' . $this->databaseConnection->fullQuoteStr($type, static::TABLE_BLOCKADE) .
                ' AND auth_key = ' .
                $this->databaseConnection->fullQuoteStr($authKey, static::TABLE_BLOCKADE) .
                ' AND auth_identifier = ' .
                $this->databaseConnection->fullQuoteStr($authIdentifier, static::TABLE_BLOCKADE) .
                ' AND expired >= ' . (int)$timestamp
        );
    }

    /**
     * @param string $type
     * @param string $authKey
     * @param string $authIdentifier
     * @param string $hashedFailureValue
     * @return string
     */
    protected function getFailedAttemptWhereClause($type, $authKey, $authIdentifier, $hashedFailureValue = '')
    {
        $whereClause = 'type = ' .
            $this->databaseConnection->fullQuoteStr($type, static::TABLE_FAILED_ATTEMPT) .
            ' AND auth_key = ' .
            $this->databaseConnection->fullQuoteStr($authKey, static::TABLE_FAILED_ATTEMPT) .
            ' AND auth_identifier = ' .
            $this->databaseConnection->fullQuoteStr($authIdentifier, static::TABLE_FAILED_ATTEMPT);
        if ($hashedFailureValue !== '') {
            $whereClause .= ' AND hashed_failure_value = ' .
                $this->databaseConnection->fullQuoteStr($hashedFailureValue, static::TABLE_FAILED_ATTEMPT);
        }
        return $whereClause;
    }
}
