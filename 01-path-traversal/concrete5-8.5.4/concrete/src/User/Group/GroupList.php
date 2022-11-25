<?php
namespace Concrete\Core\User\Group;

use Concrete\Core\Database\Connection\Connection;
use Concrete\Core\Entity\Package;
use Concrete\Core\Search\ItemList\Database\ItemList as DatabaseItemList;
use Concrete\Core\Search\Pagination\Pagination;
use Concrete\Core\Support\Facade\Facade;
use Concrete\Core\User\User;
use Loader;
use Config;
use Pagerfanta\Adapter\DoctrineDbalAdapter;
use Permissions;

class GroupList extends DatabaseItemList
{
    protected $includeAllGroups = false;

    protected $autoSortColumns = ['gName', 'gID'];

    public function includeAllGroups()
    {
        $this->includeAllGroups = true;
    }

    /**
     * Filters keyword fields by keywords (including name and description).
     *
     * @param $keywords
     */
    public function filterByKeywords($keywords)
    {
        $expressions = [
            $this->query->expr()->like('g.gName', ':keywords'),
            $this->query->expr()->like('g.gDescription', ':keywords'),
        ];

        $expr = $this->query->expr();
        $this->query->andWhere(call_user_func_array([$expr, 'orX'], $expressions));
        $this->query->setParameter('keywords', '%' . $keywords . '%');
    }

    public function filterByPackage(Package $package)
    {
        $this->query->andWhere('pkgID = :pkgID');
        $this->query->setParameter('pkgID', $package->getPackageID());
    }

    public function filterByExpirable()
    {
        $this->query->andWhere('gUserExpirationIsEnabled', ':gUserExpirationIsEnabled');
        $this->query->setParameter('gUserExpirationIsEnabled', 1);
    }

    /**
     * Only return groups the user has the ability to assign.
     */
    public function filterByAssignable()
    {
        if (Config::get('concrete.permissions.model') != 'simple') {
            // there's gotta be a more reasonable way than this but right now i'm not sure what that is.
            $excludeGroupIDs = [GUEST_GROUP_ID, REGISTERED_GROUP_ID];
            $db = Loader::db();
            $r = $db->executeQuery('select gID from ' . $db->getDatabasePlatform()->quoteSingleIdentifier('Groups') . ' where gID > ?', [REGISTERED_GROUP_ID]);
            while ($row = $r->FetchRow()) {
                $g = Group::getByID($row['gID']);
                $gp = new Permissions($g);
                if (!$gp->canAssignGroup()) {
                    $excludeGroupIDs[] = $row['gID'];
                }
            }
            $this->query->andWhere(
                $this->query->expr()->notIn('g.gId', array_map([$db, 'quote'], $excludeGroupIDs))
            );
        }
    }

    /**
     * Only return groups the user is actually a member of
     */
    public function filterByHavingMembership()
    {
        $inGroupIDs = [];
        $app = Facade::getFacadeApplication();
        $u = $app->make(User::class);
        $db = $app->make(Connection::class);
        if ($u->isRegistered()) {
            $groups = $u->getUserGroups(); // returns an array of IDs
            $this->query->andWhere(
                $this->query->expr()->in('g.gId', array_map([$db, 'quote'], $groups))
            );
        }

    }

    public function filterByParentGroup(Group $parent)
    {
        $this->query->andWhere($this->query->expr()->like('g.gPath', ':gPath'));
        $this->query->setParameter('gPath', $parent->getGroupPath() . '/%');
    }

    public function filterByUserID($uID)
    {
        $this->query->innerJoin('g', 'UserGroups', 'ug', 'g.gID = ug.gID');
        $this->query->andWhere('ug.uID = :uID');
        $this->query->setParameter('uID', $uID);
    }

    public function createQuery()
    {
        $this->query->select('g.gID')
            ->from($this->query->getConnection()->getDatabasePlatform()->quoteSingleIdentifier('Groups'), 'g');
    }

    public function finalizeQuery(\Doctrine\DBAL\Query\QueryBuilder $query)
    {
        if (!$this->includeAllGroups) {
            $query->andWhere('g.gID > :minGroupID');
            $query->setParameter('minGroupID', REGISTERED_GROUP_ID);
        }

        return $query;
    }

    /**
     * The total results of the query.
     *
     * @return int
     */
    public function getTotalResults()
    {
        $query = $this->deliverQueryObject();

        return $query->resetQueryParts(['groupBy', 'orderBy'])->select('count(distinct g.gID)')->setMaxResults(1)->execute()->fetchColumn();
    }

    /**
     * Gets the pagination object for the query.
     *
     * @return Pagination
     */
    protected function createPaginationObject()
    {
        $adapter = new DoctrineDbalAdapter($this->deliverQueryObject(), function ($query) {
            $query->resetQueryParts(['groupBy', 'orderBy'])->select('count(distinct g.gID)')->setMaxResults(1);
        });
        $pagination = new Pagination($this, $adapter);

        return $pagination;
    }

    /**
     * @param $queryRow
     *
     * @return \Concrete\Core\User\Group\Group
     */
    public function getResult($queryRow)
    {
        $g = Group::getByID($queryRow['gID']);

        return $g;
    }
}
