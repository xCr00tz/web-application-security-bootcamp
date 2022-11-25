<?php

namespace Concrete\Core\Package\ItemCategory;

use Concrete\Core\Entity\Package;
use Concrete\Core\Workflow\Type;

defined('C5_EXECUTE') or die('Access Denied.');

class WorkflowType extends AbstractCategory
{
    public function getItemCategoryDisplayName()
    {
        return t('Workflow Types');
    }

    public function getItemName($type)
    {
        return $type->getWorkflowTypeName();
    }

    public function getPackageItems(Package $package)
    {
        return Type::getListByPackage($package);
    }
}
