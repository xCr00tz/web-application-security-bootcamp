<?php
namespace Concrete\Core\Permission;

use Core;
use Database;
use Concrete\Core\Foundation\ConcreteObject;
use Concrete\Core\Package\PackageList;

class Category extends ConcreteObject
{
    protected static $categories;

    public static function getByID($pkCategoryID)
    {
        if (!self::$categories) {
            self::populateCategories();
        }

        return self::$categories[$pkCategoryID];
    }

    protected static function populateCategories()
    {
        $db = Database::get();
        self::$categories = array();
        $r = $db->Execute('select pkCategoryID, pkCategoryHandle, pkgID from PermissionKeyCategories');
        while ($row = $r->FetchRow()) {
            $pkc = new static();
            $pkc->setPropertiesFromArray($row);
            self::$categories[$pkc->getPermissionKeyCategoryID()] = $pkc;
            self::$categories[$pkc->getPermissionKeyCategoryHandle()] = $pkc;
        }
    }

    public static function getByHandle($pkCategoryHandle)
    {
        if (!self::$categories) {
            self::populateCategories();
        }

        return array_key_exists($pkCategoryHandle, self::$categories) ? self::$categories[$pkCategoryHandle] : null;
    }

    public function handleExists($pkHandle)
    {
        $db = Database::get();
        $r = $db->GetOne("select count(pkID) from PermissionKeys where pkHandle = ?", array($pkHandle));

        return $r > 0;
    }

    public static function exportList($xml)
    {
        $attribs = self::getList();
        $axml = $xml->addChild('permissioncategories');
        foreach ($attribs as $pkc) {
            $acat = $axml->addChild('category');
            $acat->addAttribute('handle', $pkc->getPermissionKeyCategoryHandle());
            $acat->addAttribute('package', $pkc->getPackageHandle());
        }
    }

    public static function getListByPackage($pkg)
    {
        $db = Database::get();
        $list = array();
        $r = $db->Execute('select pkCategoryID from PermissionKeyCategories where pkgID = ? order by pkCategoryID asc', array($pkg->getPackageID()));
        while ($row = $r->FetchRow()) {
            $list[] = static::getByID($row['pkCategoryID']);
        }
        $r->Close();

        return $list;
    }

    public function getPermissionKeyClass()
    {
        $className = core_class('\\Core\\Permission\\Key\\'
            . Core::make("helper/text")->camelcase($this->pkCategoryHandle) . 'Key',
            $this->getPackageHandle()
        );

        return $className;
    }

    public function getPermissionKeyByHandle($pkHandle)
    {
        $ak = call_user_func(array($this->getPermissionKeyClass(), 'getByHandle'), $pkHandle);

        return $ak;
    }

    public function getPermissionKeyByID($pkID)
    {
        $ak = call_user_func(array($this->getPermissionKeyClass(), 'getByID'), $pkID);

        return $ak;
    }

    public function getToolsURL($task = false)
    {
        if (!$task) {
            $task = 'save_permission';
        }
        $uh = Core::make('helper/concrete/urls');
        $akc = static::getByID($this->getPermissionKeyCategoryID());
        $url = $uh->getToolsURL('permissions/categories/' . $this->pkCategoryHandle, $akc->getPackageHandle());
        $token = Core::make('helper/validation/token')->getParameter($task);
        $url .= '?' . $token . '&task=' . $task;

        return $url;
    }

    public function getPermissionKeyCategoryID()
    {
        return $this->pkCategoryID;
    }
    public function getPermissionKeyCategoryHandle()
    {
        return $this->pkCategoryHandle;
    }
    public function getPackageID()
    {
        return $this->pkgID;
    }
    public function getPackageHandle()
    {
        return PackageList::getHandle($this->pkgID);
    }

    public function delete()
    {
        $db = Database::get();
        $db->Execute('delete from PermissionKeyCategories where pkCategoryID = ?', array($this->pkCategoryID));
    }

    public function associateAccessEntityType(\Concrete\Core\Permission\Access\Entity\Type $pt)
    {
        $db = Database::get();
        $r = $db->GetOne('select petID from PermissionAccessEntityTypeCategories where petID = ? and pkCategoryID = ?', array(
            $pt->getAccessEntityTypeID(), $this->pkCategoryID,
        ));
        if (!$r) {
            $db->Execute('insert into PermissionAccessEntityTypeCategories (petID, pkCategoryID) values (?, ?)', array(
                $pt->getAccessEntityTypeID(), $this->pkCategoryID,
            ));
        }
    }

    public function deassociateAccessEntityType(\Concrete\Core\Permission\Access\Entity\Type $pt)
    {
        $db = Database::get();
        $db->delete('PermissionAccessEntityTypeCategories', array('petID' => $pt->getAccessEntityTypeID(), 'pkCategoryID' => $this->getPermissionKeyCategoryID()));
    }

    public function clearAccessEntityTypeCategories()
    {
        $db = Database::get();
        $db->Execute('delete from PermissionAccessEntityTypeCategories where pkCategoryID = ?', $this->pkCategoryID);
    }

    public static function getList()
    {
        $db = Database::get();
        $cats = array();
        $r = $db->Execute('select pkCategoryID from PermissionKeyCategories order by pkCategoryID asc');
        while ($row = $r->FetchRow()) {
            $cats[] = static::getByID($row['pkCategoryID']);
        }

        return $cats;
    }

    public static function add($pkCategoryHandle, $pkg = false)
    {
        $db = Database::get();
        if (is_object($pkg)) {
            $pkgID = $pkg->getPackageID();
        } else {
            $pkgID = null;
        }
        $db->Execute('insert into PermissionKeyCategories (pkCategoryHandle, pkgID) values (?, ?)', array($pkCategoryHandle, $pkgID));
        $id = $db->Insert_ID();

        self::$categories = array();

        return static::getByID($id);
    }
}
