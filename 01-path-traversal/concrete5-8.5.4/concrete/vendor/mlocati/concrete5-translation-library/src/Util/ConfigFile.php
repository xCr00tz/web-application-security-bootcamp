<?php

namespace C5TL\Util;

use C5TL\Options;
use Exception;

class ConfigFile
{
    /**
     * The file contents.
     *
     * @var string
     */
    private $contents;

    /**
     * The position inside the file where we can add custom content.
     *
     * @var int
     */
    private $customizersPosition;

    /**
     * Parsed array.
     *
     * @var array
     */
    protected $array;

    /**
     * Last error catched by the evaluate method.
     *
     * @var array|null
     */
    private $lastEvaluateError;

    /**
     * Initializes the instance.
     *
     * @param string $filename
     *
     * @throws Exception
     */
    public function __construct($filename)
    {
        $this->readFile($filename);
        $this->setCustomizersPosition();
        if (!function_exists('t')) {
            $this->addCustomizer('function t($arg) { return $arg; }');
        }
        $this->evaluate();
    }

    /**
     * Read the contents of the file.
     *
     * @param string $filename
     *
     * @throws Exception
     *
     * @return string
     */
    private function readFile($filename)
    {
        if (!is_file($filename)) {
            throw new Exception('Failed to find file '.$filename);
        }
        if (!is_readable($filename)) {
            throw new Exception('File is not readable: '.$filename);
        }
        $contents = @file_get_contents($filename);
        if ($contents === false) {
            throw new Exception('Failed to read file '.$filename);
        }
        $contents = str_replace(array("\r\n", "\r"), "\n", $contents);
        $contents = preg_replace('/([a-z_\\\\]+)::class\b/i', "'\\1'", $contents);
        $this->contents = $contents;
    }

    /**
     * Set the position inside the read content where we can add custom code.
     */
    private function setCustomizersPosition()
    {
        if (!preg_match('/^\s*return[\n\s]+(?:\[|array[\s\n]*\()/ims', $this->contents, $m)) {
            throw new Exception('Failed to determine the start of the return array');
        }
        $this->customizersPosition = strpos($this->contents, $m[0]);
    }

    private function addCustomizer($code)
    {
        $this->contents = substr($this->contents, 0, $this->customizersPosition)."\n".$code."\n".substr($this->contents, $this->customizersPosition);
    }

    public function evaluateAutoloader($className)
    {
        class_alias('C5TL\Util\ConfigFileFakeClass', $className);

        return true;
    }

    public function evaluateHandleError()
    {
        $this->lastEvaluateError = func_get_args();
    }

    private function evaluate()
    {
        $tempDir = Options::getTemporaryDirectory();
        for ($i = 0; ; ++$i) {
            $filename = rtrim($tempDir, '/'.DIRECTORY_SEPARATOR).'/c5tltmp'.$i.'.php';
            if (!file_exists($filename)) {
                break;
            }
        }
        if (@file_put_contents($filename, $this->contents) === false) {
            throw new Exception('Failed to write a temporary file');
        }
        $errorReporting = @error_reporting();
        error_reporting($errorReporting & ~E_NOTICE);
        $exception = null;
        $autoloader = array($this, 'evaluateAutoloader');
        spl_autoload_register($autoloader, true, false);
        $prevErrorHandler = set_error_handler(array($this, 'evaluateHandleError'));
        $this->lastEvaluateError = null;
        try {
            $code = include $filename;
        } catch (\Exception $x) {
            $exception = $x;
        } catch (\Throwable $x) {
            $exception = new \Exception($x->getMessage());
        }
        @set_error_handler($prevErrorHandler);
        @unlink($filename);
        @spl_autoload_unregister($autoloader);
        @error_reporting($errorReporting);
        if ($exception !== null) {
            throw $exception;
        }
        if (!is_array($code) && $this->lastEvaluateError !== null) {
            throw new Exception('Failed to read configuration file: '.$this->lastEvaluateError[1]);
        }
        $this->array = is_array($code) ? $code : array();
    }

    /**
     * Return the read array.
     *
     * @return array
     */
    public function getArray()
    {
        return $this->array;
    }
}
