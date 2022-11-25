<?php

namespace C5TL\Parser;

/**
 * Extract translatable strings from block type templates.
 */
class BlockTemplates extends \C5TL\Parser
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
     * @see \C5TL\Parser::canParseDirectory()
     */
    public function canParseDirectory()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     *
     * @see \C5TL\Parser::parseDirectoryDo()
     */
    protected function parseDirectoryDo(\Gettext\Translations $translations, $rootDirectory, $relativePath, $subParsersFilter, $exclude3rdParty)
    {
        $templateHandles = array();
        $prefix = ($relativePath === '') ? '' : "$relativePath/";
        $matches = null;
        foreach (array_merge(array(''), $this->getDirectoryStructure($rootDirectory, $exclude3rdParty)) as $child) {
            $shownChild = ($child === '') ? rtrim($prefix, '/') : ($prefix.$child);
            $fullpath = ($child === '') ? $rootDirectory : "$rootDirectory/$child";
            if (preg_match('%(?:^|/)blocks/\w+/(?:templates|composer)/(\w+)$%', $fullpath, $matches)) {
                if (!isset($templateHandles[$matches[1]])) {
                    $templateHandles[$matches[1]] = array();
                }
                $templateHandles[$matches[1]][] = $shownChild;
            } elseif (preg_match('%(^|/)blocks/\w+/(?:templates|composer)$%', $fullpath)) {
                $contents = @scandir($fullpath);
                if ($contents === false) {
                    throw new \Exception("Unable to parse directory $fullpath");
                }
                foreach ($contents as $file) {
                    if ($file[0] !== '.') {
                        if (preg_match('/^(.*)\.php$/', $file, $matches) && is_file("$fullpath/$file")) {
                            if (!isset($templateHandles[$matches[1]])) {
                                $templateHandles[$matches[1]] = array();
                            }
                            $templateHandles[$matches[1]][] = $shownChild."/$file";
                        }
                    }
                }
            }
        }
        foreach ($templateHandles as $templateHandle => $references) {
            $translation = $translations->insert('TemplateFileName', ucwords(str_replace(array('_', '-', '/'), ' ', $templateHandle)));
            foreach ($references as $reference) {
                $translation->addReference($reference);
            }
        }
    }
}
