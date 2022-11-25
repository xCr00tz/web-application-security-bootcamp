<?php
namespace Concrete\Core\System;

use Localization;
use Concrete\Core\Support\Facade\Facade;
use Concrete\Core\Database\Connection\Connection;
use Concrete\Core\Foundation\Environment;
use Concrete\Core\Package\PackageList;

class Info
{
    /**
     * @var \Concrete\Core\Application\Application
     */
    private $app;

    /**
     * @var bool
     */
    protected $installed;

    /**
     * @var string
     */
    protected $webRootDirectory;

    /**
     * @var string
     */
    protected $coreRootDirectory;

    /**
     * @var string
     */
    protected $coreVersions;

    /**
     * @var string
     */
    protected $packages;

    /**
     * @var string
     */
    protected $overrides;

    /**
     * @var string
     */
    protected $cache;

    /**
     * @var string
     */
    protected $serverSoftware;

    /**
     * @var string
     */
    protected $serverAPI;

    /**
     * @var string
     */
    protected $phpVersion;

    /**
     * @var string
     */
    protected $versionInstalled;

    /**
     * @var string
     */
    protected $codeVersion;

    /**
     * @var string
     */
    protected $dbVersion;

    /**
     * @var string|null
     */
    private $dbmsVersion;

    /**
     * @var string|null
     */
    private $dbmsSqlMode;

