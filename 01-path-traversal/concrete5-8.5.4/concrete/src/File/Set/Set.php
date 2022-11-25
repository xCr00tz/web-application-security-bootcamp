<?php
namespace Concrete\Core\File\Set;

use Concrete\Core\Database\Connection\Connection;
use Concrete\Core\Entity\File\File as FileEntity;
use Concrete\Core\Entity\File\Image\Thumbnail\Type\Type as ThumbnailType;
use Concrete\Core\Entity\File\Version as FileVersionEntity;
use Concrete\Core\Support\Facade\Application;
use Concrete\Core\Permission\Access\Entity\GroupCombinationEntity as GroupCombinationPermissionAccessEntity;
use Concrete\Core\Permission\Access\Entity\GroupEntity as GroupPermissionAccessEntity;
use Concrete\Core\Permission\Access\Entity\UserEntity as UserPermissionAccessEntity;
use Concrete\Core\Permission\Key\FileSetKey as FileSetPermissionKey;
use Concrete\Core\Support\Facade\Facade;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Events;
use File as ConcreteFile;
use Database;
use Concrete\Core\Permission\Access\Access as PermissionAccess;
use PermissionKey;
use Permissions;
use Concrete\Core\User\User;

/**
 * Represents a file set.
 *
 * @method static Set add(string $setName, int $fsOverrideGlobalPermissions = 0, bool|\User $u = false, int $type = self::TYPE_PUBLIC) Deprecated method. Use Set::create instead.
 */
class Set
{
    const TYPE_PRIVATE = 0;
    const TYPE_PUBLIC = 1;
    const TYPE_STARRED = 2;
    const TYPE_SAVED_SEARCH = 3;
    const GLOBAL_FILESET_USER_ID = 0;

    protected $fileSetFiles;

    /**
     * @var int File Set ID
     */
    public $fsID;

    /**
     * @var int User ID
     */
    public $uID;

    /**
     * @var string File Set Name
     */
    public $fsName;

    /**
     * @var int
     */
    //public $fsOverrideGlobalPermissions;

    /**
     * @var int
     */
    public $fsType;

    public $fsSearchRequest;
    public $fsResultColumns;

    /**
     * Returns an object mapping to the global file set, fsID = 0.
     * This is really only used for permissions mapping.
     */
    public static function getGlobal()
    {
        $fs = new static();
        $fs->fsID = 0;

        return $fs;
    }

    /**
     * Returns all sets currently available to the User
     *
     * @param bool|User|\Concrete\Core\User\UserInfo $user
     *
     * @return static[]
     */
    public static function getMySets($user = false)
    {
        $app = Facade::getFacadeApplication();

        if ($user === false) {
            $user = $app->make(User::class);
        }

        /** @var $database \Concrete\Core\Database\Connection\Connection */
        $database = $app->make('database')->connection();
        $fileSets = array();

        $queryBuilder = $database->createQueryBuilder();
        $results = $queryBuilder->select('*')->from('FileSets')->where(
            $queryBuilder->expr()->eq('fsType', self::TYPE_PUBLIC)
            )->orWhere(
                $queryBuilder->expr()->andX(
                    $queryBuilder->expr()->in('fsType',[self::TYPE_PRIVATE, self::TYPE_STARRED, self::TYPE_PUBLIC]),
                    $queryBuilder->expr()->eq('uID', $user->getUserID())
                )
            )->orderBy('fsName', 'ASC')->execute();


        while ($row = $results->fetch()) {
            $fileSet = new static();
            $fileSet = array_to_object($fileSet, $row);
            $fileSets[] = $fileSet;
        }

        return $fileSets;
    }

