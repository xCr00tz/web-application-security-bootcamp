<?php
namespace Concrete\Core\Entity\Block\BlockType;

use Concrete\Core\Block\Block;
use BlockTypeSet;
use Concrete\Block\CoreStackDisplay\Controller;
use Concrete\Core\Block\BlockType\BlockTypeList;
use Concrete\Core\Block\View\BlockView;
use Concrete\Core\Database\Schema\Schema;
use Concrete\Core\Filesystem\TemplateFile;
use Concrete\Core\Package\PackageList;
use Concrete\Core\Support\Facade\Application;
use Concrete\Core\Support\Facade\Facade;
use Core;
use Database as DB;
use Environment;
use Loader;
use Localization;
use Package;
use Page;
use Concrete\Core\User\User;
use Doctrine\ORM\Mapping as ORM;
use Concrete\Core\Database\Connection\Connection;

/**
 * @ORM\Entity
 * @ORM\Table(name="BlockTypes")
 */
class BlockType
{
    public $controller;

    /**
     * @ORM\Column(type="boolean")
     */
    protected $btIgnorePageThemeGridFrameworkContainer = false;

    /**
     * @ORM\Id @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    protected $btID;
    /**
     * @ORM\Column(type="string", length=128)
     */
    protected $btHandle;
    /**
     * @ORM\Column(type="string", length=128)
     */
    protected $btName;
    /**
     * @ORM\Column(type="text")
     */
    protected $btDescription;
    /**
     * @ORM\Column(type="boolean")
     */
    protected $btCopyWhenPropagate = false;
    /**
     * @ORM\Column(type="boolean")
     */
    protected $btIncludeAll = false;
    /**
     * @ORM\Column(type="boolean")
     */
    protected $btIsInternal = false;
    /**
     * @ORM\Column(type="boolean")
     */
    protected $btSupportsInlineEdit = false;
    /**
     * @ORM\Column(type="boolean")
     */
    protected $btSupportsInlineAdd = false;

    /**
     * @ORM\Column(type="integer")
     */
    protected $btDisplayOrder = 0;

    /**
     * @ORM\Column(type="integer")
     */
    protected $btInterfaceHeight;
    /**
     * @ORM\Column(type="integer")
     */
    protected $btInterfaceWidth;

    /**
     * @ORM\Column(type="integer", options={"unsigned": true})
     */
    protected $pkgID = 0;

    public function getBlockTypeInSetName()
    {
        if ($this->controller) {
            return $this->controller->getBlockTypeInSetName();
        }
    }

    /**
     * Sets the Ignore Page Theme Gride Framework Container.
     */
    public function setBlockTypeIgnorePageThemeGridFrameworkContainer($btIgnorePageThemeGridFrameworkContainer)
    {
        $this->btIgnorePageThemeGridFrameworkContainer = $btIgnorePageThemeGridFrameworkContainer;
    }

    /**
     * Sets the block type handle.
     */
    public function setBlockTypeName($btName)
    {
        $this->btName = $btName;
    }

    /**
     * Sets the block type description.
     */
    public function setBlockTypeDescription($btDescription)
    {
        $this->btDescription = $btDescription;
    }

    /**
     * Sets the block type handle.
     */
    public function setBlockTypeHandle($btHandle)
    {
        $this->btHandle = $btHandle;
    }

    public function setPackageID($pkgID)
    {
        $this->pkgID = $pkgID;
    }

    /**
     * Determines if the block type has templates available.
     *
     * @return bool
     */
    public function hasAddTemplate()
    {
        $bv = new BlockView($this);
        $path = $bv->getBlockPath(FILENAME_BLOCK_ADD);
        if (file_exists($path . '/' . FILENAME_BLOCK_ADD)) {
            return true;
        }

        return false;
    }

