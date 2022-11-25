<?php

namespace C5TL\Parser\DynamicItem;

/**
 * Extract translatable data from PermissionAccessEntityTypes.
 */
class PermissionAccessEntityType extends DynamicItem
{
    /**
     * {@inheritdoc}
     *
     * @see \C5TL\Parser\DynamicItem\DynamicItem::getParsedItemNames()
     */
    public function getParsedItemNames()
    {
        return function_exists('t') ? t('Access entity type names') : 'Access entity type names';
    }

    /**
     * {@inheritdoc}
     *
     * @see \C5TL\Parser\DynamicItem\DynamicItem::getClassNameForExtractor()
     */
    protected function getClassNameForExtractor()
    {
        return '\Concrete\Core\Permission\Access\Entity\Type';
    }

    /**
     * {@inheritdoc}
     *
     * @see \C5TL\Parser\DynamicItem\DynamicItem::parseManual()
     */
    public function parseManual(\Gettext\Translations $translations, $concrete5version)
    {
        if (class_exists('\PermissionAccessEntityType', true) && method_exists('\PermissionAccessEntityType', 'getList')) {
            foreach (\PermissionAccessEntityType::getList() as $aet) {
                $this->addTranslation($translations, $aet->getAccessEntityTypeName(), 'PermissionAccessEntityTypeName');
            }
        }
    }
}
