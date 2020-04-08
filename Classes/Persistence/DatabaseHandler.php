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

namespace Pluswerk\SecureLogin\Persistence;

use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

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
     * @var \TYPO3\CMS\Core\Database\ConnectionPool
     */
    protected $queryBuilder = null;

    /**
     * LEGACY CODE
     * @var DatabaseConnection
     */
    protected $databaseConnection = null;

    /**
     * AuthSecurityService constructor.
     */
    public function __construct()
    {
        $this->initDatabaseConnection();
    }

    /**
     * @return void
     */
    protected function initDatabaseConnection()
    {
        if (class_exists('\TYPO3\CMS\Core\Database\ConnectionPool')) {
            $this->queryBuilder = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Database\ConnectionPool::class);
        } elseif ($GLOBALS['TYPO3_DB']) {
            // LEGACY CODE
            $this->databaseConnection = $GLOBALS['TYPO3_DB'];
        }
    }

    /**
     * @param string $authIdentifier
     * @param string $hashedFailureValue
     * @param string $type
     * @param string $authKey
     * @param int $timestamp
     *
     * @return void
     */
    public function logFailedAttempt($authIdentifier, $hashedFailureValue, $type, $authKey, $timestamp = null)
    {
        if ($timestamp === null) {
            $timestamp = time();
        }
        $values = [
            'type' => (string)$type,
            'auth_key' => (string)$authKey,
            'auth_identifier' => (string)$authIdentifier,
            'hashed_failure_value' => (string)$hashedFailureValue,
            'timestamp' => (int)$timestamp,
        ];
        if ($this->queryBuilder) {
            $queryBuilder = $this->queryBuilder
                ->getQueryBuilderForTable(static::TABLE_FAILED_ATTEMPT);
            $resource = $queryBuilder
                ->select('*')
                ->from(static::TABLE_FAILED_ATTEMPT);
            $resource = $this->addFailedAttemptWhereClause(
                $resource,
                $queryBuilder,
                $type,
                $authKey,
                $authIdentifier,
                $hashedFailureValue
            );
            $failedAttempt = $resource->execute()->fetchColumn(0);
            $queryBuilder = $this->queryBuilder
                ->getQueryBuilderForTable(static::TABLE_FAILED_ATTEMPT);
            if (is_array($failedAttempt)) {
                $resource = $queryBuilder
                    ->update(static::TABLE_FAILED_ATTEMPT);
                $resource = $this->addFailedAttemptWhereClause(
                    $resource,
                    $queryBuilder,
                    $type,
                    $authKey,
                    $authIdentifier,
                    $hashedFailureValue
                )
                ->set('timestamp', (int)$timestamp)
                ->execute();
            } else {
                $resource = $queryBuilder
                    ->insert(static::TABLE_FAILED_ATTEMPT)
                    ->values($values)
                    ->execute();
            }
        } else {
            // LEGACY CODE
            // -----------
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
                    ['timestamp' => (int)$timestamp]
                );
            } else {
                $this->databaseConnection->exec_INSERTquery(
                    static::TABLE_FAILED_ATTEMPT,
                    $values
                );
            }
        }
    }

    /**
     * @param string $authIdentifier
     * @param string $authKey
     * @param string $type
     * @param int $greaterThanTimestamp
     *
     * @return int
     */
    public function countFailedAttempts($authIdentifier, $authKey, $type, $greaterThanTimestamp)
    {
        if ($this->queryBuilder) {
            $queryBuilder = $this->queryBuilder
                ->getQueryBuilderForTable(static::TABLE_FAILED_ATTEMPT);
            $resource = $queryBuilder
                ->count('*')
                ->from(static::TABLE_FAILED_ATTEMPT);
            $resource = $this->addFailedAttemptWhereClause(
                $resource,
                $queryBuilder,
                $type,
                $authKey,
                $authIdentifier
            )
            ->andWhere(
                $queryBuilder->expr()->gte('timestamp', $queryBuilder->createNamedParameter($greaterThanTimestamp, \PDO::PARAM_INT))
            );
            $failedAttempts = $resource->execute()->fetchColumn(0);
        } else {
            // LEGACY CODE
            // -----------
            $whereClause = $this->getFailedAttemptWhereClause($type, $authKey, $authIdentifier);
            $whereClause .= ' AND timestamp >= ' . $greaterThanTimestamp;
            $failedAttempts = $this->databaseConnection->exec_SELECTcountRows('*', static::TABLE_FAILED_ATTEMPT, $whereClause);
        }
        return $failedAttempts;
    }

    /**
     * @param string $type
     * @param string $authKey
     * @param string $authIdentifier
     * @param string $reason
     * @param int $blockingPeriodInSeconds
     * @param int $timestamp
     *
     * @return void
     */
    public function addBlockade($type, $authKey, $authIdentifier, $reason, $blockingPeriodInSeconds, $timestamp = null)
    {
        if ($timestamp === null) {
            $timestamp = time();
        }
        $expired = $timestamp + $blockingPeriodInSeconds;
        $values = [
            'type' => (string)$type,
            'auth_key' => (string)$authKey,
            'auth_identifier' => (string)$authIdentifier,
            'reason' => (string)$reason,
            'expired' => (int)$expired,
            'timestamp' => (int)$timestamp,
        ];
        if ($this->queryBuilder) {
            $queryBuilder = $this->queryBuilder
                ->getQueryBuilderForTable(static::TABLE_BLOCKADE);
            $affectedRows = $queryBuilder
                ->insert(static::TABLE_BLOCKADE)
                ->values($values)
                ->execute();
        } else {
            // LEGACY CODE
            // -----------
            $this->databaseConnection->exec_INSERTquery(
                static::TABLE_BLOCKADE,
                $values
            );
        }
    }

    /**
     * @param string $type
     * @param string $authKey
     * @param string $authIdentifier
     * @param int $timestamp
     *
     * @return array
     */
    public function getBlockade($type, $authKey, $authIdentifier, $timestamp = null)
    {
        if ($timestamp === null) {
            $timestamp = time();
        }
        if ($this->queryBuilder) {
            $queryBuilder = $this->queryBuilder
                ->getQueryBuilderForTable(static::TABLE_BLOCKADE);
            $query = $queryBuilder->select('*')
                ->from(static::TABLE_BLOCKADE)
                ->where(
                    $queryBuilder->expr()->eq('type', $queryBuilder->createNamedParameter($type)),
                    $queryBuilder->expr()->eq('auth_key', $queryBuilder->createNamedParameter($authKey)),
                    $queryBuilder->expr()->eq('auth_identifier', $queryBuilder->createNamedParameter($authIdentifier)),
                    $queryBuilder->expr()->gte('expired', $queryBuilder->createNamedParameter($timestamp, \PDO::PARAM_INT))
                )
                ->execute();

            $results = $query->fetchAll();

            if (is_array($results) && count($results)) {
                return $results[count($results) - 1];
            } else {
                return $results;
            }
        } else {
            // LEGACY CODE
            // -----------
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
    }

    /**
     * LEGACY CODE
     * @deprecated this function will be removed in version for TYPO3 Version 10
     * @param string $type
     * @param string $authKey
     * @param string $authIdentifier
     * @param string $hashedFailureValue
     *
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

    /**
     * @param object $resource
     * @param object $queryBuilder
     * @param string $type
     * @param string $authKey
     * @param string $authIdentifier
     * @param string $hashedFailureValue
     *
     * @return object $resource
     */
    protected function addFailedAttemptWhereClause(
        $resource,
        $queryBuilder,
        $type,
        $authKey,
        $authIdentifier,
        $hashedFailureValue = ''
    ) {
        $type           = $queryBuilder->createNamedParameter($type);
        $authKey        = $queryBuilder->createNamedParameter($authKey);
        $authIdentifier = $queryBuilder->createNamedParameter($authIdentifier);

        if ($hashedFailureValue === '') {
            $resource = $resource->where(
                $queryBuilder->expr()->eq('type', $type),
                $queryBuilder->expr()->eq('auth_key', $authKey),
                $queryBuilder->expr()->eq('auth_identifier', $authIdentifier)
            );
        } else {
            $hashed_failure_value = $queryBuilder->createNamedParameter($hashed_failure_value);

            $resource = $resource->where(
                $queryBuilder->expr()->eq('type', $type),
                $queryBuilder->expr()->eq('auth_key', $authKey),
                $queryBuilder->expr()->eq('auth_identifier', $authIdentifier),
                $queryBuilder->expr()->eq('hashed_failure_value', $hashed_failure_value)
            );
        }
        return $resource;
    }
}
