<?php

namespace C5TL\Parser\DynamicItem;

/**
 * Extract translatable data from JobSets.
 */
class JobSet extends DynamicItem
{
    /**
     * {@inheritdoc}
     *
     * @see \C5TL\Parser\DynamicItem\DynamicItem::getParsedItemNames()
     */
    public function getParsedItemNames()
    {
        return function_exists('t') ? t('Job set names') : 'Job set names';
    }

    /**
     * {@inheritdoc}
     *
     * @see \C5TL\Parser\DynamicItem\DynamicItem::getClassNameForExtractor()
     */
    protected function getClassNameForExtractor()
    {
        return '\Concrete\Core\Job\Set';
    }

    /**
     * {@inheritdoc}
     *
     * @see \C5TL\Parser\DynamicItem\DynamicItem::parseManual()
     */
    public function parseManual(\Gettext\Translations $translations, $concrete5version)
    {
        if (class_exists('\JobSet', true)) {
            foreach (\JobSet::getList() as $js) {
                $this->addTranslation($translations, $js->getJobSetName(), 'JobSetName');
            }
        }
    }
}
