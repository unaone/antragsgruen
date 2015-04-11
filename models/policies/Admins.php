<?php

namespace app\models\policies;

use app\models\db\User;
use app\models\wording\IWording;

class Admins extends IPolicy
{
    /**
     * @static
     * @return string
     */
    public static function getPolicyID()
    {
        return "admins";
    }

    /**
     * @static
     * @param IWording $wording
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public static function getPolicyName(IWording $wording)
    {
        return "Admins";
    }

    /**
     * @static
     * @return bool
     */
    public function checkCurUserHeuristically()
    {
        return false;
    }

    /**
     * @return string
     */
    public function getOnCreateDescription()
    {
        return 'Admins';
    }

    /**
     * @param IWording $wording
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getPermissionDeniedMotionMsg(IWording $wording)
    {
        return 'Nur Admins dürfen Anträge anlegen.';
    }

    /**
     * @param IWording $wording
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getPermissionDeniedAmendmentMsg(IWording $wording)
    {
        return 'Nur Admins dürfen Änderungsanträge anlegen.';
    }

    /**
     * @param IWording $wording
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getPermissionDeniedSupportMsg(IWording $wording)
    {
        return 'Nur Admins dürfen Anträge unterstützen.';
    }

    /**
     * @param IWording $wording
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getPermissionDeniedCommentMsg(IWording $wording)
    {
        return 'Nur Admins dürfen kommentieren';
    }

    /**
     * @return bool
     */
    public function checkMotionSubmit()
    {
        return User::currentUserHasPrivilege($this->consultation, User::PRIVILEGE_SCREENING);
    }
}