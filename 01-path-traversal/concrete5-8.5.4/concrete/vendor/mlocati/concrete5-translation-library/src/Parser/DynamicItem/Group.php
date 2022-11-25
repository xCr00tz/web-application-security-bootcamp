<?php

namespace C5TL\Parser\DynamicItem;

/**
 * Extract translatable data from Groups.
 */
class Group extends DynamicItem
{
    /**
     * {@inheritdoc}
     *
     * @see \C5TL\Parser\DynamicItem\DynamicItem::getParsedItemNames()
     */
    public function getParsedItemNames()
    {
        return function_exists('t') ? t('User group names and descriptions') : 'User group names and descriptions';
    }

    /**
     * {@inheritdoc}
     *
     * @see \C5TL\Parser\DynamicItem\DynamicItem::getClassNameForExtractor()
     */
    protected function getClassNameForExtractor()
    {
        return '\Concrete\Core\User\Group\Group';
    }

    /**
     * {@inheritdoc}
     *
     * @see \C5TL\Parser\DynamicItem\DynamicItem::parseManual()
     */
    public function parseManual(\Gettext\Translations $translations, $concrete5version)
    {
        if (class_exists('\GroupList', true)) {
            $gl = new \GroupList(null, false, true);
            foreach ($gl->getGroupList() as $g) {
                $this->addTranslation($translations, $g->getGroupName(), 'GroupName');
                $this->addTranslation($translations, $g->getGroupDescription(), 'GroupDescription');
            }
        }
    }
}