    /**
     * gets the available composer templates
     * used for editing instances of the BlockType while in the composer ui in the dashboard.
     *
     * @return TemplateFile[]
     */
    public function getBlockTypeComposerTemplates()
    {
        $btHandle = $this->getBlockTypeHandle();
        $files = array();
        $fh = Loader::helper('file');
        $dir = DIR_FILES_BLOCK_TYPES . "/{$btHandle}/" . DIRNAME_BLOCK_TEMPLATES_COMPOSER;
        if (is_dir($dir)) {
            $files = array_merge($files, $fh->getDirectoryContents($dir));
        }
        foreach (PackageList::get()->getPackages() as $pkg) {
            $dir =
                (is_dir(DIR_PACKAGES . '/' . $pkg->getPackageHandle()) ? DIR_PACKAGES : DIR_PACKAGES_CORE)
                . '/' . $pkg->getPackageHandle() . '/' . DIRNAME_BLOCKS . '/' . $btHandle . '/' . DIRNAME_BLOCK_TEMPLATES_COMPOSER;
            if (is_dir($dir)) {
                $files = array_merge($files, $fh->getDirectoryContents($dir));
            }
        }
        $dir = DIR_FILES_BLOCK_TYPES_CORE . "/{$btHandle}/" . DIRNAME_BLOCK_TEMPLATES_COMPOSER;
        if (file_exists($dir)) {
            $files = array_merge($files, $fh->getDirectoryContents($dir));
        }
        $templates = array();
        foreach (array_unique($files) as $file) {
            $templates[] = new TemplateFile($this, $file);
        }

        return TemplateFile::sortTemplateFileList($templates);
    }

    /**
     * @return string
     */
    public function getBlockTypeHandle()
    {
        return $this->btHandle;
    }

    /**
     * if a the current BlockType supports inline edit or not.
     *
     * @return bool
     */
    public function supportsInlineEdit()
    {
        return $this->btSupportsInlineEdit;
    }

    /**
     * if a the current BlockType supports inline add or not.
     *
     * @return bool
     */
    public function supportsInlineAdd()
    {
        return $this->btSupportsInlineAdd;
    }

    /**
     * Returns true if the block type is internal (and therefore cannot be removed) a core block.
     *
     * @return bool
     */
    public function isInternalBlockType()
    {
        return $this->btIsInternal;
    }

    /**
     * returns the width in pixels that the block type's editing dialog will open in.
     *
     * @return int
     */
    public function getBlockTypeInterfaceWidth()
    {
        return $this->btInterfaceWidth;
    }

    /**
     * returns the height in pixels that the block type's editing dialog will open in.
     *
     * @return int
     */
    public function getBlockTypeInterfaceHeight()
    {
        return $this->btInterfaceHeight;
    }

    /**
     * If true, container classes will not be wrapped around this block type in edit mode (if the
     * theme in question supports a grid framework.
     *
     * @return bool
     */
    public function ignorePageThemeGridFrameworkContainer()
    {
        return $this->btIgnorePageThemeGridFrameworkContainer;
    }

    /**
     * returns the id of the BlockType's package if it's in a package.
     *
     * @return int
     */
    public function getPackageID()
    {
        return $this->pkgID;
    }

    /**
     * gets the BlockTypes description text.
     *
     * @return string
     */
    public function getBlockTypeDescription()
    {
        return $this->btDescription;
    }

    /**
     * @return string
     */
    public function getBlockTypeName()
    {
        return $this->btName;
    }

    /**
     * @return bool
     */
    public function isCopiedWhenPropagated()
    {
        return $this->btCopyWhenPropagate;
    }

    /**
     * If true, this block is not versioned on a page – it is included as is on all versions of the page, even when updated.
     *
     * @return bool
     */
    public function includeAll()
    {
        return $this->btIncludeAll;
    }

    /**
     * @deprecated
     */
    public function getBlockTypeClassFromHandle()
    {
        return $this->getBlockTypeClass();
    }

    /**
     * Returns the class for the current block type.
     */
    public function getBlockTypeClass()
    {
        return \Concrete\Core\Block\BlockType\BlockType::getBlockTypeMappedClass($this->getBlockTypeHandle(), $this->getPackageHandle());
    }

    /**
     * returns the handle of the BlockType's package if it's in a package.
     *
     * @return string
     */
    public function getPackageHandle()
    {
        return \Concrete\Core\Package\PackageList::getHandle($this->pkgID);
    }

