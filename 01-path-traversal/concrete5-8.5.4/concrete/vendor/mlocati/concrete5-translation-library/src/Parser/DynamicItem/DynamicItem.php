<?php

namespace C5TL\Parser\DynamicItem;

/**
 * Base class for all DynamicItem parsers.
 */
abstract class DynamicItem
{
    /**
     * Return a short name of the items extracted by this DynamicItem.
     */
    abstract public function getParsedItemNames();

    /**
     * Extract specific items from the running concrete5.
     *
     * @param \Gettext\Translations $translations     Found translations will be appended here
     * @param string                $concrete5version The version of the running concrete5 instance
     */
    final public function parse(\Gettext\Translations $translations, $concrete5version)
    {
        $fqClassName = $this->getClassNameForExtractor();
        if (is_string($fqClassName) && ($fqClassName !== '') && class_exists($fqClassName, true) && method_exists($fqClassName, 'exportTranslations')) {
            $translations->mergeWith(call_user_func($fqClassName.'::exportTranslations'));
        } else {
            $this->parseManual($translations, $concrete5version);
        }
    }

    /**
     * Returns the fully qualified class name that extracts automatically strings.
     *
     * @return string
     */
    protected function getClassNameForExtractor()
    {
        return '';
    }

    /**
     * Manual parsing of items.
     *
     * @param \Gettext\Translations $translations     Found translations will be appended here
     * @param string                $concrete5version The version of the running concrete5 instance
     */
    protected function parseManual(\Gettext\Translations $translations, $concrete5version)
    {
    }

    /**
     * Adds a translation to the \Gettext\Translations object.
     *
     * @param \Gettext\Translations $translations
     * @param string                $string
     * @param string                $context
     */
    final protected function addTranslation(\Gettext\Translations $translations, $string, $context = '')
    {
        if (is_string($string) && ($string !== '')) {
            $translations->insert($context, $string);
        }
    }

    /**
     * Returns the handle of the DynamicItem handle.
     */
    final public function getDynamicItemsParserHandler()
    {
        $chunks = explode('\\', get_class($this));

        return \C5TL\Parser::handlifyString(end($chunks));
    }
}
