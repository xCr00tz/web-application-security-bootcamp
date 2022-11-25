<?php

namespace C5TL\Parser;

/**
 * Extract translatable strings from core configuration PHP files.
 */
class ConfigFiles extends \C5TL\Parser
{
    /**
     * {@inheritdoc}
     *
     * @see \C5TL\Parser::getParserName()
     */
    public function getParserName()
    {
        return function_exists('t') ? t('Core PHP Configurations Parser') : 'Core PHP Configurations Parser';
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
     * @see \C5TL\Parser::canParseConcreteVersion()
     */
    public function canParseConcreteVersion($version)
    {
        return version_compare($version, '8.0.0b6') >= 0;
    }

    /**
     * {@inheritdoc}
     *
     * @see \C5TL\Parser::parseDirectoryDo()
     */
    protected function parseDirectoryDo(\Gettext\Translations $translations, $rootDirectory, $relativePath, $subParsersFilter, $exclude3rdParty)
    {
        switch ($relativePath) {
            case '':
                $directoryAlternatives = array('application/config/generated_overrides', 'application/config', 'concrete/config');
                break;
            case 'application':
                $directoryAlternatives = array('config/generated_overrides', 'config');
                break;
            case 'concrete':
                $directoryAlternatives = array('config');
                break;
            default:
                return;
        }
        $prefix = ($relativePath === '') ? '' : "$relativePath/";
        $this->parseFileTypes($translations, $rootDirectory, $prefix, $directoryAlternatives);
        $this->parseTranslatableStrings($translations, $rootDirectory, $prefix, $directoryAlternatives);
    }

    /**
     * Parse the file type names.
     *
     * @param \Gettext\Translations $translations
     * @param string                $rootDirectory
     * @param string                $prefix
     * @param string[]              $directoryAlternatives
     */
    private function parseFileTypes(\Gettext\Translations $translations, $rootDirectory, $prefix, $directoryAlternatives)
    {
        foreach ($directoryAlternatives as $subDir) {
            $rel = ($subDir === '') ? 'app.php' : "$subDir/app.php";
            $fileAbs = $rootDirectory.'/'.$rel;
            if (!is_file($fileAbs)) {
                continue;
            }
            $fileRel = $prefix.$rel;
            $configFile = new \C5TL\Util\ConfigFile($fileAbs);
            $config = $configFile->getArray();
            if (isset($config['file_types']) && is_array($config['file_types'])) {
                $fileTypes = $config['file_types'];
                foreach (array_keys($fileTypes) as $fileType) {
                    $translation = $translations->insert('', $fileType);
                    $translation->addReference($fileRel);
                }
            }
        }
    }

    /**
     * Parse the file type names.
     *
     * @param \Gettext\Translations $translations
     * @param string                $rootDirectory
     * @param string                $prefix
     * @param string[]              $directoryAlternatives
     */
    private function parseTranslatableStrings(\Gettext\Translations $translations, $rootDirectory, $prefix, $directoryAlternatives)
    {
        foreach ($this->getTranslatableKeys() as $key => $context) {
            list($baseFilename, $subkey) = explode('.', $key, 2);
            foreach ($directoryAlternatives as $subDir) {
                $rel = ($subDir === '') ? "{$baseFilename}.php" : "{$subDir}/{$baseFilename}.php";
                $fileAbs = $rootDirectory.'/'.$rel;
                if (!is_file($fileAbs)) {
                    continue;
                }
                $fileRel = $prefix.$rel;
                $configFile = new \C5TL\Util\ConfigFile($fileAbs);
                $config = $configFile->getArray();
                $subkeys = explode('.', $subkey);
                while (($k = array_shift($subkeys)) !== null) {
                    if (!is_array($config) || !isset($config[$k])) {
                        $config = null;
                        break;
                    }
                    $config = $config[$k];
                }
                if ($config !== null) {
                    $this->parseTranslatableConfigValue($context, $config, $translations, $fileRel);
                }
            }
        }
    }

    /**
     * Get the list of configuration keys that contains translatable strings.
     * Array keys are the configuration keys, values are translation contexts (for `tc()`).
     * If the found configuration keys are arrays with 2 elements, we'll assume the strings are plurals (for `t2()`).
     *
     * @var array
     */
    private function getTranslatableKeys()
    {
        return array(
            'concrete.user.deactivation.message' => '',
            'concrete.user.username.allowed_characters.requirement_string' => '',
            'concrete.user.username.allowed_characters.error_string' => '',
        );
    }
    
    /**
     * Add to the $translations the value read from the configuration.
     *
     * @param string $context
     * @param string|string[]|mixed $configurationValue
     * @param \Gettext\Translations $translations
     * @param string $concrete5version
     * @param string $fileRel
     */
    private function parseTranslatableConfigValue($context, $configurationValue, \Gettext\Translations $translations, $fileRel)
    {
        $translation = null;
        if (is_string($configurationValue)) {
            if ($configurationValue !== '') {
                $translation = $translations->insert((string) $context, $configurationValue);
            }
        } elseif (is_array($configurationValue)) {
            if (
                count($configurationValue) === 2
                && isset($configurationValue[0]) && isset($configurationValue[1])
                && is_string($configurationValue[0]) && is_string($configurationValue[1])
                && $configurationValue[0] !== '' && $configurationValue[1] !== ''
            ) {
                $translation = $translations->insert((string) $context, $configurationValue[0], $configurationValue[1]);
            }
        }
        if ($translation !== null) {
            $translation->addReference($fileRel);
        }
    }
}
