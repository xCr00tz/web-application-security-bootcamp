<?php

namespace C5TL\Parser\DynamicItem;

/**
 * Extract translatable data from SelectAttributeValues.
 */
class SelectAttributeValue extends DynamicItem
{
    /**
     * {@inheritdoc}
     *
     * @see \C5TL\Parser\DynamicItem\DynamicItem::getParsedItemNames()
     */
    public function getParsedItemNames()
    {
        return function_exists('t') ? t('Values of the select attributes') : 'Values of the select attributes';
    }

    /**
     * {@inheritdoc}
     *
     * @see \C5TL\Parser\DynamicItem\DynamicItem::getClassNameForExtractor()
     */
    protected function getClassNameForExtractor()
    {
        return '\Concrete\Attribute\Select\Option';
    }

    /**
     * {@inheritdoc}
     *
     * @see \C5TL\Parser\DynamicItem\DynamicItem::parseManual()
     */
    public function parseManual(\Gettext\Translations $translations, $concrete5version)
    {
        if (class_exists('\AttributeKeyCategory', true) && class_exists('\AttributeKey', true) && class_exists('\AttributeType', true)) {
            foreach (\AttributeKeyCategory::getList() as $akc) {
                $akcHandle = $akc->getAttributeKeyCategoryHandle();
                foreach (\AttributeKey::getList($akcHandle) as $ak) {
                    if ($ak->getAttributeType()->getAttributeTypeHandle() === 'select') {
                        foreach ($ak->getController()->getOptions() as $option) {
                            $this->addTranslation($translations, $option->getSelectAttributeOptionValue(false), 'SelectAttributeValue');
                        }
                    }
                }
            }
        }
    }
}
