<?php
namespace Pluswerk\SecureLogin\Service\AuthenticationServices;

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

/**
 * Class AuthenticationService
 *
 * @author Markus Hölzle <markus.hoelzle@pluswerk.ag>
 * @copyright 2018 +Pluswerk AG
 * @license GPL, version 2
 * @package Pluswerk\SecureLogin\AuthenticationServices
 */
class AuthenticationService extends \TYPO3\CMS\Sv\AuthenticationService
{

    /**
     * Find a user (eg. look up the user record in database when a login is sent)
     *
     * @return array|bool User array or FALSE
     */
    public function getUser()
    {
        $result = parent::getUser();
        if ($result === false && $this->login['status'] === 'login' && (string)$this->login['uident_text'] !== '' &&
            (string)$this->login['uname'] !== ''
        ) {
            /** @var AuthSecurityService $authSecurityService */
            $authSecurityService = GeneralUtility::makeInstance(AuthSecurityService::class);
            $authSecurityService->logUserAuthenticationFailed($this->login['uname'], TYPO3_MODE);
            if ($authSecurityService->getBlockade($this->login['uname'], TYPO3_MODE) !== null) {
                $result = false;
            }
        }
        return $result;
    }

    /**
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
        $result = (int)parent::authUser($user);
        if ($result === 0) {
            /** @var AuthSecurityService $authSecurityService */
            $authSecurityService = GeneralUtility::makeInstance(AuthSecurityService::class);
            $authSecurityService->logUserPasswordAuthenticationFailed($user['username'], $this->login['uident_text'], TYPO3_MODE);
        }
        return $result;
    }
}
