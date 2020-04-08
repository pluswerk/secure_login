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

namespace Pluswerk\SecureLogin\Service\AuthenticationServices;

use Pluswerk\SecureLogin\Service\AuthSecurityService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Authentication\AuthenticationService as CoreAuthenticationService;

/**
 * Class DenyAuthenticationService
 *
 * @author Markus Hölzle <markus.hoelzle@pluswerk.ag>
 * @copyright 2018 +Pluswerk AG
 * @license GPL, version 2
 * @package Pluswerk\SecureLogin\AuthenticationServices
 */
class DenyAuthenticationService extends CoreAuthenticationService
{

    /**
     * Deny authentication for banned users
     *
     * @param array $user Data of user.
     * @return int >= 200: User authenticated successfully.
     *                     No more checking is needed by other auth services.
     *             >= 100: User not authenticated; this service is not responsible.
     *                     Other auth services will be asked.
     *             > 0:    User authenticated successfully.
     *                     Other auth services will still be asked.
     *             <= 0:   Authentication failed, no more checking needed
     *                     by other auth services.
     */
    public function authUser(array $user): int
    {
        $result = 100;
        /** @var AuthSecurityService $authSecurityService */
        $authSecurityService = GeneralUtility::makeInstance(AuthSecurityService::class);
        if ($authSecurityService->getBlockade($user['username'], TYPO3_MODE) !== null) {
            $result = 0;
        }
        return $result;
    }
}
