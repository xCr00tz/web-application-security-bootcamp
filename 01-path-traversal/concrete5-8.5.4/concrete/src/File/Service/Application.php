<?php
namespace Concrete\Core\File\Service;

use Concrete\Core\File\StorageLocation\StorageLocation;
use Config;
use Concrete\Core\Support\Facade\Application as ApplicationFacade;
use Concrete\Core\File\Image\BitmapFormat;
use Concrete\Core\File\Image\Thumbnail\ThumbnailFormatService;
use Concrete\Core\File\Incoming;

class Application
{
    public function prefix($prefix, $filename)
    {
        $apr = str_split($prefix, 4);

        return sprintf('/%s/%s/%s/%s', $apr[0], $apr[1], $apr[2], $filename);
    }

    public function getThumbnailFilePath($prefix, $filename, $level)
    {
        switch ($level) {
            case 2:
                $base = REL_DIR_FILES_THUMBNAILS_LEVEL2;
                break;
            case 3:
                $base = REL_DIR_FILES_THUMBNAILS_LEVEL3;
                break;
            default: // level 1
                $base = REL_DIR_FILES_THUMBNAILS;
                break;
        }
        $app = ApplicationFacade::getFacadeApplication();
        $format = $app->make(ThumbnailFormatService::class)->getFormatForFile($filename);
        $extension = $app->make(BitmapFormat::class)->getFormatFileExtension($format);
        $hi = $app->make('helper/file');
        $filename = $hi->replaceExtension($filename, $extension);

        return $base . $this->prefix($prefix, $filename);
    }

    /**
     * @return array
     */
    public function getIncomingDirectoryContents()
    {
        $app = ApplicationFacade::getFacadeApplication();
        $incoming = $app->make(Incoming::class);
        $fs = $incoming->getIncomingFilesystem();
        $items = $fs->listContents($incoming->getIncomingPath());

        return $items;
    }

    /**
     * Serializes an array of strings into format suitable for multi-uploader.
     *
     * example for format:
     * '*.flv;*.jpg;*.gif;*.jpeg;*.ico;*.docx;*.xla;*.png;*.psd;*.swf;*.doc;*.txt;*.xls;*.csv;*.pdf;*.tiff;*.rtf;*.m4a;*.mov;*.wmv;*.mpeg;*.mpg;*.wav;*.avi;*.mp4;*.mp3;*.qt;*.ppt;*.kml'
     *
     * @param array $types
     *
     * @return string
     */
    public function serializeUploadFileExtensions($types)
    {
        $serialized = '';
        $types = preg_replace('{[^a-z0-9]}i', '', $types);
        foreach ($types as $type) {
            $serialized .= '*.'.$type.';';
        }
        //removing trailing ; unclear if multiupload will choke on that or not
        $serialized = substr($serialized, 0, strlen($serialized) - 1);

        return $serialized;
    }

    /**
     * Unserializes an array of strings from format suitable for multi-uploader.
     *
     * example for format:
     * '*.flv;*.jpg;*.gif;*.jpeg;*.ico;*.docx;*.xla;*.png;*.psd;*.swf;*.doc;*.txt;*.xls;*.csv;*.pdf;*.tiff;*.rtf;*.m4a;*.mov;*.wmv;*.mpeg;*.mpg;*.wav;*.avi;*.mp4;*.mp3;*.qt;*.ppt;*.kml'
     *
     * @param string $types
     *
     * @return array
     */
    public function unSerializeUploadFileExtensions($types)
    {
        //split by semi-colon
        $types = preg_split('{;}', $types, null, PREG_SPLIT_NO_EMPTY);
        $types = preg_replace('{[^a-z0-9]}i', '', $types);

        return $types;
    }

    /**
     * Returns an array of all allowed file extensions within the system.
     */
    public function getAllowedFileExtensions()
    {
        $whitelist = array_map('strtolower', $this->unserializeUploadFileExtensions(Config::get('concrete.upload.extensions')));
        $blacklist = array_map('strtolower', $this->unserializeUploadFileExtensions(Config::get('concrete.upload.extensions_blacklist')));
        $whitelistNotBlacklist = array_diff($whitelist, $blacklist);
        sort($whitelistNotBlacklist);

        return $whitelistNotBlacklist;
    }
}
