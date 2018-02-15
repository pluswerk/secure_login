<?php
namespace Pluswerk\SecureLogin\Configuration;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class BlockingConfiguration
 *
 * @author Markus Hölzle <markus.hoelzle@pluswerk.ag>
 * @copyright 2018 +Pluswerk AG
 * @license GPL, version 2
 * @package Pluswerk\SecureLogin\Configuration
 */
class BlockingConfiguration
{
    /**
     * @var int
     */
    protected $maxFailedAttempts = 5;

    /**
     * Default: 1 hour
     * @var int
     */
    protected $timeRangeInSeconds = 3600;

    /**
     * Default: 2 hours
     * @var int
     */
    protected $blockingPeriodInSeconds = 7200;

    /**
     * @param int $maxFailedAttempts
     * @param int $timeRangeInSeconds
     * @param int $blockingPeriodInSeconds
     * @return BlockingConfiguration
     */
    public static function createConfig($maxFailedAttempts = 0, $timeRangeInSeconds = 0, $blockingPeriodInSeconds = 0)
    {
        /** @var BlockingConfiguration $config */
        $config = GeneralUtility::makeInstance(BlockingConfiguration::class);
        if ($maxFailedAttempts > 0) {
            $config->setMaxFailedAttempts($maxFailedAttempts);
        }
        if ($timeRangeInSeconds > 0) {
            $config->setTimeRangeInSeconds($timeRangeInSeconds);
        }
        if ($blockingPeriodInSeconds > 0) {
            $config->setBlockingPeriodInSeconds($blockingPeriodInSeconds);
        }
        return $config;
    }

    /**
     * @return int
     */
    public function getMaxFailedAttempts()
    {
        return $this->maxFailedAttempts;
    }

    /**
     * @param int $maxFailedAttempts
     */
    public function setMaxFailedAttempts($maxFailedAttempts)
    {
        $this->maxFailedAttempts = $maxFailedAttempts;
    }

    /**
     * @return int
     */
    public function getTimeRangeInSeconds()
    {
        return $this->timeRangeInSeconds;
    }

    /**
     * @param int $timeRangeInSeconds
     */
    public function setTimeRangeInSeconds($timeRangeInSeconds)
    {
        $this->timeRangeInSeconds = $timeRangeInSeconds;
    }

    /**
     * @return int
     */
    public function getBlockingPeriodInSeconds()
    {
        return $this->blockingPeriodInSeconds;
    }

    /**
     * @param int $blockingPeriodInSeconds
     */
    public function setBlockingPeriodInSeconds($blockingPeriodInSeconds)
    {
        $this->blockingPeriodInSeconds = $blockingPeriodInSeconds;
    }
}
