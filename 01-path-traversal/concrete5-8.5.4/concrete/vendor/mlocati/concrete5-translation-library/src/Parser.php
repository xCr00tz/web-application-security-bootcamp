<?php

namespace C5TL;

/**
 * Base class for all the parsers.
 */
abstract class Parser
{
    /**
     * Handles some stuff in memory.
     *
     * @var array
     */
    private static $cache = array();

    /**
     * Returns the parser name.
     *
     * @return string
     */
    abstract public function getParserName();

    /**
     * Returns the parser handler.
     *
     * @return string
     */
    final public function getParserHandle()
    {
        $chunks = explode('\\', get_class($this));

        return static::handlifyString(end($chunks));
    }

    /**
     * Can this parser parse files of a specific concrete5 version?
     *
     * @param string $version
     *
     * @return bool
     */
    public function canParseConcreteVersion($version)
    {
        return true;
    }

    /**
     * Does this parser can parse directories?
     *
     * @return bool
     */
    public function canParseDirectory()
    {
        return false;
    }

    /**
     * Extracts translations from a directory.
     *
     * @param string                          $rootDirectory    The base directory where we start looking translations from
     * @param string                          $relativePath     The relative path (translations references will be prepended with this path)
     * @param \Gettext\Translations|null=null $translations     The translations object where the translatable strings will be added (if null we'll create a new Translations instance)
     * @param array|false                     $subParsersFilter A list of sub-parsers handles (set to false to use all the sub-parsers)
     * @param bool                            $exclude3rdParty  Exclude concrete5 3rd party directories (namely directories called 'vendor' and '3rdparty')
     *
     * @throws \Exception Throws an \Exception in case of errors
     *
     * @return \Gettext\Translations
     *
     * @example If you want to parse the concrete5 core directory, you should call `parseDirectory('PathToTheWebroot/concrete', 'concrete')`
     * @example If you want to parse a concrete5 package, you should call `parseDirectory('PathToThePackageFolder', 'packages/YourPackageHandle')`
     */
    final public function parseDirectory($rootDirectory, $relativePath, $translations = null, $subParsersFilter = false, $exclude3rdParty = true)
    {
        if (!is_object($translations)) {
            $translations = new \Gettext\Translations();
        }
        $dir = (string) $rootDirectory;
        if ($dir !== '') {
            $dir = @realpath($rootDirectory);
            if (($dir === false) || (!is_dir($dir))) {
                $dir = '';
            } else {
                $dir = rtrim(str_replace(DIRECTORY_SEPARATOR, '/', $dir), '/');
            }
        }
        if ($dir === '') {
            throw new \Exception("Unable to find the directory $rootDirectory");
        }
        if (!@is_readable($dir)) {
            throw new \Exception("Directory not readable: $dir");
        }
        $dirRel = is_string($relativePath) ? trim(str_replace(DIRECTORY_SEPARATOR, '/', $relativePath), '/') : '';
        $this->parseDirectoryDo($translations, $dir, $dirRel, $subParsersFilter, $exclude3rdParty);

        return $translations;
    }

    /**
     * Final implementation of {@link \C5TL\Parser::parseDirectory()}.
     *
     * @param \Gettext\Translations $translations     Found translatable strings will be appended here
     * @param string                $rootDirectory    The base directory where we start looking translations from
     * @param string                $relativePath     The relative path (translations references will be prepended with this path)
     * @param array|false           $subParsersFilter A list of sub-parsers handles (set to false to use all the sub-parsers)
     * @param bool                  $exclude3rdParty  Exclude concrete5 3rd party directories (namely directories called 'vendor' and '3rdparty')
     */
    protected function parseDirectoryDo(\Gettext\Translations $translations, $rootDirectory, $relativePath, $subParsersFilter, $exclude3rdParty)
    {
        throw new \Exception('This parser does not support filesystem parsing');
    }

    /**
     * Does this parser can parse data from a running concrete5 instance?
     *
     * @return bool
     */
    public function canParseRunningConcrete5()
    {
        return false;
    }

    /**
     * Extracts translations from a running concrete5 instance.
     *
     * @param \Gettext\Translations|null=null $translations     The translations object where the translatable strings will be added (if null we'll create a new Translations instance)
     * @param array|false                     $subParsersFilter A list of sub-parsers handles (set to false to use all the sub-parsers)
     *
     * @throws \Exception Throws an \Exception in case of errors
     *
     * @return \Gettext\Translations
     */
    final public function parseRunningConcrete5($translations = null, $subParsersFilter = false)
    {
        if (!is_object($translations)) {
            $translations = new \Gettext\Translations();
        }
        $runningVersion = '';
        if (defined('\C5_EXECUTE') && defined('\APP_VERSION') && is_string(\APP_VERSION)) {
            $runningVersion = \APP_VERSION;
        }
        if ($runningVersion === '') {
            throw new \Exception('Unable to determine the current working directory');
        }
        $this->parseRunningConcrete5Do($translations, $runningVersion, $subParsersFilter);

        return $translations;
    }

