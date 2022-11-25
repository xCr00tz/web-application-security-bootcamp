<?php
namespace Concrete\Core\File;

use Config;
use Loader;

/**
 * \@package Helpers
 * @subpackage Validation
 *
 * @author Andrew Embler <andrew@concrete5.org>
 * @copyright  Copyright (c) 2003-2008 Concrete5. (http://www.concrete5.org)
 * @license    http://www.concrete5.org/license/     MIT License
 */

/**
 * Helper elements for validating uploaded and existing files in Concrete.
 *
 * \@package Helpers
 * @subpackage Validation
 *
 * @author Andrew Embler <andrew@concrete5.org>
 * @copyright  Copyright (c) 2003-2008 Concrete5. (http://www.concrete5.org)
 * @license    http://www.concrete5.org/license/     MIT License
 */
class ValidationService
{
    /**
     * Tests whether the passed item a valid image.
     *
     * @param $pathToImage
     *
     * @return bool
     */
    public function image($pathToImage)
    {

        /* compatibility if exif functions not available (--enable-exif) */
        if (!function_exists('exif_imagetype')) {
            function exif_imagetype($filename)
            {
                if ((list($width, $height, $type, $attr) = getimagesize($filename)) !== false) {
                    return $type;
                }

                return false;
            }
        }

        $val = @exif_imagetype($pathToImage);

        return in_array($val, array(IMAGETYPE_GIF, IMAGETYPE_JPEG, IMAGETYPE_PNG));
    }

    /**
     * Tests whether a file exists.
     *
     * @todo Should probably have a list of valid file types that could be passed
     *
     * @return bool
     */
    public function file($pathToFile)
    {
        return file_exists($pathToFile);
    }

    /**
     * Parses the file extension for a given file name, checks it to see if it's in the the extension array if provided
     * if not, it checks to see if it's in the concrete.upload.extensions configuration option.
     *
     * @param string $filename
     * @param array $extensions
     *
     * @return bool
     */
    public function extension($filename, $extensions = null)
    {
        $f = app('helper/file');
        $cf = app('helper/concrete/file');
        $config = app('config');
        $ext = strtolower($f->getExtension($filename));
        
        $blacklist = array_map('strtolower', $cf->unSerializeUploadFileExtensions($config->get('concrete.upload.extensions_blacklist')));
        if (in_array($ext, $blacklist, true)) {
            return false;
        }
        if (is_array($extensions) && $extensions !== []) {
            $allowed_extensions = $extensions;
        } else { // pull from constants
            $allowed_extensions = $cf->unSerializeUploadFileExtensions($config->get('concrete.upload.extensions'));
        }
        $allowed_extensions = array_map('strtolower', $allowed_extensions);

        return in_array($ext, $allowed_extensions, true);
    }

    /**
     */
    public function filetype($filename, $extensions = null)
    {
        return $this->extension($filename, $extensions);
    }
}
