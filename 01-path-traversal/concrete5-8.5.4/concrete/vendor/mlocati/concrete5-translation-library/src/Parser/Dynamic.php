<?php

namespace C5TL\Parser;

/**
 * Extract translatable strings from block type templates.
 */
class Dynamic extends \C5TL\Parser
{
    /**
     * {@inheritdoc}
     *
     * @see \C5TL\Parser::getParserName()
     */
    public function getParserName()
    {
        return function_exists('t') ? t('Block templates') : 'Block templates';
    }

    /**
     * {@inheritdoc}
     *
     * @see \C5TL\Parser::canParseRunningConcrete5()
     */
    public function canParseRunningConcrete5()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     *
     * @see \C5TL\Parser::parseRunningConcrete5Do()
     */
    protected function parseRunningConcrete5Do(\Gettext\Translations $translations, $concrete5version, $subParsersFilter)
    {
        foreach ($this->getSubParsers() as $dynamicItemParser) {
            if ((!is_array($subParsersFilter)) || in_array($dynamicItemParser->getDynamicItemsParserHandler(), $subParsersFilter, true)) {
                $dynamicItemParser->parse($translations, $concrete5version);
            }
        }
    }

    /**
     * Returns the fully-qualified class names of all the sub-parsers.
     *
     * @return array[\C5TL\Parser\DynamicItem\DynamicItem]
     */
    public function getSubParsers()
    {
        $result = array();
        $dir = __DIR__.'/DynamicItem';
        if (is_dir($dir) && is_readable($dir)) {
            $matches = null;
            foreach (scandir($dir) as $item) {
                if (($item[0] !== '.') && preg_match('/^(.+)\.php$/i', $item, $matches) && ($matches[1] !== 'DynamicItem')) {
                    $fqClassName = '\\'.__NAMESPACE__.'\\DynamicItem\\'.$matches[1];
                    $instance = new $fqClassName();
                    /* @var $instance \C5TL\Parser\DynamicItem\DynamicItem */
                    $result[$instance->getDynamicItemsParserHandler()] = $instance;
                }
            }
        }

        return $result;
    }
}
