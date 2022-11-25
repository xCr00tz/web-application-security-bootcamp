<?php

spl_autoload_register(
    function ($class) {
        if (strpos($class, 'C5TL\\') !== 0) {
            return;
        }
        $file = __DIR__.str_replace('\\', DIRECTORY_SEPARATOR, substr($class, strlen('C5TL'))).'.php';
        if (is_file($file)) {
            require_once $file;
        }
    }
);