    /**
     * Returns all sets (except saved searches) for a User
     *
     * @param bool|User|\Concrete\Core\User\UserInfo $user User or UserInfo Object
     *
     * @return array
     */
    public static function getOwnedSets($user = false)
    {

        $app = Facade::getFacadeApplication();

        if ($user === false) {
            $user = $app->make(User::class);
        }

        /** @var \Concrete\Core\Database\Connection\Connection $database */
        $database = $app->make('database')->connection();
        $fileSets = array();

        $queryBuilder = $database->createQueryBuilder();
        $results = $queryBuilder->select('*')->from('FileSets')->where(
            $queryBuilder->expr()->in('fsType',[self::TYPE_PRIVATE, self::TYPE_STARRED, self::TYPE_PUBLIC])
        )->andWhere($queryBuilder->expr()->eq('uID', $user->getUserID()))->execute();


        while ($row = $results->fetch()) {
            $fileSet = new static();
            $fileSet = array_to_object($fileSet, $row);
            $fileSets[] = $fileSet;
        }

        return $fileSets;

    }

    /**
     * Creats a new fileset if set doesn't exists.
     *
     * If we find a multiple groups with the same properties,
     * we return an array containing each group
     *
     * @param string $fs_name
     * @param int    $fs_type
     * @param int|bool    $fs_uid
     *
     * @return mixed
     *
     * Dev Note: This will create duplicate sets with the same name if a set exists owned by another user!!!
     */
    public static function createAndGetSet($fs_name, $fs_type, $fs_uid = false)
    {
        $app = Facade::getFacadeApplication();
        if ($fs_uid === false) {
            $u = $app->make(User::class);
            $fs_uid = $u->uID;
        }

        $db = Database::connection();
        $criteria = array($fs_name, $fs_type, $fs_uid);
        $fsID = $db->fetchColumn('SELECT fsID FROM FileSets WHERE fsName=? AND fsType=? AND uID=?', $criteria);
        if ($fsID > 0) {
            return static::getByID($fsID);
        } else {
            $fs = static::create($fs_name, 0, $fs_uid, $fs_type);

            return $fs;
        }
    }

    /**
     * Get a file set object by a file set's id.
     *
     * @param int $fsID
     *
     * @return Set
     */
    public static function getByID($fsID)
    {
        $db = Database::connection();
        $row = $db->fetchAssoc('SELECT * FROM FileSets WHERE fsID = ?', array($fsID));
        if (is_array($row)) {
            $fs = new static();
            $fs = array_to_object($fs, $row);
            if ($row['fsType'] == static::TYPE_SAVED_SEARCH) {
                $row2 = $db->GetRow(
                    'SELECT fsSearchRequest, fsResultColumns FROM FileSetSavedSearches WHERE fsID = ?',
                    array($fsID));
                $fs->fsSearchRequest = @unserialize($row2['fsSearchRequest']);
                $fs->fsResultColumns = @unserialize($row2['fsResultColumns']);
            }

            return $fs;
        }
    }

    public static function __callStatic($name, $arguments)
    {
        if (strcasecmp($name, 'add') === 0) {
            return call_user_func_array('static::create', $arguments);
        }
        trigger_error("Call to undefined method ".__CLASS__."::$name()", E_USER_ERROR);
    }

    /**
     * Adds a File set.
     *
     * @param string $setName
     * @param int $fsOverrideGlobalPermissions
     * @param bool|\User $u
     * @param int $type
     *
     * @return Set
     */
    public static function create($setName, $fsOverrideGlobalPermissions = 0, $u = false, $type = self::TYPE_PUBLIC)
    {
        if (is_object($u) && $u->isRegistered()) {
            $uID = $u->getUserID();
        } else {
            if ($u) {
                $uID = $u;
            } else {
                $uID = 0;
            }
        }

        $db = Database::connection();
        $db->insert(
            "FileSets",
            array(
                'fsType' => $type,
                'uID' => $uID,
                'fsName' => $setName,
            )
        );
        $fsID = $db->lastInsertId();
        $fs = static::getByID($fsID);

        $fe = new \Concrete\Core\File\Event\FileSet($fs);
        Events::dispatch('on_file_set_add', $fe);

        return $fs;
    }

    /**
     * Static method to return an array of File objects by the set id.
     *
     * @param  int $fsID
     *
     * @return array|void
     */
    public static function getFilesBySetID($fsID)
    {
        if (intval($fsID) > 0) {
            $fileset = self::getByID($fsID);
            if ($fileset instanceof \Concrete\Core\File\Set\Set) {
                return $fileset->getFiles();
            }
        }
    }

