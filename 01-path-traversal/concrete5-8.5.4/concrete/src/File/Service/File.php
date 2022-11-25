<?php
namespace Concrete\Core\File\Service;

use Zend\Http\Client\Adapter\Exception\TimeoutException;
use Config;
use Environment;
use Exception;
use Core;
use Concrete\Core\Support\Facade\Application as CoreApplication;

/**
 * File helper.
 *
 * Functions useful for working with files and directories.
 *
 * Used as follows:
 * <code>
 * $file = Core::make('helper/file');
 * $path = 'http://www.concrete5.org/tools/get_latest_version_number';
 * $contents = $file->getContents($path);
 * echo $contents;
 * </code>
 *
 * \@package    Helpers
 *
 * @category   Concrete
 *
 * @author     Andrew Embler <andrew@concrete5.org>
 * @copyright  Copyright (c) 2003-2008 Concrete5. (http://www.concrete5.org)
 * @license    http://www.concrete5.org/license/     MIT License
 */
class File
{
    /**
     * Returns the contents of a directory.
     *
     *
     */
    public function getDirectoryContents($dir, $ignoreFiles = [], $recursive = false)
    {
        $aDir = [];
        if (is_dir($dir)) {
            $handle = opendir($dir);
            while (($file = readdir($handle)) !== false) {
                if (substr($file, 0, 1) != '.' && (!in_array($file, $ignoreFiles))) {
                    if (is_dir($dir . '/' . $file)) {
                        if ($recursive) {
                            $aDir = array_merge($aDir, $this->getDirectoryContents($dir . '/' . $file, $ignoreFiles, $recursive));
                            $file = $dir . '/' . $file;
                        }
                        $aDir[] = preg_replace("/\/\//si", '/', $file);
                    } else {
                        if ($recursive) {
                            $file = $dir . '/' . $file;
                        }
                        $aDir[] = preg_replace("/\/\//si", '/', $file);
                    }
                }
            }
            closedir($handle);
        }

        return $aDir;
    }
    /**
     * Removes the extension of a filename, uncamelcases it.
     *
     * @param string $filename
     *
     * @return string
     */
    public function unfilename($filename)
    {
        $parts = $this->splitFilename($filename);
        $txt = Core::make('helper/text');
        /* @var $txt \Concrete\Core\Utility\Service\Text */

        return $txt->unhandle($parts[0] . $parts[1]);
    }

    /**
     * Recursively copies all items in the source directory or file to the target directory.
     *
     * @param string $source Source dir/file to copy
     * @param string $target Place to copy the source
     * @param int    $mode   What to chmod the file to
     */
    public function copyAll($source, $target, $mode = null)
    {
        if (is_dir($source)) {
            if ($mode == null) {
                @mkdir($target, Config::get('concrete.filesystem.permissions.directory'));
                @chmod($target, Config::get('concrete.filesystem.permissions.directory'));
            } else {
                @mkdir($target, $mode);
                @chmod($target, $mode);
            }

            $d = dir($source);
            while (false !== ($entry = $d->read())) {
                if (substr($entry, 0, 1) === '.') {
                    continue;
                }

                $Entry = $source . '/' . $entry;
                if (is_dir($Entry)) {
                    $this->copyAll($Entry, $target . '/' . $entry, $mode);
                    continue;
                }

                copy($Entry, $target . '/' . $entry);
                if ($mode == null) {
                    @chmod($target . '/' . $entry, $this->getCreateFilePermissions($target)->file);
                } else {
                    @chmod($target . '/' . $entry, $mode);
                }
            }

            $d->close();
        } else {
            if ($mode == null) {
                $mode = $this->getCreateFilePermissions(dirname($target))->file;
            }
            copy($source, $target);
            chmod($target, $mode);
        }
    }

    /**
     * Returns an object with two permissions modes (octal):
     * one for files: $res->file
     * and another for directories: $res->dir.
     *
     * @param string $path (optional)
     *
     * @return \stdClass
     */
    public function getCreateFilePermissions($path = null)
    {
        try {
            if (!isset($path)) {
                $path = DIR_FILES_UPLOADED_STANDARD;
            }

            if (!is_dir($path)) {
                $path = @dirname($path);
            }
            $perms = @fileperms($path);

            if (!$perms) {
                throw new Exception(t('An error occurred while attempting to determine file permissions.'));
            }
            clearstatcache();
            $dir_perms = substr(decoct($perms), 1);

            $file_perms = "0";
            $parts[] = substr($dir_perms, 1, 1);
            $parts[] = substr($dir_perms, 2, 1);
            $parts[] = substr($dir_perms, 3, 1);
            foreach ($parts as $p) {
                if (intval($p) % 2 == 0) {
                    $file_perms .= $p;
                    continue;
                }
                $file_perms .= intval($p) - 1;
            }
        } catch (Exception $e) {
            return false;
        }
        $res = new \stdClass();
        $res->file = intval($file_perms, 8);
        $res->dir = intval($dir_perms, 8);

        return $res;
    }

