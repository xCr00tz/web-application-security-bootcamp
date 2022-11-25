<?php
namespace Concrete\Core\Permission\Response;

use Block;
use Concrete\Core\Area\Area;
use Concrete\Core\Page\Page;
use Concrete\Core\Permission\Access\Entity\Entity as PermissionAccessEntity;
use Concrete\Core\Permission\Assignment\PageTimedAssignment as PageContentPermissionTimedAssignment;
use Concrete\Core\Permission\Duration as PermissionDuration;
use Concrete\Core\Permission\Key\AreaKey as AreaPermissionKey;
use Concrete\Core\Permission\Key\BlockKey as BlockPermissionKey;
use Concrete\Core\Permission\Key\Key;
use Concrete\Core\Permission\Key\PageKey as PagePermissionKey;
use Concrete\Core\Support\Facade\Application;
use Concrete\Core\User\User;
use Config;
use Loader;
use Permissions;
use Session;
use TaskPermission;

class PageResponse extends Response
{
    // legacy support
    public function canWrite()
    {
        return $this->validate('edit_page_contents');
    }

    public function canReadVersions()
    {
        return $this->validate('view_page_versions');
    }

    public function canRead()
    {
        return $this->canViewPage();
    }

    public function canAddSubContent()
    {
        return $this->validate('add_subpage');
    }

    public function canViewPageInSitemap()
    {
        if (Config::get('concrete.permissions.model') != 'simple') {
            $pk = $this->category->getPermissionKeyByHandle('view_page_in_sitemap');
            $pk->setPermissionObject($this->object);

            return $pk->validate();
        }

        return $this->canViewPage();
    }

    public function canViewPage()
    {
        return $this->validate('view_page');
    }

    public function canAddSubpages()
    {
        return $this->validate('add_subpage');
    }

    public function canDeleteCollection()
    {
        return $this->canDeletePage();
    }

    public function canEditPageType()
    {
        return $this->validate('edit_page_page_type');
    }

    public function canApproveCollection()
    {
        return $this->validate('approve_page_versions');
    }

    public function canAdminPage()
    {
        return $this->validate('edit_page_permissions');
    }

    public function canAdmin()
    {
        return $this->validate('edit_page_permissions');
    }

    public function canAddExternalLink()
    {
        $pk = $this->category->getPermissionKeyByHandle('add_subpage');
        $pk->setPermissionObject($this->object);

        return $pk->canAddExternalLink();
    }

    public function canAddSubCollection($ct)
    {
        $pk = $this->category->getPermissionKeyByHandle('add_subpage');
        $pk->setPermissionObject($this->object);

        return $pk->validate($ct);
    }

    public function canAddBlockType($bt)
    {
        // Check can add the block to any area on the site.
        $key = Key::getByHandle('add_block');
        if (!$key || !$key->validate($bt)) {
            return false;
        }

        // Check can add blocks to this area.
        $list = Area::getListOnPage($this->object);
        foreach ($list as $la) {
            $lap = new Permissions($la);
            if ($lap->canAddBlockToArea($bt)) {
                return true;
            }
        }

        return false;
    }

    public function canEditPageProperties($obj = false)
    {
        $pk = $this->category->getPermissionKeyByHandle('edit_page_properties');
        $pk->setPermissionObject($this->object);

        return $pk->validate($obj);
    }

    public function canDeletePage()
    {
        return $this->validate('delete_page');
    }

    // end legacy

    // convenience function
    public function canViewToolbar()
    {
        $app = Application::getFacadeApplication();
        $u = $app->make(User::class);
        if (!$u->isRegistered()) {
            return false;
        }
        if ($u->isSuperUser()) {
            return true;
        }

        $app = Application::getFacadeApplication();
        $sh = $app->make('helper/concrete/dashboard/sitemap');
        if ($sh->canViewSitemapPanel()) {
            return true;
        }

        $dh = $app->make('helper/concrete/dashboard');
        if ($dh->canRead() ||
            $this->canViewPageVersions() ||
            $this->canPreviewPageAsUser() ||
            $this->canEditPageSpeedSettings() ||
            $this->canEditPageProperties() ||
            $this->canEditPageContents() ||
            $this->canAddSubpage() ||
            $this->canDeletePage() ||
            $this->canApprovePageVersions() ||
            $this->canEditPagePermissions() ||
            $this->canMoveOrCopyPage()
        ) {
            return true;
        }
        $c = Page::getCurrentPage();
        if ($c && $c->getCollectionPath() == STACKS_LISTING_PAGE_PATH) {
            return true;
        }

        return false;
    }