    /**
     * Static method to return an array of File objects by the set name.
     *
     * @param  string   $fsName
     * @param  int|bool $uID
     *
     * @return array|void
     */
    public static function getFilesBySetName($fsName, $uID = false)
    {
        if (!empty($fsName)) {
            $fileset = self::getByName($fsName, $uID);
            if ($fileset instanceof \Concrete\Core\File\Set\Set) {
                return $fileset->getFiles();
            }
        }
    }

    /**
     * Get a file set object by a file name.
     *
     * @param  string   $fsName
     * @param  int|bool $uID
     *
     * @return Set
     */
    public static function getByName($fsName, $uID = false)
    {
        $db = Database::connection();
        if ($uID !== false) {
            $row = $db->fetchAssoc('SELECT * FROM FileSets WHERE fsName = ? AND uID = ?', array($fsName, $uID));
        } else {
            $row = $db->fetchAssoc('SELECT * FROM FileSets WHERE fsName = ?', array($fsName));
        }
        if (is_array($row) && count($row)) {
            $fs = new static();
            $fs = array_to_object($fs, $row);

            return $fs;
        }
    }

    /**
     * Returns an array of File objects from the current set.
     *
     * @return ConcreteFile[]
     */
    public function getFiles()
    {
        if (!$this->fileSetFiles) {
            $this->populateFiles();
        }
        $files = array();
        foreach ($this->fileSetFiles as $file) {
            $files[] = ConcreteFile::getByID($file->fID);
        }

        return $files;
    }

    /**
     * Get a list of files associated with this set.
     *
     * Can obsolete this when we get version of ADOdB with one/many support
     */
    private function populateFiles()
    {
        $this->fileSetFiles = File::getFileSetFiles($this);
    }

    /**
     * @return int
     */
    public function getFileSetUserID()
    {
        return $this->uID;
    }

    /**
     * @return int
     */
    public function getFileSetType()
    {
        return $this->fsType;
    }

    public function getSavedSearches()
    {
        $app = Facade::getFacadeApplication();
        $db = Database::connection();
        $sets = array();
        $u = $app->make(User::class);
        $r = $db->executeQuery(
            'SELECT * FROM FileSets WHERE fsType = ? AND uID = ? ORDER BY fsName ASC',
            array(self::TYPE_SAVED_SEARCH, $u->getUserID())
        );
        while ($row = $r->fetch()) {
            $fs = new static();
            $fs = array_to_object($fs, $row);
            $sets[] = $fs;
        }

        return $sets;
    }

    /**
     * @return int
     */
    public function getFileSetID()
    {
        if ($this->fsID) {
            return $this->fsID;
        }

        return 0;
    }

    /**
     * @param array $files Array of file IDs
     */
    public function updateFileSetDisplayOrder($files)
    {
        $db = Database::connection();
        $db->executeQuery('UPDATE FileSetFiles SET fsDisplayOrder = 0 WHERE fsID = ?', array($this->getFileSetID()));
        $i = 0;
        if (is_array($files)) {
            foreach ($files as $fID) {
                $db->executeQuery(
                    'UPDATE FileSetFiles SET fsDisplayOrder = ? WHERE fsID = ? AND fID = ?',
                    array($i, $this->getFileSetID(), $fID)
                );
                ++$i;
            }
        }
    }

    /**
     * @return int
     */
    public function overrideGlobalPermissions()
    {
        return false;
    }

    /**
     * @return string
     */
    public function getFileSetName()
    {
        return $this->fsName;
    }

    /**
     * Returns the display name for this file set (localized and escaped accordingly to $format).
     *
     * @param string $format
     *
     * @return string
     */
    public function getFileSetDisplayName($format = 'html')
    {
        $value = tc('FileSetName', $this->getFileSetName());
        switch ($format) {
            case 'html':
                return h($value);
            case 'text':
            default:
            return $value;
        }
    }

    /**
     * Updates a file set.
     *
     * @return Set
     */
    public function update($setName)
    {
        $db = Database::connection();
        $db->update(
            'FileSets',
            array('fsName' => $setName),
            array('fsID' => $this->fsID)
        );

        return static::getByID($this->fsID);
    }

