<?php

namespace C5TL\Parser\DynamicItem;

/**
 * Extract translatable data from AttributeKeys.
 */
class AttributeKey extends DynamicItem
{
    /**
     * {@inheritdoc}
     *
     * @see \C5TL\Parser\DynamicItem\DynamicItem::getParsedItemNames()
     */
    public function getParsedItemNames()
    {
        return function_exists('t') ? t('Attribute names') : 'Attribute names';
    }

    /**
     * {@inheritdoc}
     *
     * @see \C5TL\Parser\DynamicItem\DynamicItem::getClassNameForExtractor()
     */
    protected function getClassNameForExtractor()
    {
        return '\Concrete\Core\Attribute\Key\Key';
    }

    /**
     * {@inheritdoc}
     *
     * @see \C5TL\Parser\DynamicItem\DynamicItem::parseManual()
     */
    public function parseManual(\Gettext\Translations $translations, $concrete5version)
    {
        if (class_exists('\AttributeKeyCategory', true) && class_exists('\AttributeKey', true)) {
            foreach (\AttributeKeyCategory::getList() as $akc) {
                foreach (\AttributeKey::getList($akc->getAttributeKeyCategoryHandle()) as $ak) {
                    $this->addTranslation($translations, $ak->getAttributeKeyName(), 'AttributeKeyName');
                }
            }
        }
    }
}