    /**
     * Removes all files from within a specified directory.
     *
     * @param string $source Directory
     * @param bool   $inc    Remove the passed directory as well or leave it alone
     *
     * @return bool Whether the methods succeeds or fails
     */
    public function removeAll($source, $inc = false)
    {
        if (!is_dir($source)) {
            return false;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $path) {
            if ($path->isDir()) {
                if (!@rmdir($path->__toString())) {
                    return false;
                }
            } else {
                if (!@unlink($path->__toString())) {
                    return false;
                }
            }
        }
        if ($inc) {
            if (!@rmdir($source)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Takes a path to a file and sends it to the browser, streaming it, and closing the HTTP connection afterwards. Basically a force download method.
     *
     * @param stings $file
     */
    public function forceDownload($file)
    {
        session_write_close();
        ob_clean();
        header('Content-type: application/octet-stream');
        $filename = basename($file);
        header("Content-Disposition: attachment; filename=\"$filename\"");
        header('Content-Length: ' . filesize($file));
        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Cache-Control: private", false);
        header("Content-Transfer-Encoding: binary");
        header("Content-Encoding: plainbinary");

        // This code isn't ready yet. It will allow us to no longer force download

        /*
        $h = Core::make('helper/mime');
        $mimeType = $h->mimeFromExtension($this->getExtension($file));
        header('Content-type: ' . $mimeType);
        */

        $buffer = '';
        $chunk = 1024 * 1024;
        $handle = fopen($file, 'rb');
        if ($handle === false) {
            return false;
        }
        while (!feof($handle)) {
            $buffer = fread($handle, $chunk);
            echo $buffer;
        }

        fclose($handle);
        exit;
    }

    /**
     * Returns the full path to the temporary directory.
     *
     * @return string
     */
    public function getTemporaryDirectory()
    {
        $temp = Config::get('concrete.filesystem.temp_directory');
        if ($temp && @is_dir($temp)) {
            return $temp;
        }

        if (!is_dir(DIR_FILES_UPLOADED_STANDARD . '/tmp')) {
            @mkdir(DIR_FILES_UPLOADED_STANDARD . '/tmp', Config::get('concrete.filesystem.permissions.directory'));
            @chmod(DIR_FILES_UPLOADED_STANDARD . '/tmp', Config::get('concrete.filesystem.permissions.directory'));
            @touch(DIR_FILES_UPLOADED_STANDARD . '/tmp/index.html');
        }

        if (is_dir(DIR_FILES_UPLOADED_STANDARD . '/tmp') && is_writable(DIR_FILES_UPLOADED_STANDARD . '/tmp')) {
            return DIR_FILES_UPLOADED_STANDARD . '/tmp';
        }

        if ($temp = getenv('TMP')) {
            return str_replace(DIRECTORY_SEPARATOR, '/', $temp);
        }
        if ($temp = getenv('TEMP')) {
            return str_replace(DIRECTORY_SEPARATOR, '/', $temp);
        }
        if ($temp = getenv('TMPDIR')) {
            return str_replace(DIRECTORY_SEPARATOR, '/', $temp);
        }

        $temp = @tempnam(__FILE__, '');
        if (file_exists($temp)) {
            unlink($temp);

            return str_replace(DIRECTORY_SEPARATOR, '/', dirname($temp));
        }
    }

    /**
     * Adds content to a new line in a file. If a file is not there it will be created.
     *
     * @param string $filename
     * @param string $content
     *
     * @return bool
     */
    public function append($filename, $content)
    {
        return file_put_contents($filename, $content, FILE_APPEND) !== false;
    }

    /**
     * Just a consistency wrapper for file_get_contents
     * Should use curl if it exists and fopen isn't allowed (thanks Remo).
     *
     * @param string $filename
     * @param string $timeout
     *
     * @throws TimeoutException Request timed out
     *
     * @return string|bool Returns false in case of failure
     */
    public function getContents($file, $timeout = null)
    {
        $url = @parse_url($file);
        if (isset($url['scheme']) && isset($url['host'])) {
            $app = CoreApplication::getFacadeApplication();
            $client = $app->make('http/client')->setUri($file);
            if (!empty($timeout) && $timeout > 0) {
                $client->setOptions(['timeout' => $timeout]);
            }
            try {
                $response = $client->send();
            } catch (TimeoutException $x) {
                throw $x;
            } catch (Exception $x) {
                $response = null;
            }

            return $response ? $response->getBody() : false;
        } else {
            $contents = @file_get_contents($file);
            if ($contents !== false) {
                return $contents;
            }
        }

        return false;
    }

    /**
     * Removes contents of the file.
     *
     * @param $filename
     *
     * @return bool
     */
    public function clear($file)
    {
        return file_put_contents($file, '') !== false;
    }

    /**
     * Cleans up a filename and returns the cleaned up version.
     *
     * @param string $file
     *
     * @return string
     */
    public function sanitize($file)
    {
        // Let's build an ASCII-only version of name, to avoid filesystem-specific encoding issues.
        $asciiName = Core::make('helper/text')->asciify($file);
        // Let's keep only letters, numbers, underscore and dots.
        $asciiName = trim(preg_replace(["/[\\s]/", "/[^0-9A-Z_a-z-.]/"], ["_", ""], $asciiName));
        // Trim underscores at start and end
        $asciiName = trim($asciiName, '_');
        if (!strlen(str_replace('.', '', $asciiName))) {
            // If the resulting name is empty (or we have only dots in it)
            $asciiName = md5($file);
        } elseif (preg_match('/^\.\w+$/', $asciiName)) {
            // If the resulting name is only composed by the file extension
            $asciiName = md5($file) . $asciiName;
        }

        return $asciiName;
    }

    /**
     * Splits a filename into directory, base file name, extension.
     * If the file name starts with a dot and it's the only dot (eg: '.htaccess'), we don't consider the file to have an extension.
     *
     * @param string $filename
     *
     * @return array
     */
    public function splitFilename($filename)
    {
        $result = ['', '', ''];
        if (is_string($filename)) {
            $result[1] = $filename;
            $slashAt = strrpos(str_replace('\\', '/', $result[1]), '/');
            if ($slashAt !== false) {
                $result[0] = substr($result[1], 0, $slashAt + 1);
                $result[1] = (string) substr($result[1], $slashAt + 1);
            }
            $dotAt = strrpos($result[1], '.');
            if (($dotAt !== false) && ($dotAt > 0)) {
                $result[2] = (string) substr($result[1], $dotAt + 1);
                $result[1] = substr($result[1], 0, $dotAt);
            }
        }

        return $result;
    }

    /**
     * Returns the extension for a file name.
     *
     * @param string $filename
     *
     * @return string
     */
    public function getExtension($filename)
    {
        $parts = $this->splitFilename($filename);

        return $parts[2];
    }

    /**
     * Takes a path and replaces the files extension in that path with the specified extension.
     *
     * @param string $filename
     * @param string $extension
     *
     * @return string
     */
    public function replaceExtension($filename, $extension)
    {
        $parts = $this->splitFilename($filename);
        $newFilename = $parts[0] . $parts[1];
        if (is_string($extension) && ($extension !== '')) {
            $newFilename .= '.' . $extension;
        }

        return $newFilename;
    }

    /**
     * Checks if two path are the same, considering directory separator and OS case sensitivity.
     *
     * @param string $path1
     * @param string $path2
     *
     * @return bool
     */
    public function isSamePath($path1, $path2)
    {
        $path1 = str_replace(DIRECTORY_SEPARATOR, '/', $path1);
        $path2 = str_replace(DIRECTORY_SEPARATOR, '/', $path2);
        // Check if OS is case insensitive
        $checkFile = strtoupper(__FILE__);
        if ($checkFile === __FILE__) {
            $checkFile = strtolower(__FILE__);
        }
        if (@is_file($checkFile)) {
            $same = (strcasecmp($path1, $path2) === 0) ? true : false;
        } else {
            $same = ($path1 === $path2) ? true : false;
        }

        return $same;
    }

    /**
     * Try to set the executable bit of a file.
     *
     * @param string $file The full path
     * @param string $who One of 'user', 'group', 'all'
     *
     * @throws Exception Throws an exception in case of errors
     */
    public function makeExecutable($file, $who = 'all')
    {
        if (!is_file($file)) {
            throw new Exception(t('File %s could not be found.', $file));
        }
        $perms = @fileperms($file);
        if ($perms === false) {
            throw new Exception(t('Unable to retrieve the permissions of the file %s', $file));
        }
        $currentMode = $perms & 0777;
        switch ($who) {
            case 'user':
                $newMode = $currentMode | (1 << 0);
                break;
            case 'group':
                $newMode = $currentMode | (1 << 3);
                break;
            case 'all':
                $newMode = $currentMode | (1 << 6);
                break;
            default:
                throw new Exception(t('Bad parameter: %s', '$who'));
        }
        if ($currentMode !== $newMode) {
            if (@chmod($file, $newMode) === false) {
                throw new Exception(t('Unable to set the permissions of the file %s', $file));
            }
        }
    }
}
