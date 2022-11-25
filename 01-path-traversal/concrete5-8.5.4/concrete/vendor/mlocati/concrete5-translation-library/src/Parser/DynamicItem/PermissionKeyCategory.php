<?php

namespace C5TL\Parser\DynamicItem;

/**
 * Extract translatable data from PermissionKeyCategories.
 */
class PermissionKeyCategory extends DynamicItem
{
    /**
     * {@inheritdoc}
     *
     * @see \C5TL\Parser\DynamicItem\DynamicItem::getParsedItemNames()
     */
    public function getParsedItemNames()
    {
        return function_exists('t') ? t('Permission key category names') : 'Permission key category names';
    }

    /**
     * {@inheritdoc}
     *
     * @see \C5TL\Parser\DynamicItem\DynamicItem::getClassNameForExtractor()
     */
    protected function getClassNameForExtractor()
    {
        return '\Concrete\Core\Permission\Category';
    }

    /**
     * {@inheritdoc}
     *
     * @see \C5TL\Parser\DynamicItem\DynamicItem::parseManual()
     */
    public function parseManual(\Gettext\Translations $translations, $concrete5version)
    {
        $pkcNameMap = array(
            'page' => 'Page',
            'single_page' => 'Single page',
            'stack' => 'Stack',
            'composer_page' => 'Composer page',
            'user' => 'User',
            'file_set' => 'File set',
            'file' => 'File',
            'area' => 'Area',
            'block_type' => 'Block type',
            'block' => 'Block',
            'admin' => 'Administration',
            'sitemap' => 'Site map',
            'marketplace_newsflow' => 'MarketPlace newsflow',
            'basic_workflow' => 'Basic workflow',
        );
        if (version_compare($concrete5version, '5.7') < 0) {
            $pkcClass = '\PermissionKeyCategory';
        } else {
            $pkcClass = '\Concrete\Core\Permission\Category';
        }
        if (class_exists($pkcClass, true) && method_exists($pkcClass, 'getList')) {
            foreach (call_user_func($pkcClass.'::getList') as $pkc) {
                $pkcHandle = $pkc->getPermissionKeyCategoryHandle();
                $this->addTranslation($translations, isset($pkcNameMap[$pkcHandle]) ? $pkcNameMap[$pkcHandle] : ucwords(str_replace(array('_', '-', '/'), ' ', $pkcHandle)));
            }
        }
    }
}
