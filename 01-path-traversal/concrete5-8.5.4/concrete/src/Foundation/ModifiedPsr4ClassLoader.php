<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Concrete\Core\Foundation;

/**
 * A PSR-4 compatible class loader.
 *
 * See http://www.php-fig.org/psr/psr-4/
 *
 * @author Alexander M. Turek <me@derrabus.de>
 */
class ModifiedPsr4ClassLoader implements ClassLoaderInterface
{
    /**
     * @var array
     */
    private $prefixes = array();

    /**
     * @param string $prefix
     * @param string $baseDir
     */
    public function addPrefix($prefix, $baseDir)
    {
        $prefix = trim($prefix, '\\').'\\';
        $baseDir = rtrim($baseDir, '/') . '/';
        $this->prefixes[] = array($prefix, $baseDir);
    }

    /**
     * @param string $class
     *
     * @return string|null
     */
    public function findFile($class)
    {
        $class = ltrim($class, '\\');
        foreach ($this->prefixes as $current) {
            list($currentPrefix, $currentBaseDir) = $current;
            if (0 === strpos($class, $currentPrefix)) {
                $classWithoutPrefix = substr($class, strlen($currentPrefix));
                $file = $currentBaseDir . str_replace('\\', '/', $classWithoutPrefix) . '.php';
                if (file_exists($file)) {
                    return $file;
                }

                /* concrete5 Modification */
                $file = '';
                $segments = explode('\\', $classWithoutPrefix);
                foreach ($segments as $segment) {
                    $file .= '/';
                    $segment = lcfirst($segment);
                    $chunks = preg_split('/(?=[A-Z])/', $segment);
                    for ($i = 0; $i < count($chunks); ++$i) {
                        $file .= strtolower($chunks[$i]);
                        if (($i + 1) < count($chunks)) {
                            $file .= '_';
                        }
                    }
                }
                $file = ltrim($file, '/') . '.php';
                $file = $currentBaseDir . $file;
                if (file_exists($file)) {
                    return $file;
                }
            }
        }
    }

    /**
     * @param string $class
     *
     * @return bool
     */
    public function loadClass($class)
    {
        $file = $this->findFile($class);
        if (null !== $file) {
            require $file;

            return true;
        }

        return false;
    }

    /**
     * Registers this instance as an autoloader.
     *
     * @param bool $prepend
     */
    public function register($prepend = false)
    {
        spl_autoload_register(array($this, 'loadClass'), true, $prepend);
    }

    /**
     * Removes this instance from the registered autoloaders.
     */
    public function unregister()
    {
        spl_autoload_unregister(array($this, 'loadClass'));
    }
}
