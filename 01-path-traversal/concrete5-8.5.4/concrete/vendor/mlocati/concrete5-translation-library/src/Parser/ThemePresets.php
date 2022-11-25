<?php

namespace C5TL\Parser;

/**
 * Extract translatable strings from themes presets.
 */
class ThemePresets extends \C5TL\Parser
{
    /**
     * {@inheritdoc}
     *
     * @see \C5TL\Parser::getParserName()
     */
    public function getParserName()
    {
        return function_exists('t') ? t('Themes presets') : 'Themes presets';
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
        $themesPresets = array();
        $prefix = ($relativePath === '') ? '' : "$relativePath/";
        $matches = null;
        foreach (array_merge(array(''), $this->getDirectoryStructure($rootDirectory, $exclude3rdParty)) as $child) {
            $presetsAbsDirectory = ($child === '') ? $rootDirectory : "$rootDirectory/$child";
            if (preg_match('%(?:^|/)themes/\w+/css/presets$%', $presetsAbsDirectory, $matches)) {
                $dirList = @scandir($presetsAbsDirectory);
                if ($dirList === false) {
                    throw new \Exception("Unable to parse directory $presetsAbsDirectory");
                }
                $shownChild = ($child === '') ? rtrim($prefix, '/') : ($prefix.$child);
                foreach ($dirList as $file) {
                    if (($file[0] !== '.') && preg_match('/[^.].*\.less$/i', $file)) {
                        $fileAbs = "$presetsAbsDirectory/$file";
                        if (is_file($fileAbs)) {
                            $content = @file_get_contents($fileAbs);
                            if ($content === false) {
                                throw new \Exception("Error reading file '$fileAbs'");
                            }
                            $content = str_replace("\r", "\n", str_replace("\r\n", "\n", $content));
                            // Strip multiline comments
                            $content = preg_replace_callback(
                                '|/\*.*?\*/|s',
                                function ($matches) {
                                    return str_repeat("\n", substr_count($matches[0], "\n"));
                                },
                                $content
                            );
                            foreach (array("'", '"') as $quote) {
                                if (preg_match('%(?:^|\\n|;)[ \\t]*@preset-name:\\s*'.$quote.'([^'.$quote.']*)'.$quote.'\\s*(?:;|$)%s', $content, $matches)) {
                                    $presetName = $matches[1];
                                    $presetLine = null;
                                    $p = strpos($content, $matches[0]);
                                    if ($p !== false) {
                                        $presetLine = substr_count(substr($content, 0, $p), "\n") + 1;
                                    }
                                    if (!isset($themesPresets[$presetName])) {
                                        $themesPresets[$presetName] = array();
                                    }
                                    $themesPresets[$presetName][] = array($shownChild."/$file", $presetLine);
                                    break;
                                }
                            }
                        }
                    }
                }
            }
        }
        foreach ($themesPresets as $themesPreset => $references) {
            $translation = $translations->insert('PresetName', ucwords(str_replace(array('_', '-', '/'), ' ', $themesPreset)));
            foreach ($references as $reference) {
                $translation->addReference($reference[0], $reference[1]);
            }
        }
    }
}
