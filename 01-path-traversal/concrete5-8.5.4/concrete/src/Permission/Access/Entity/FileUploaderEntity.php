<?php
namespace Concrete\Core\Permission\Access\Entity;

use Concrete\Core\Support\Facade\Application;
use Concrete\Core\Permission\Access\FileFolderAccess;
use Loader;
use Concrete\Core\Permission\Access\Access as PermissionAccess;
use Config;
use UserInfo;
use Concrete\Core\User\User;
use Concrete\Core\Permission\Access\FileSetAccess as FileSetPermissionAccess;
use Concrete\Core\Permission\Access\FileAccess as FilePermissionAccess;

class FileUploaderEntity extends Entity
{
    public function getAccessEntityUsers(PermissionAccess $pa)
    {
        $f = $pa->getPermissionObject();
        if (is_object($f) && ($f instanceof File)) {
            return UserInfo::getByID($f->getUserID());
        }
    }

    public function validate(PermissionAccess $pae)
    {
        if ($pae instanceof FileFolderAccess) {
            return true;
        }
        if ($pae instanceof FilePermissionAccess) {
            $f = $pae->getPermissionObject();
            if (is_object($f)) {
                $app = Application::getFacadeApplication();
                $u = $app->make(User::class);

                return $u->getUserID() == $f->getUserID();
            }
        }

        return false;
    }

    public function getAccessEntityTypeLinkHTML()
    {
        $html = '<a href="javascript:void(0)" onclick="ccm_choosePermissionAccessEntityFileUploader()">' . tc('PermissionAccessEntityTypeName', 'File Uploader') . '</a>';

        return $html;
    }

    public static function getAccessEntitiesForUser($user)
    {
        $entities = array();
        $db = Loader::db();
        if ($user->isRegistered()) {
            $pae = static::getOrCreate();
            $r = $db->GetOne('select fID from Files where uID = ?', array($user->getUserID()));
            if ($r > 0) {
                $entities[] = $pae;
            }
        }

        return $entities;
    }

    public static function getOrCreate()
    {
        $db = Loader::db();
        $petID = $db->GetOne('select petID from PermissionAccessEntityTypes where petHandle = \'file_uploader\'');
        $peID = $db->GetOne('select peID from PermissionAccessEntities where petID = ?',
            array($petID));
        if (!$peID) {
            $db->Execute("insert into PermissionAccessEntities (petID) values(?)", array($petID));
            $peID = $db->Insert_ID();
            Config::save('concrete.misc.access_entity_updated', time());
        }

        return \Concrete\Core\Permission\Access\Entity\Entity::getByID($peID);
    }

    public function load()
    {
        $this->label = t('File Uploader');
    }
}
