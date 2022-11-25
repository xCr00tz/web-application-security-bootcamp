<?php

namespace C5TL;

/**
 * Holds global options of C5TL.
 */
class Options
{
    /**
     * Currently configured temporary directory. If empty we'll try to detect it.
     *
     * @var string
     */
    protected static $temporaryDirectory = '';

    /**
     * Returns the temporary directory.
     *
     * @throws \Exception
     *
     * @return string
     */
    public static function getTemporaryDirectory()
    {
        $check = function ($s) {
            $result = '';
            if (is_string($s) && ($s !== '')) {
                $s = @realpath($s);
                if (($s !== false) && is_dir($s) && is_writable(($s))) {
                    $result = $s;
                }
            }

            return $result;
        };
        $result = '';
        if ($result === '') {
            if (static::$temporaryDirectory !== '') {
                $result = $check(static::$temporaryDirectory);
                if ($result === '') {
                    throw new \Exception('The configured temporary directory is not valid');
                }
            }
        }
        if ($result === '') {
            if (function_exists('sys_get_temp_dir')) {
                $result = $check(@sys_get_temp_dir());
            }
        }
        if ($result === '') {
            if (isset($_ENV) && is_array($_ENV)) {
                foreach (array('TMP', 'TMPDIR', 'TEMP') as $k) {
                    if (isset($_ENV[$k])) {
                        $result = $check($_ENV[$k]);
                        if ($result !== '') {
                            break;
                        }
                    }
                }
            }
        }
        if ($result === '') {
            throw new \Exception('Unable to determine a temporary directory');
        }

        return $result;
    }

    /**
     * Sets the temporary directory.
     *
     * @param string $value Set to an empty string to detect the temporary directory
     */
    public static function setTemporaryDirectory($value)
    {
        static::$temporaryDirectory = (string) $value;
    }
}
