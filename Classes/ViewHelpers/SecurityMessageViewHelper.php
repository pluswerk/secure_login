<?php
namespace Pluswerk\SecureLogin\ViewHelpers;

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

use Pluswerk\SecureLogin\Service\AuthSecurityService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * Class SecurityMessageViewHelper
 *
 * @author Markus Hölzle <markus.hoelzle@pluswerk.ag>
 * @copyright 2018 +Pluswerk AG
 * @license GPL, version 2
 * @package Pluswerk\SecureLogin\ViewHelpers
 */
class SecurityMessageViewHelper extends AbstractViewHelper
{

    /**
     * @return string
     */
    public function render()
    {
        return GeneralUtility::makeInstance(AuthSecurityService::class)->getPublicErrorMessage();
    }
}