    public function __construct()
    {
        $loc = Localization::getInstance();
        $loc->pushActiveContext(Localization::CONTEXT_SYSTEM);
        try {
            $this->app = Facade::getFacadeApplication();
            $config = $this->app->make('config');
            $maxExecutionTime = ini_get('max_execution_time');
            @set_time_limit(5);

            $this->installed = (bool) $this->app->isInstalled();

            $this->webRootDirectory = DIR_BASE;

            $this->coreRootDirectory = DIR_BASE_CORE;

            $this->codeVersion = $config->get('concrete.version');
            $this->dbVersion = $config->get('concrete.version_db');
            $this->versionInstalled = $config->get('concrete.version_installed');

            $versions = ['Core Version - '. $this->codeVersion];
            if ($this->installed) {
                $versions[] = 'Version Installed - ' . $this->versionInstalled;
            }
            $versions[] = 'Database Version - ' . $this->dbVersion;
            $this->coreVersions = implode("\n", $versions);


            $packages = [];
            if ($this->installed) {
                foreach (PackageList::get()->getPackages() as $p) {
                    if ($p->isPackageInstalled()) {
                        $packages[] = $p->getPackageName() . ' (' . $p->getPackageVersion() . ')';
                    }
                }
            }
            natcasesort($packages);
            $this->packages = implode(', ', $packages);

            $overrides = $this->getOverrideList();
            if (empty($overrides)) {
                $this->overrides = '';
            } else {
                $this->overrides = implode(', ', $overrides);
            }

            $cache = [
                sprintf('Block Cache - %s', $config->get('concrete.cache.blocks') ? 'On' : 'Off'),
                sprintf('Overrides Cache - %s', $config->get('concrete.cache.overrides') ? 'On' : 'Off'),
                sprintf('Full Page Caching - %s',
                    $config->get('concrete.cache.pages') == 'blocks' ?
                        'On - If blocks on the particular page allow it.'
                        :
                        (
                        $config->get('concrete.cache.pages') == 'all' ?
                            'On - In all cases.'
                            :
                            'Off'
                        )
                ),
            ];
            if ($config->get('concrete.cache.full_page_lifetime')) {
                $cache[] = sprintf("Full Page Cache Lifetime - %s",
                    $config->get('concrete.cache.full_page_lifetime') == 'default' ?
                        sprintf('Every %s (default setting).', $this->app->make('helper/date')->describeInterval($config->get('concrete.cache.lifetime')))
                        :
                        (
                        $config->get('concrete.cache.full_page_lifetime') == 'forever' ?
                            'Only when manually removed or the cache is cleared.'
                            :
                            sprintf('Every %s minutes.', $config->get('concrete.cache.full_page_lifetime_value'))
                        )
                );
            }
            $this->cache = implode("\n", $cache);

            $this->serverSoftware = \Request::getInstance()->server->get('SERVER_SOFTWARE', '');

            $this->serverAPI = PHP_SAPI;

            $this->phpVersion = PHP_VERSION;

            if (function_exists('get_loaded_extensions')) {
                $extensions = @get_loaded_extensions();
            } else {
                $extensions = false;
            }
            if (is_array($extensions)) {
                natcasesort($extensions);
                $this->phpExtensions = implode(', ', $extensions);
            } else {
                $this->phpExtensions = false;
            }

            ob_start();
            phpinfo();
            $buffer = ob_get_clean();
            $phpinfo = [];
            if ($this->app->isRunThroughCommandLineInterface()) {
                $section = null;
                foreach (preg_split('/[\r\n]+/', $buffer) as $line) {
                    $chunks = array_map('trim', explode('=>', $line));
                    switch (count($chunks)) {
                        case 1:
                            if ($chunks[0] === '') {
                                continue 2;
                            }
                            $section = $chunks[0];
                            break;
                        case 2:
                            if ($section !== null) {
                                $phpinfo[$section][$chunks[0]] = $chunks[1];
                            }
                            break;
                        default:
                            if ($section !== null) {
                                $phpinfo[$section][$chunks[0]] = [$chunks[1], $chunks[2]];
                            }
                            break;
                    }
                }
            } else {
                $section = 'phpinfo';
                $phpinfo[$section] = [];
                if (preg_match_all('#(?:<h2>(?:<a name=".*?">)?(.*?)(?:</a>)?</h2>)|(?:<tr(?: class=".*?")?><t[hd](?: class=".*?")?>(.*?)\s*</t[hd]>(?:<t[hd](?: class=".*?")?>(.*?)\s*</t[hd]>(?:<t[hd](?: class=".*?")?>(.*?)\s*</t[hd]>)?)?</tr>)#s', $buffer, $matches, PREG_SET_ORDER)) {
                    foreach ($matches as $match) {
                        if ($match[1] !== null && $match[1] !== '') {
                            $section = $match[1];
                            $phpinfo[$section] = [];
                        } elseif (isset($match[3])) {
                            $phpinfo[$section][$match[2]] = isset($match[4]) ? [$match[3], $match[4]] : $match[3];
                        } else {
                            $phpinfo[$section][] = $match[2];
                        }
                    }
                }
            }
            $phpSettings = [
                "max_execution_time - $maxExecutionTime",
            ];
            foreach ($phpinfo as $name => $section) {
                foreach ($section as $key => $val) {
                    if (preg_match('/.*max_execution_time*/', $key)) {
                        continue;
                    }
                    if (strpos($key, 'limit') === false && strpos($key, 'safe') === false && strpos($key, 'max') === false) {
                        continue;
                    }
                    if (is_array($val)) {
                        $phpSettings[] = "$key - {$val[0]}";
                    } elseif (is_string($key)) {
                        $phpSettings[] = "$key - $val";
                    } else {
                        $phpSettings[] = $val;
                    }
                }
            }
            $this->phpSettings = implode("\n", $phpSettings);

            $loc->popActiveContext();
        } catch (\Exception $x) {
            $loc->popActiveContext();
            throw $x;
        }
    }

