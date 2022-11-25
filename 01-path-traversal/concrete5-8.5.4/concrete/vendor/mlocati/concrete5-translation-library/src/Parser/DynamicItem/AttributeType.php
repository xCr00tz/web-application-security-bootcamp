<?php

namespace C5TL\Parser\DynamicItem;

/**
 * Extract translatable data from AttributeTypes.
 */
class AttributeType extends DynamicItem
{
    /**
     * {@inheritdoc}
     *
     * @see \C5TL\Parser\DynamicItem\DynamicItem::getParsedItemNames()
     */
    public function getParsedItemNames()
    {
        return function_exists('t') ? t('Attribute type names') : 'Attribute type names';
    }

    /**
     * {@inheritdoc}
     *
     * @see \C5TL\Parser\DynamicItem\DynamicItem::getClassNameForExtractor()
     */
    protected function getClassNameForExtractor()
    {
        return '\Concrete\Core\Attribute\Type';
    }

    /**
     * {@inheritdoc}
     *
     * @see \C5TL\Parser\DynamicItem\DynamicItem::parseManual()
     */
    public function parseManual(\Gettext\Translations $translations, $concrete5version)
    {
        if (class_exists('\AttributeType', true)) {
            foreach (\AttributeType::getList() as $at) {
                $this->addTranslation($translations, $at->getAttributeTypeName(), 'AttributeTypeName');
            }
        }
    }
}
