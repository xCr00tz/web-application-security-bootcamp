<?php

namespace C5TL;

/**
 * Variuos gettext-related helper functions.
 */
class Gettext
{
    /**
     * Checks if a gettext command is available.
     *
     * @param string $command One of the gettext commands
     *
     * @return bool
     */
    public static function commandIsAvailable($command)
    {
        static $cache = array();
        if (!isset($cache[$command])) {
            $cache[$command] = false;
            $safeMode = @ini_get('safe_mode');
            if (empty($safeMode)) {
                if (function_exists('exec')) {
                    if (!in_array('exec', array_map('trim', explode(',', strtolower(@ini_get('disable_functions')))), true)) {
                        $rc = 1;
                        $output = array();
                        @exec($command.' --version 2>&1', $output, $rc);
                        if ($rc === 0) {
                            $cache[$command] = true;
                        }
                    }
                }
            }
        }

        return $cache[$command];
    }
}
