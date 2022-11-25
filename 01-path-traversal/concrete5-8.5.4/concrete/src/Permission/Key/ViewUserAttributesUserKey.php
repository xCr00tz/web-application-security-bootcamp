<?php
namespace Concrete\Core\Permission\Key;

use Concrete\Core\Permission\Access\ListItem\ViewUserAttributesUserListItem;
use Loader;
use Concrete\Core\User\User;
use Concrete\Core\Support\Facade\Application;
use \Concrete\Core\Permission\Duration as PermissionDuration;
use Concrete\Core\Permission\Key\Key as PermissionKey;

class ViewUserAttributesUserKey extends UserKey
{
    protected function getAllowedAttributeKeyIDs($list = false)
    {
        $app = Application::getFacadeApplication();
        if (!$list) {
            $u = $app->make(User::class);
            $accessEntities = $u->getUserAccessEntityObjects();
            $list = $this->getAccessListItems(UserKey::ACCESS_TYPE_ALL, $accessEntities);
            $list = PermissionDuration::filterByActive($list);
        }

        $db = Loader::db();
        $allakIDs = $db->GetCol('select akID from UserAttributeKeys');
        $akIDs = array();
        foreach ($list as $l) {
            if ($l->getAttributesAllowedPermission() == 'N') {
                $akIDs = array();
            }
            if ($l->getAttributesAllowedPermission() == 'C') {
                if ($l->getAccessType() == PermissionKey::ACCESS_TYPE_EXCLUDE) {
                    $akIDs = array_values(array_diff($akIDs, $l->getAttributesAllowedArray()));
                } else {
                    $akIDs = array_unique(array_merge($akIDs, $l->getAttributesAllowedArray()));
                }
            }
            if ($l->getAttributesAllowedPermission() == 'A') {
                $akIDs = $allakIDs;
            }
        }

        return $akIDs;
    }

    public function getMyAssignment()
    {
        $app = Application::getFacadeApplication();
        $u = $app->make(User::class);
        $asl = new ViewUserAttributesUserListItem();
        if ($u->isSuperUser()) {
            $asl->setAttributesAllowedPermission('A');

            return $asl;
        }

        $pae = $this->getPermissionAccessObject();
        if (!is_object($pae)) {
            return $asl;
        }

        $accessEntities = $u->getUserAccessEntityObjects();
        $accessEntities = $pae->validateAndFilterAccessEntities($accessEntities);
        $list = $this->getAccessListItems(UserKey::ACCESS_TYPE_ALL, $accessEntities);
        $list = PermissionDuration::filterByActive($list);

        foreach ($list as $l) {
            if ($l->getAttributesAllowedPermission() == 'N') {
                $asl->setAttributesAllowedPermission('N');
            }

            if ($l->getAttributesAllowedPermission() == 'C') {
                $asl->setAttributesAllowedPermission('C');
            }

            if ($l->getAttributesAllowedPermission() == 'A') {
                $asl->setAttributesAllowedPermission('A');
            }
        }

        $asl->setAttributesAllowedArray($this->getAllowedAttributeKeyIDs($list));

        return $asl;
    }

    public function validate($obj = false)
    {
        $app = Application::getFacadeApplication();
        $u = $app->make(User::class);
        if ($u->isSuperUser()) {
            return true;
        }

        $types = $this->getAllowedAttributeKeyIDs();
        if ($obj != false) {
            if (is_object($obj)) {
                $akID = $obj->getAttributeKeyID();
            } else {
                $akID = $obj;
            }

            return in_array($akID, $types);
        } else {
            return count($types) > 0;
        }
    }
}