    /**
     * Adds the file to the set.
     *
     * @param int|FileEntity|FileVersionEntity $f_id
     *
     * @return \Concrete\Core\File\Set\File|null returns NULL if the operation failed (for instance because $f_id is invalid), a \Concrete\Core\File\Set\File instance otherwise  
     */
    public function addFileToSet($f_id)
    {
        $app = Application::getFacadeApplication();
        if (is_object($f_id)) {
            $f = $f_id;
            if ($f instanceof FileEntity) {
                $file = $f;
                $fileVersion = $file->getApprovedVersion();
            } else {
                $fileVersion = $f;
                $file = $fileVersion->getFile();
            }
            $f_id = (int) $file->getFileID();
        } else {
            $f_id = (int) $f_id;
            $em = $app->make(EntityManagerInterface::class);
            $file = $em->find(FileEntity::class, $f_id);
            $fileVersion = $file->getApprovedVersion();
        }
        if ($file === null) {
            $result = null;
        } else {
            $file_set_file = File::createAndGetFile($f_id, $this->fsID);
            $fe = new \Concrete\Core\File\Event\FileSetFile($file_set_file);
            $director = $app->make(EventDispatcherInterface::class);
            $director->dispatch('on_file_added_to_set', $fe);
            if ($fileVersion !== null && $this->shouldRefreshFileThumbnails('add')) {
                $fileVersion->refreshThumbnails(false);
            }
            $result = $file_set_file;
        }

        return $result;
    }

    public function getSavedSearchRequest()
    {
        return $this->fsSearchRequest;
    }

    public function getSavedSearchColumns()
    {
        return $this->fsResultColumns;
    }

    /**
     * @param int|FileEntity|FileVersionEntity $f_id
     *
     * @return bool Returns false if the operation failed (for instance because $f_id is invalid), true otherwise
     */
    public function removeFileFromSet($f_id)
    {
        $app = Application::getFacadeApplication();
        if (is_object($f_id)) {
            $f = $f_id;
            if ($f instanceof FileEntity) {
                $file = $f;
                $fileVersion = $file->getApprovedVersion();
            } else {
                $fileVersion = $f;
                $file = $fileVersion->getFile();
            }
            $f_id = (int) $file->getFileID();
        } else {
            $f_id = (int) $f_id;
            $em = $app->make(EntityManagerInterface::class);
            $file = $em->find(FileEntity::class, $f_id);
            $fileVersion = $file->getApprovedVersion();
        }
        if ($file === null) {
            $result = false;
        } else {
            $file_set_file = File::createAndGetFile($f_id, $this->fsID);
            $db = $app->make(Connection::class);
            $db->executeQuery(
                'DELETE FROM FileSetFiles WHERE fID = ? AND fsID = ?',
                [$f_id, $this->getFileSetID()]
            );
            $fe = new \Concrete\Core\File\Event\FileSetFile($file_set_file);
            $director = $app->make(EventDispatcherInterface::class);
            $director->dispatch('on_file_removed_from_set', $fe);
            if ($fileVersion !== null && $this->shouldRefreshFileThumbnails('remove')) {
                $fileVersion->refreshThumbnails(false);
            }
            $result = true;
        }

        return $result;
    }

    public function hasFileID($f_id)
    {
        if (!is_array($this->fileSetFiles)) {
            $this->populateFiles();
        }
        foreach ($this->fileSetFiles as $file) {
            if ($file->fID == $f_id) {
                return true;
            }
        }
    }

    public function delete()
    {
        $fe = new \Concrete\Core\File\Event\FileSet($this);
        Events::dispatch('on_file_set_delete', $fe);

        $db = Database::connection();
        $db->delete('FileSets', array('fsID' => $this->fsID));
        $db->executeQuery('DELETE FROM FileSetSavedSearches WHERE fsID = ?', array($this->fsID));
        $db->executeQuery('DELETE FROM FileSetFiles WHERE fsID = ?', array($this->fsID));
        $db->executeQuery('DELETE FROM FileImageThumbnailTypeFileSets WHERE ftfsFileSetID = ?', [$this->fsID]);
    }