    /**
     * Returns an array of all BlockTypeSet objects that this block is in.
     *
     * @return BlockTypeSet[]
     */
    public function getBlockTypeSets()
    {
        $db = Loader::db();
        $list = array();
        $r = $db->Execute(
                'select btsID from BlockTypeSetBlockTypes where btID = ? order by displayOrder asc',
                array($this->getBlockTypeID()));
        while ($row = $r->FetchRow()) {
            $list[] = BlockTypeSet::getByID($row['btsID']);
        }
        $r->Close();

        return $list;
    }

    /**
     * @return int
     */
    public function getBlockTypeID()
    {
        return $this->btID;
    }

    /**
     * Returns the number of unique instances of this block throughout the entire site
     * note - this count could include blocks in areas that are no longer rendered by the theme.
     *
     * @param bool specify true if you only want to see the number of blocks in active pages
     *
     * @return int
     */
    public function getCount($ignoreUnapprovedVersions = false)
    {
        $app = Application::getFacadeApplication();
        $db = $app->make(Connection::class);
        $now = $app->make('date')->getOverridableNow();
        if ($ignoreUnapprovedVersions) {
            $count = $db->GetOne(<<<'EOT'
SELECT
    count(btID)
FROM
    Blocks b
    INNER JOIN CollectionVersionBlocks cvb
        ON b.bID=cvb.bID
    INNER JOIN CollectionVersions cv
        ON cvb.cID=cv.cID AND cvb.cvID=cv.cvID AND cv.cvIsApproved=1 AND (cv.cvPublishDate IS NULL OR cv.cvPublishDate <= ?) AND (cv.cvPublishEndDate IS NULL OR cv.cvPublishEndDate >= ?)
WHERE
    b.btID = ?
EOT
                ,
                [$now, $now, $this->btID]
            );
        } else {
            $count = $db->GetOne("SELECT count(btID) FROM Blocks WHERE btID = ?", array($this->btID));
        }

        return $count;
    }

    /**
     * Not a permissions call. Actually checks to see whether this block is not an internal one.
     *
     * @return bool
     */
    public function canUnInstall()
    {
        return !$this->isBlockTypeInternal();
    }

    /**
     * if a the current BlockType is Internal or not - meaning one of the core built-in concrete5 blocks.
     *
     * @return bool
     */
    public function isBlockTypeInternal()
    {
        return $this->btIsInternal;
    }

    /**
     * Renders a particular view of a block type, using the public $controller variable as the block type's controller.
     *
     * @param string template 'view' for the default
     */
    public function render($view = 'view')
    {
        $bv = new BlockView($this);
        $bv->render($view);
    }

    /**
     * get's the block type controller.
     *
     * @return BlockTypeController
     */
    public function getController()
    {
        return $this->controller;
    }

    /**
     * Gets the custom templates available for the current BlockType.
     *
     * @return TemplateFile[]
     */
    public function getBlockTypeCustomTemplates(Block $b)
    {
        $btHandle = $this->getBlockTypeHandle();
        $fh = Loader::helper('file');
        $files = array();
        $dir = DIR_FILES_BLOCK_TYPES . "/{$btHandle}/" . DIRNAME_BLOCK_TEMPLATES;
        if (is_dir($dir)) {
            $files = array_merge($files, $fh->getDirectoryContents($dir));
        }

        // Next, check the current theme.
        $c = $b->getBlockCollectionObject();
        if (is_object($c)) {
            $theme = $c->getCollectionThemeObject();
            if (is_object($theme)) {
                $dir = DIR_FILES_THEMES . "/" . $theme->getThemeHandle() . "/" . DIRNAME_BLOCKS . "/" . $btHandle . "/" . DIRNAME_BLOCK_TEMPLATES;
                if (is_dir($dir)) {
                    $files = array_merge($files, $fh->getDirectoryContents($dir));
                }

                if ($theme->getPackageHandle()) {
                    $dir =
                        (is_dir(DIR_PACKAGES . '/' . $theme->getPackageHandle()) ? DIR_PACKAGES : DIR_PACKAGES_CORE)
                        . '/' . $theme->getPackageHandle() . '/' . DIRNAME_THEMES . '/' . $theme->getThemeHandle() . '/' . DIRNAME_BLOCKS . '/' . $btHandle . '/' . DIRNAME_BLOCK_TEMPLATES;
                    if (is_dir($dir)) {
                        $files = array_merge($files, $fh->getDirectoryContents($dir));
                    }
                }

                $dir = DIR_FILES_THEMES_CORE . "/" . $theme->getThemeHandle() . "/" . DIRNAME_BLOCKS . "/" . $btHandle . "/" . DIRNAME_BLOCK_TEMPLATES;
                if (is_dir($dir)) {
                    $files = array_merge($files, $fh->getDirectoryContents($dir));
                }
            }
        }
        // NOW, we check to see if this btHandle has any custom templates that have been installed as separate packages
        foreach (PackageList::get()->getPackages() as $pkg) {
            $dir =
                (is_dir(DIR_PACKAGES . '/' . $pkg->getPackageHandle()) ? DIR_PACKAGES : DIR_PACKAGES_CORE)
                . '/' . $pkg->getPackageHandle() . '/' . DIRNAME_BLOCKS . '/' . $btHandle . '/' . DIRNAME_BLOCK_TEMPLATES;
            if (is_dir($dir)) {
                $files = array_merge($files, $fh->getDirectoryContents($dir));
            }
        }
        $dir = DIR_FILES_BLOCK_TYPES_CORE . "/{$btHandle}/" . DIRNAME_BLOCK_TEMPLATES;
        if (is_dir($dir)) {
            $files = array_merge($files, $fh->getDirectoryContents($dir));
        }
        $templates = array();
        foreach (array_unique($files) as $file) {
            $templates[] = new TemplateFile($this, $file);
        }

        return TemplateFile::sortTemplateFileList($templates);
    }