    /**
     * Final implementation of {@link \C5TL\Parser::parseRunningConcrete5()}.
     *
     * @param \Gettext\Translations $translations     Found translatable strings will be appended here
     * @param string                $concrete5version The version of the running concrete5 instance
     * @param array|false           $subParsersFilter A list of sub-parsers handles (set to false to use all the sub-parsers)
     */
    protected function parseRunningConcrete5Do(\Gettext\Translations $translations, $concrete5version, $subParsersFilter)
    {
        throw new \Exception('This parser does not support parsing a running concrete5 instance');
    }

    /**
     * Returns a list of handles of sub-parsers (if any).
     *
     * @return array
     */
    public function getSubParsers()
    {
        return array();
    }

    /**
     * Clears the memory cache.
     */
    final public static function clearCache()
    {
        self::$cache = array();
    }

    /**
     * Returns the directory structure underneath a given directory.
     *
     * @param string $rootDirectory        The root directory
     * @param bool   $exclude3rdParty=true Exclude concrete5 3rd party directories (namely directories called 'vendor' and '3rdparty')
     *
     * @return array
     */
    final protected static function getDirectoryStructure($rootDirectory, $exclude3rdParty = true)
    {
        $rootDirectory = rtrim(str_replace(DIRECTORY_SEPARATOR, '/', $rootDirectory), '/');
        if (!isset(self::$cache[__FUNCTION__])) {
            self::$cache[__FUNCTION__] = array();
        }
        $cacheKey = $rootDirectory.'*'.($exclude3rdParty ? '1' : '0');
        if (!isset(self::$cache[__FUNCTION__][$cacheKey])) {
            self::$cache[__FUNCTION__][$cacheKey] = static::getDirectoryStructureDo('', $rootDirectory, $exclude3rdParty);
        }

        return self::$cache[__FUNCTION__][$cacheKey];
    }

    /**
     * Helper function called by {@link \C5TL\Parser::getDirectoryStructure()}.
     *
     * @param string $relativePath
     * @param string $rootDirectory
     * @param bool   $exclude3rdParty
     *
     * @throws \Exception
     *
     * @return array[string]
     */
    final private static function getDirectoryStructureDo($relativePath, $rootDirectory, $exclude3rdParty)
    {
        $thisRoot = $rootDirectory;
        if ($relativePath !== '') {
            $thisRoot .= '/'.$relativePath;
        }
        $subDirs = array();
        $hDir = @opendir($thisRoot);
        if ($hDir === false) {
            throw new \Exception("Unable to open directory $rootDirectory");
        }
        while (($entry = @readdir($hDir)) !== false) {
            if ($entry[0] === '.') {
                continue;
            }
            $fullPath = $thisRoot.'/'.$entry;
            if (!is_dir($fullPath)) {
                continue;
            }
            if ($exclude3rdParty) {
                if (($entry === 'vendor') || preg_match('%/libraries/3rdparty$%', $fullPath)) {
                    continue;
                }
            }
            $subDirs[] = $entry;
        }
        @closedir($hDir);
        $result = array();
        foreach ($subDirs as $subDir) {
            $rel = ($relativePath === '') ? $subDir : "$relativePath/$subDir";
            $result = array_merge($result, static::getDirectoryStructureDo($rel, $rootDirectory, $exclude3rdParty));
            $result[] = $rel;
        }

        return $result;
    }

    /**
     * Retrieves all the available parsers.
     *
     * @return array[\C5TL\Parser]
     */
    final public static function getAllParsers()
    {
        $result = array();
        $dir = __DIR__.'/Parser';
        if (is_dir($dir) && is_readable($dir)) {
            $matches = null;
            foreach (scandir($dir) as $item) {
                if (($item[0] !== '.') && preg_match('/^(.+)\.php$/i', $item, $matches)) {
                    $fqClassName = '\\'.__NAMESPACE__.'\\Parser\\'.$matches[1];
                    $result[] = new $fqClassName();
                }
            }
        }

        return $result;
    }

    final public static function getByHandle($parserHandle)
    {
        $parser = null;
        $fqClassName = '\\'.__NAMESPACE__.'\\Parser\\'.static::camelifyString($parserHandle);
        if (class_exists($fqClassName, true)) {
            $parser = new $fqClassName();
        }

        return $parser;
    }

    /**
     * Camelcases a string and separates words (eg from 'hi_there' to 'Hi There').
     *
     * @param string $string
     *
     * @return string
     */
    final protected static function unhandleString($string)
    {
        return ucwords(str_replace(array('_', '-', '/'), ' ', $string));
    }

    /**
     * Camelcases a string (eg from 'hi_there' to 'HiThere').
     *
     * @param string $string
     *
     * @return string
     */
    final protected static function camelifyString($string)
    {
        return str_replace(' ', '', static::unhandleString($string));
    }

    /**
     * Concatenates words with an underscore and lowercases them (eg from 'HiThere' or 'HiThere' to 'hi_there'). Upper case words are prepended with an underscore too.
     *
     * @param string $string
     *
     * @return string
     *
     * @internal
     */
    final public static function handlifyString($string)
    {
        $string = preg_replace('/\W+/', '_', $string);
        $string = preg_replace('/([A-Z])/', '_$1', $string);
        $string = strtolower($string);
        $string = preg_replace('/_+/', '_', trim($string, '_'));

        return $string;
    }
}