    /*
    public function acquireBaseFileSetPermissions()
    {
        $this->resetPermissions();

        $db = Database::connection();

        $q = "SELECT fsID, paID, pkID FROM FileSetPermissionAssignments WHERE fsID = 0";
        $r = $db->query($q);
        while ($row = $r->fetch()) {
            $v = array($this->fsID, $row['paID'], $row['pkID']);
            $q = "INSERT INTO FileSetPermissionAssignments (fsID, paID, pkID) VALUES (?, ?, ?)";
            $db->executeQuery($q, $v);
        }
    }

    public function resetPermissions()
    {
        $db = Database::connection();
        $db->executeQuery('DELETE FROM FileSetPermissionAssignments WHERE fsID = ?', array($this->fsID));
    }

    public function assignPermissions(
        $userOrGroup,
        $permissions = array(),
        $accessType = FileSetPermissionKey::ACCESS_TYPE_INCLUDE
    ) {
        $db = Database::connection();
        if ($this->fsID > 0) {
            $db->executeQuery("UPDATE FileSets SET fsOverrideGlobalPermissions = 1 WHERE fsID = ?", array($this->fsID));
            $this->fsOverrideGlobalPermissions = true;
        }

        if (is_array($userOrGroup)) {
            $pe = GroupCombinationPermissionAccessEntity::getOrCreate($userOrGroup);
            // group combination
        } else {
            if ($userOrGroup instanceof User || $userOrGroup instanceof \Concrete\Core\User\UserInfo) {
                $pe = UserPermissionAccessEntity::getOrCreate($userOrGroup);
            } else {
                // group;
                $pe = GroupPermissionAccessEntity::getOrCreate($userOrGroup);
            }
        }

        foreach ($permissions as $pkHandle) {
            $pk = PermissionKey::getByHandle($pkHandle);
            $pk->setPermissionObject($this);
            $pa = $pk->getPermissionAccessObject();
            if (!is_object($pa)) {
                $pa = PermissionAccess::create($pk);
            } else {
                if ($pa->isPermissionAccessInUse()) {
                    $pa = $pa->duplicate();
                }
            }
            $pa->addListItem($pe, false, $accessType);
            $pt = $pk->getPermissionAssignmentObject();
            $pt->assignPermissionAccess($pa);
        }
    }

    */

    public function getJSONObject()
    {
        $r = new \stdClass();
        $r->fsName = $this->getFileSetName();
        $r->fsDisplayName = $this->getFileSetDisplayName();
        $r->fsID = $this->getFileSetID();

        return $r;
    }

    /**
     * @deprecated
     */
    public function getPermissionResponseClassName()
    {
        return '\\Concrete\\Core\\Permission\\Response\\FileSetResponse';
    }

    /**
     * @deprecated
     */
    public function getPermissionObjectKeyCategoryHandle()
    {
        return 'file_set';
    }

    /**
     * @deprecated
     */
    public function getPermissionObjectIdentifier()
    {
        return $this->getFileSetID();
    }

    /**
     * Check if we should build the thumbnails for files added or removed to this file set should.
     * 
     * @param string $fileOperation 'add' or 'remove'
     *
     * @return bool
     */
    protected function shouldRefreshFileThumbnails($fileOperation)
    {
        $app = Application::getFacadeApplication();
        $em = $app->make(EntityManagerInterface::class);
        $qb = $em->createQueryBuilder();
        $qb
            ->select('ft.ftTypeID')
            ->from(ThumbnailType::class, 'ft')
            ->innerJoin('ft.ftAssociatedFileSets', 'ftfs')
            ->andWhere($qb->expr()->eq('ftfs.ftfsFileSetID', ':fsID'))
            ->setParameter('fsID', $this->getFileSetID())
            ->andWhere($qb->expr()->eq('ft.ftLimitedToFileSets', ':limitedTo'))
            ->setParameter('limitedTo', $fileOperation === 'add')
            ->setMaxResults(1)
        ;
        $query = $qb->getQuery();

        return $query->getOneOrNullResult($query::HYDRATE_SINGLE_SCALAR) !== null;
    }
}