    public function testForErrors()
    {
        if ($this->object->isMasterCollection()) {
            $canEditMaster = TaskPermission::getByHandle('access_page_defaults')->can();
            if (!($canEditMaster && Session::get('mcEditID') == $this->object->getCollectionID())) {
                return COLLECTION_FORBIDDEN;
            }
        } else {
            if ((!$this->canViewPage()) && (!$this->object->getCollectionPointerExternalLink() != '')) {
                return COLLECTION_FORBIDDEN;
            }
        }

        return parent::testForErrors();
    }

    public function getAllTimedAssignmentsForPage()
    {
        return $this->getAllAssignmentsForPage();
    }

    public function getAllAssignmentsForPage()
    {
        $db = Loader::db();
        $assignments = [];
        $r = $db->Execute(
            'select peID, pkID, pdID from PagePermissionAssignments ppa inner join PermissionAccessList pal on ppa.paID = pal.paID where cID = ?',
            [$this->object->getCollectionID()]
        );
        while ($row = $r->FetchRow()) {
            $pk = PagePermissionKey::getByID($row['pkID']);
            $pae = PermissionAccessEntity::getByID($row['peID']);
            $pd = PermissionDuration::getByID($row['pdID']);
            $ppc = new PageContentPermissionTimedAssignment();
            $ppc->setDurationObject($pd);
            $ppc->setAccessEntityObject($pae);
            $ppc->setPermissionKeyObject($pk);
            $assignments[] = $ppc;
        }
        $r = $db->Execute(
            'select arHandle from Areas where cID = ? and arOverrideCollectionPermissions = 1',
            [$this->object->getCollectionID()]
        );
        while ($row = $r->FetchRow()) {
            $r2 = $db->Execute(
                'select peID, pdID, pkID from AreaPermissionAssignments apa inner join PermissionAccessList pal on apa.paID = pal.paID where cID = ? and arHandle = ?',
                [$this->object->getCollectionID(), $row['arHandle']]
            );
            while ($row2 = $r2->FetchRow()) {
                $pk = AreaPermissionKey::getByID($row2['pkID']);
                $pae = PermissionAccessEntity::getByID($row2['peID']);
                $area = Area::get($this->getPermissionObject(), $row['arHandle']);
                $pk->setPermissionObject($area);
                $pd = PermissionDuration::getByID($row2['pdID']);
                $ppc = new PageContentPermissionTimedAssignment();
                $ppc->setDurationObject($pd);
                $ppc->setAccessEntityObject($pae);
                $ppc->setPermissionKeyObject($pk);
                $assignments[] = $ppc;
            }
        }
        $r = $db->Execute(
            'select peID, cvb.cvID, cvb.bID, pdID, pkID from BlockPermissionAssignments bpa
                    inner join PermissionAccessList pal on bpa.paID = pal.paID inner join CollectionVersionBlocks cvb on cvb.cID = bpa.cID and cvb.cvID = bpa.cvID and cvb.bID = bpa.bID
                    where cvb.cID = ? and cvb.cvID = ? and cvb.cbOverrideAreaPermissions = 1',
            [$this->object->getCollectionID(), $this->object->getVersionID()]
        );
        while ($row = $r->FetchRow()) {
            $pk = BlockPermissionKey::getByID($row['pkID']);
            $pae = PermissionAccessEntity::getByID($row['peID']);
            $arHandle = $db->GetOne(
                'select arHandle from CollectionVersionBlocks where bID = ? and cvID = ? and cID = ?',
                [
                    $row['bID'],
                    $row['cvID'],
                    $this->object->getCollectionID(),
                ]
            );
            $b = Block::getByID($row['bID'], $this->object, $arHandle);
            $pk->setPermissionObject($b);
            $pd = PermissionDuration::getByID($row['pdID']);
            $ppc = new PageContentPermissionTimedAssignment();
            $ppc->setDurationObject($pd);
            $ppc->setAccessEntityObject($pae);
            $ppc->setPermissionKeyObject($pk);
            $assignments[] = $ppc;
        }

        return $assignments;
    }
}
