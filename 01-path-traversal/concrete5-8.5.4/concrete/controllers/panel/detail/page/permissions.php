<?php
namespace Concrete\Controller\Panel\Detail\Page;

use Concrete\Controller\Backend\UserInterface\Page as BackendInterfacePageController;
use Concrete\Core\Permission\Access\Access;
use Concrete\Core\Permission\Access\Entity\GroupEntity as GroupPermissionAccessEntity;
use Concrete\Core\Permission\Key\PageKey as PagePermissionKey;
use Config;
use Group;
use GroupList;
use PageEditResponse;
use PermissionKey;
use View;

class Permissions extends BackendInterfacePageController
{
    protected $viewPath = '/panels/details/page/permissions/simple';

    protected function canAccess()
    {
        return $this->permissions->canEditPagePermissions();
    }

    public function view()
    {
        if (Config::get('concrete.permissions.model') != 'simple') {
            $this->setViewObject(new View('/panels/details/page/permissions/advanced'));
            $this->set('editPermissions', false);
            if ($this->page->getCollectionInheritance() == 'OVERRIDE') {
                $this->set('editPermissions', true);
            }
        } else {
            $editAccess = [];
            $viewAccess = [];
            $c = $this->page;

            $pk = PagePermissionKey::getByHandle('view_page');
            $pk->setPermissionObject($c);
            $assignments = $pk->getAccessListItems();
            foreach ($assignments as $asi) {
                $ae = $asi->getAccessEntityObject();
                if ($ae->getAccessEntityTypeHandle() == 'group') {
                    $group = $ae->getGroupObject();
                    if (is_object($group)) {
                        $viewAccess[] = $group->getGroupID();
                    }
                }
            }

            $pk = PermissionKey::getByHandle('edit_page_contents');
            $pk->setPermissionObject($c);
            $assignments = $pk->getAccessListItems();
            foreach ($assignments as $asi) {
                $ae = $asi->getAccessEntityObject();
                if ($ae->getAccessEntityTypeHandle() == 'group') {
                    $group = $ae->getGroupObject();
                    if (is_object($group)) {
                        $editAccess[] = $group->getGroupID();
                    }
                }
            }

            $gl = new GroupList();
            $gl->sortBy('gID', 'asc');
            $gl->includeAllGroups();
            $groups = $gl->getResults();

            $this->set('editAccess', $editAccess);
            $this->set('viewAccess', $viewAccess);
            $this->set('gArray', $groups);
        }
    }

    public function save_simple()
    {
        if ($this->validateAction()) {
            $c = $this->page;
            $c->setPermissionsToManualOverride();

            $pk = PermissionKey::getByHandle('view_page');
            $pk->setPermissionObject($c);
            $pt = $pk->getPermissionAssignmentObject();
            $pt->clearPermissionAssignment();
            $pa = Access::create($pk);

            $readGID = $this->request->request->get('readGID');
            if (is_array($readGID)) {
                foreach ($readGID as $gID) {
                    $pa->addListItem(GroupPermissionAccessEntity::getOrCreate(Group::getByID($gID)));
                }
            }
            $pt->assignPermissionAccess($pa);

            $editAccessEntities = [];
            $editGID = $this->request->request->get('editGID');
            if (is_array($editGID)) {
                foreach ($editGID as $gID) {
                    $editAccessEntities[] = GroupPermissionAccessEntity::getOrCreate(Group::getByID($gID));
                }
            }

            $editPermissions = [
                'view_page_versions',
                'edit_page_properties',
                'edit_page_contents',
                'edit_page_speed_settings',
                'edit_page_multilingual_settings',
                'edit_page_theme',
                'edit_page_page_type',
                'edit_page_template',
                'edit_page_permissions',
                'preview_page_as_user',
                'schedule_page_contents_guest_access',
                'delete_page',
                'delete_page_versions',
                'approve_page_versions',
                'add_subpage',
                'move_or_copy_page',
            ];
            foreach ($editPermissions as $pkHandle) {
                $pk = PermissionKey::getByHandle($pkHandle);
                $pk->setPermissionObject($c);
                $pt = $pk->getPermissionAssignmentObject();
                $pt->clearPermissionAssignment();
                $pa = Access::create($pk);
                foreach ($editAccessEntities as $editObj) {
                    $pa->addListItem($editObj);
                }
                $pt->assignPermissionAccess($pa);
            }

            $r = new PageEditResponse();
            $r->setPage($this->page);
            $r->setTitle(t('Page Updated'));
            $r->setMessage(t('Page permissions have been saved.'));
            $r->outputJSON();
        }
    }
}
