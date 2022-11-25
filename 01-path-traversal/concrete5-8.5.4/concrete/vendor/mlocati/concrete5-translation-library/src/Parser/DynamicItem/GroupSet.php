<?php

namespace C5TL\Parser\DynamicItem;

/**
 * Extract translatable data from GroupSets.
 */
class GroupSet extends DynamicItem
{
    /**
     * {@inheritdoc}
     *
     * @see \C5TL\Parser\DynamicItem\DynamicItem::getParsedItemNames()
     */
    public function getParsedItemNames()
    {
        return function_exists('t') ? t('User group set names') : 'User group set names';
    }

    /**
     * {@inheritdoc}
     *
     * @see \C5TL\Parser\DynamicItem\DynamicItem::getClassNameForExtractor()
     */
    protected function getClassNameForExtractor()
    {
        return '\Concrete\Core\User\Group\GroupSet';
    }

    /**
     * {@inheritdoc}
     *
     * @see \C5TL\Parser\DynamicItem\DynamicItem::parseManual()
     */
    public function parseManual(\Gettext\Translations $translations, $concrete5version)
    {
        if (class_exists('\GroupSet', true)) {
            foreach (\GroupSet::getList() as $gs) {
                $this->addTranslation($translations, $gs->getGroupSetName(), 'GroupSetName');
            }
        }
    }
}