    public function getOverrideList()
    {
        $overrides = [];
        $fh = \Core::make("helper/file");
        $check = array(
            DIR_FILES_BLOCK_TYPES,
            DIR_FILES_CONTROLLERS,
            DIR_FILES_ELEMENTS,
            DIR_APPLICATION.'/'.DIRNAME_ATTRIBUTES,
            DIR_APPLICATION.'/'.DIRNAME_AUTHENTICATION,
            DIR_FILES_JOBS,
            DIR_APPLICATION.'/'.DIRNAME_CSS,
            DIR_APPLICATION.'/'.DIRNAME_JAVASCRIPT,
            DIR_FILES_EMAIL_TEMPLATES,
            DIR_FILES_CONTENT,
            DIR_FILES_THEMES,
            DIR_FILES_TOOLS,
            DIR_APPLICATION.'/'.DIRNAME_PAGE_TEMPLATES,
            DIR_APPLICATION.'/'.DIRNAME_VIEWS,
            DIR_APPLICATION.'/'.DIRNAME_CLASSES,
            DIR_APPLICATION.'/'.DIRNAME_MENU_ITEMS,
        );
        foreach ($check as $loc) {
            if (is_dir($loc)) {
                $contents = $fh->getDirectoryContents($loc, array(), true);
                foreach ($contents as $f) {
                    $item = str_replace(DIR_APPLICATION.'/', '', $f);
                    $item = str_replace(DIR_BASE.'/', '', $item);
                    $overrides[] = $item;
                }
            }
        }

        return $overrides;
    }

    /**
     * @return bool
     */
    public function isInstalled()
    {
        return $this->installed;
    }

    /**
     * @return string
     */
    public function getWebRootDirectory()
    {
        return $this->webRootDirectory;
    }

    /**
     * @return string
     */
    public function getCoreRootDirectory()
    {
        return $this->coreRootDirectory;
    }

    /**
     * @return string
     */
    public function getCoreVersions()
    {
        return $this->coreVersions;
    }

    /**
     * @return string
     */
    public function getPackages()
    {
        return $this->packages;
    }

    /**
     * @return string
     */
    public function getOverrides()
    {
        return $this->overrides;
    }

    /**
     * @return string
     */
    public function getCache()
    {
        return $this->cache;
    }

    /**
     * @return string
     */
    public function getServerSoftware()
    {
        return $this->serverSoftware;
    }

    /**
     * @return string
     */
    public function getServerAPI()
    {
        return $this->serverAPI;
    }

    /**
     * @return string
     */
    public function getPhpVersion()
    {
        return $this->phpVersion;
    }

    /**
     * @var string|false
     */
    protected $phpExtensions;

    /**
     * @return string|false
     */
    public function getPhpExtensions()
    {
        return $this->phpExtensions;
    }

    /**
     * @var string
     */
    protected $phpSettings;

    /**
     * @return string
     */
    public function getPhpSettings()
    {
        return $this->phpSettings;
    }

    public function getJSONOBject()
    {
        $o = new \stdClass();
        $o->phpSettings = $this->phpSettings;
        $o->phpExtensions = $this->phpExtensions;
        $o->phpVersion = $this->phpVersion;
        $o->serverAPI = $this->serverAPI;
        $o->serverSoftware = $this->serverSoftware;
        $o->cache = $this->cache;
        $o->overrides = $this->overrides;
        $o->packages = $this->packages;
        $o->coreVersions = $this->coreVersions;
        return $o;
    }

    /**
     * @return string
     */
    public function getVersionInstalled()
    {
        return $this->versionInstalled;
    }

    /**
     * @return string
     */
    public function getCodeVersion()
    {
        return $this->codeVersion;
    }

    /**
     * @return string
     */
    public function getDbVersion()
    {
        return $this->dbVersion;
    }

    /**
     * @return string
     */
    public function getDBMSVersion()
    {
        if ($this->dbmsVersion === null) {
            $this->dbmsVersion = '';
            if ($this->installed) {
                try {
                    $cn = $this->app->make(Connection::class);
                    $this->dbmsVersion = (string) $cn->fetchColumn('select @@version');
                } catch (\Exception $x) {
                }
            }
        }

        return $this->dbmsVersion;
    }

    /**
     * @return string
     */
    public function getDBMSSqlMode()
    {
        if ($this->dbmsSqlMode === null) {
            $this->dbmsSqlMode = '';
            if ($this->installed) {
                try {
                    $cn = $this->app->make(Connection::class);
                    $this->dbmsSqlMode = (string) $cn->fetchColumn('select @@sql_mode');
                } catch (\Exception $x) {
                }
            }
        }

        return $this->dbmsSqlMode;
    }
}