    /**
     * @private
     */
    public function setBlockTypeDisplayOrder($displayOrder)
    {
        $db = Loader::db();

        $displayOrder = intval($displayOrder); //in case displayOrder came from a string (so ADODB escapes it properly)

        $sql = "UPDATE BlockTypes SET btDisplayOrder = btDisplayOrder - 1 WHERE btDisplayOrder > ?";
        $vals = array($this->btDisplayOrder);
        $db->Execute($sql, $vals);

        $sql = "UPDATE BlockTypes SET btDisplayOrder = btDisplayOrder + 1 WHERE btDisplayOrder >= ?";
        $vals = array($displayOrder);
        $db->Execute($sql, $vals);

        $sql = "UPDATE BlockTypes SET btDisplayOrder = ? WHERE btID = ?";
        $vals = array($displayOrder, $this->btID);
        $db->Execute($sql, $vals);

        // now we remove the block type from cache
        /** @var \Concrete\Core\Cache\Cache $cache */
        $cache = Core::make('cache');
        $cache->delete('blockTypeByID/' . $this->btID);
        $cache->delete('blockTypeByHandle/' . $this->btHandle);
        $cache->delete('blockTypeList');
    }

    /**
     * Get the display order of this block type when it's not assigned to any block type set.
     *
     * @return int
     */
    public function getBlockTypeDisplayOrder()
    {
        return $this->btDisplayOrder;
    }

    /**
     * refreshes the BlockType's database schema throws an Exception if error.
     */
    public function refresh()
    {
        $app = Facade::getFacadeApplication();
        $db = $app->make('database')->connection();
        $pkgHandle = false;
        if ($this->pkgID > 0) {
            $pkgHandle = $this->getPackageHandle();
        }

        $class = \Concrete\Core\Block\BlockType\BlockType::getBlockTypeMappedClass($this->btHandle, $pkgHandle);
        $bta = $app->build($class);

        $this->loadFromController($bta);

        $em = \ORM::entityManager();
        $em->persist($this);
        $em->flush();

        $env = Environment::get();
        $r = $env->getRecord(DIRNAME_BLOCKS . '/' . $this->btHandle . '/' . FILENAME_BLOCK_DB, $this->getPackageHandle());
        if ($r->exists()) {
            $parser = Schema::getSchemaParser(simplexml_load_file($r->file));
            $parser->setIgnoreExistingTables(false);
            $toSchema = $parser->parse($db);

            $fromSchema = $db->getSchemaManager()->createSchema();
            $comparator = new \Doctrine\DBAL\Schema\Comparator();
            $schemaDiff = $comparator->compare($fromSchema, $toSchema);
            $saveQueries = $schemaDiff->toSaveSql($db->getDatabasePlatform());
            foreach ($saveQueries as $query) {
                $db->query($query);
            }
        }
    }

