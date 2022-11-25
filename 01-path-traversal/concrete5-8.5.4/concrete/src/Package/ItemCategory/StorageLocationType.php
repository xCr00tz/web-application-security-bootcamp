<?php

namespace Concrete\Core\Package\ItemCategory;

use Concrete\Core\Entity\Package;
use Concrete\Core\File\StorageLocation\Type\Type;

defined('C5_EXECUTE') or die('Access Denied.');

class StorageLocationType extends AbstractCategory
{
    public function getItemCategoryDisplayName()
    {
        return t('Storage Locations');
    }

    public function getItemName($location)
    {
        return $location->getName();
    }

    public function getPackageItems(Package $package)
    {
        return Type::getListByPackage($package);
    }
}