    public function loadFromController($bta)
    {
        $this->btName = $bta->getBlockTypeName();
        $this->btDescription = $bta->getBlockTypeDescription();
        $this->btCopyWhenPropagate = $bta->isCopiedWhenPropagated();
        $this->btIncludeAll = $bta->includeAll();
        $this->btIsInternal = $bta->isBlockTypeInternal();
        $this->btSupportsInlineEdit = $bta->supportsInlineEdit();
        $this->btSupportsInlineAdd = $bta->supportsInlineAdd();
        $this->btIgnorePageThemeGridFrameworkContainer = $bta->ignorePageThemeGridFrameworkContainer();
        $this->btInterfaceHeight = $bta->getInterfaceHeight();
        $this->btInterfaceWidth = $bta->getInterfaceWidth();
    }

    /**
     * Removes the block type. Also removes instances of content.
     */
    public function delete()
    {
        $db = Loader::db();
        $r = $db->Execute(
                'select cID, cvID, b.bID, arHandle
                from CollectionVersionBlocks cvb
                    inner join Blocks b on b.bID  = cvb.bID
                where btID = ?
                union
                select cID, cvID, cvb.bID, arHandle
                from CollectionVersionBlocks cvb
                    inner join btCoreScrapbookDisplay btCSD on cvb.bID = btCSD.bID
                    inner join Blocks b on b.bID = btCSD.bOriginalID
                where btID = ?',
                array($this->getBlockTypeID(), $this->getBlockTypeID()));
        while ($row = $r->FetchRow()) {
            $nc = Page::getByID($row['cID'], $row['cvID']);
            if (!is_object($nc) || $nc->isError()) {
                continue;
            }
            $b = Block::getByID($row['bID'], $nc, $row['arHandle']);
            if (is_object($b)) {
                $b->deleteBlock();
            }
        }

        $em = \ORM::entityManager();
        $em->remove($this);
        $em->flush();

        //Remove gaps in display order numbering (to avoid future sorting errors)
        BlockTypeList::resetBlockTypeDisplayOrder('btDisplayOrder');
    }

    /**
     * Adds a block to the system without adding it to a collection.
     * Passes page and area data along if it is available, however.
     *
     * @param mixed            $data
     * @param bool|\Collection $c
     * @param bool|\Area       $a
     *
     * @return bool|\Concrete\Core\Block\Block
     */
    public function add($data, $c = false, $a = false)
    {
        $app = Facade::getFacadeApplication();
        $db = $app->make('database')->connection();

        $u = $app->make(User::class);
        if (isset($data['uID'])) {
            $uID = $data['uID'];
        } else {
            $uID = $u->getUserID();
        }
        $bName = '';
        if (isset($data['bName'])) {
            $bName = $data['bName'];
        }

        $btID = $this->btID;
        $dh = $app->make('helper/date');
        $bDate = $dh->getOverridableNow();
        $bIsActive = (isset($this->btActiveWhenAdded) && $this->btActiveWhenAdded == 1) ? 1 : 0;

        $v = array($bName, $bDate, $bDate, $bIsActive, $btID, $uID);
        $q = "insert into Blocks (bName, bDateAdded, bDateModified, bIsActive, btID, uID) values (?, ?, ?, ?, ?, ?)";

        $res = $db->executeQuery($q, $v);

        // we get the block object for the block we just added
        if ($res) {
            $bIDnew = $db->lastInsertId();

            $nb = Block::getByID($bIDnew);
            if (is_object($c)) {
                $nb->setBlockCollectionObject($c);
            }
            if (is_object($a)) {
                $nb->setBlockAreaObject($a);
            }
            $class = $this->getBlockTypeClass();
            $bc = $app->build($class, [$nb]);
            $bc->save($data);

            return Block::getByID($bIDnew);
        }
    }

    /**
     * Loads controller.
     */
    public function loadController()
    {
        $class = $this->getBlockTypeClass();

        /** @var Controller controller */
        if ($class) {
            $this->controller = Facade::getFacadeApplication()->build($class, [$this]);
        }
    }
}
