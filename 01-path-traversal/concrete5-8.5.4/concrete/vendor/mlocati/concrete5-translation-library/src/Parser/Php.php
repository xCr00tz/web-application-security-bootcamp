<?php

namespace C5TL\Parser;

/**
 * Extract translatable strings from PHP files (functions t(), tc() and t2()).
 */
class Php extends \C5TL\Parser
{
    /**
     * {@inheritdoc}
     *
     * @see \C5TL\Parser::getParserName()
     */
    public function getParserName()
    {
        return function_exists('t') ? t('PHP Parser') : 'PHP Parser';
    }

    /**
     * {@inheritdoc}
     *
     * @see \C5TL\Parser::canParseDirectory()
     */
    public function canParseDirectory()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     *
     * @see \C5TL\Parser::parseDirectoryDo()
     */
    protected function parseDirectoryDo(\Gettext\Translations $translations, $rootDirectory, $relativePath, $subParsersFilter, $exclude3rdParty)
    {
        $phpFiles = array();
        foreach (array_merge(array(''), $this->getDirectoryStructure($rootDirectory, $exclude3rdParty)) as $child) {
            $fullDirectoryPath = ($child === '') ? $rootDirectory : "$rootDirectory/$child";
            $contents = @scandir($fullDirectoryPath);
            if ($contents === false) {
                throw new \Exception("Unable to parse directory $fullDirectoryPath");
            }
            foreach ($contents as $file) {
                if ($file[0] !== '.') {
                    $fullFilePath = "$fullDirectoryPath/$file";
                    if (preg_match('/^(.*)\.php$/i', $file) && is_file($fullFilePath)) {
                        $phpFiles[] = ($child === '') ? $file : "$child/$file";
                    }
                }
            }
        }
        if (count($phpFiles) > 0) {
            if (\C5TL\Gettext::commandIsAvailable('xgettext')) {
                $newTranslations = static::parseDirectoryDo_xgettext($rootDirectory, $phpFiles);
            } else {
                $newTranslations = static::parseDirectoryDo_php($rootDirectory, $phpFiles);
            }
            if ($newTranslations->count() > 0) {
                if ($relativePath !== '') {
                    foreach ($newTranslations as $newTranslation) {
                        static::fixExtractedComments($newTranslation);
                        $references = $newTranslation->getReferences();
                        $newTranslation->deleteReferences();
                        foreach ($references as $reference) {
                            $newTranslation->addReference($relativePath.'/'.$reference[0], $reference[1]);
                        }
                        $translations[] = $newTranslation;
                    }
                } else {
                    foreach ($newTranslations as $newTranslation) {
                        static::fixExtractedComments($newTranslation);
                        $translations[] = $newTranslation;
                    }
                }
            }
        }
    }

    protected static function fixExtractedComments(\Gettext\Translation $translation)
    {
        $extractedComments = $translation->getExtractedComments();
        $n = count($extractedComments);
        if ($n > 0) {
            $m = null;
            $translation->deleteExtractedComments();
            foreach (array_unique($extractedComments) as $extractedComment) {
                if (preg_match('/^\s*i18n:?\s*(.*)\s*$/s', $extractedComment, $m)) {
                    $translation->addExtractedComment($m[1]);
                } else {
                    $translation->addExtractedComment($extractedComment);
                }
            }
        }
    }

    /**
     * Extracts translatable strings from PHP files with xgettext.
     *
     * @param string        $rootDirectory The base root directory
     * @param array[string] $phpFiles      The relative paths to the PHP files to be parsed
     *
     * @throws \Exception Throws an \Exception in case of problems
     *
     * @return \Gettext\Translations
     */
    protected static function parseDirectoryDo_xgettext($rootDirectory, $phpFiles)
    {
        try {
            $tempDirectory = \C5TL\Options::getTemporaryDirectory();
            $tempFileList = @tempnam($tempDirectory, 'cil');
            if ($tempFileList === false) {
                throw new \Exception(t('Unable to create a temporary file'));
            }
            if (@file_put_contents($tempFileList, implode("\n", $phpFiles)) === false) {
                global $php_errormsg;
                if (isset($php_errormsg) && $php_errormsg) {
                    throw new \Exception("Error writing a temporary file: $php_errormsg");
                } else {
                    throw new \Exception('Error writing a temporary file');
                }
            }
            $tempFilePot = @tempnam($tempDirectory, 'cil');
            if ($tempFilePot === false) {
                throw new \Exception(t('Unable to create a temporary file'));
            }
            if (DIRECTORY_SEPARATOR === '\\') {
                $line = 'cd /D '.escapeshellarg(str_replace('/', '\\', $rootDirectory));
            } else {
                $line = 'cd '.escapeshellarg($rootDirectory);
            }
            $line .= ' && xgettext';
            $line .= ' --default-domain=messages'; // Domain
            $line .= ' --output='.escapeshellarg(basename($tempFilePot)); // Output .pot file name
            $line .= ' --output-dir='.escapeshellarg(dirname($tempFilePot)); // Output .pot folder name
            $line .= ' --language=PHP'; // Source files are in php
            $line .= ' --from-code=UTF-8'; // Source files are in utf-8
            $line .= ' --add-comments=i18n'; // Place comment blocks preceding keyword lines in output file if they start with '// i18n: '
            $line .= ' --keyword'; // Don't use default keywords
            $line .= ' --keyword=t:1'; // Look for the first argument of the "t" function for extracting translatable text in singular form
            $line .= ' --keyword=t2:1,2'; // Look for the first and second arguments of the "t2" function for extracting both the singular and plural forms
            $line .= ' --keyword=tc:1c,2'; // Look for the first argument of the "tc" function for extracting translation context, and the second argument is the translatable text in singular form.
            $line .= ' --no-escape'; // Do not use C escapes in output
            $line .= ' --add-location'; // Generate '#: filename:line' lines
            $line .= ' --files-from='.escapeshellarg($tempFileList); // Get list of input files from file
            $line .= ' 2>&1';
            $output = array();
            $rc = null;
            @exec($line, $output, $rc);
            @unlink($tempFileList);
            unset($tempFileList);
            if (!is_int($rc)) {
                $rc = -1;
            }
            if (!is_array($output)) {
                $output = array();
            }
            if ($rc !== 0) {
                throw new \Exception('xgettext failed: '.implode("\n", $output));
            }
            $newTranslations = \Gettext\Translations::fromPoFile($tempFilePot);
            @unlink($tempFilePot);
            unset($tempFilePot);
        } catch (\Exception $x) {
            if (isset($tempFilePot) && @is_file($tempFilePot)) {
                @unlink($tempFilePot);
            }
            if (isset($tempFileList) && @is_file($tempFileList)) {
                @unlink($tempFileList);
            }
            throw $x;
        }

        return $newTranslations;
    }

    /**
     * Extracts translatable strings from PHP files with xgettext.
     *
     * @param string        $rootDirectory The base root directory
     * @param array[string] $phpFiles      The relative paths to the PHP files to be parsed
     *
     * @throws \Exception Throws an \Exception in case of problems
     *
     * @return \Gettext\Translations
     */
    protected static function parseDirectoryDo_php($rootDirectory, $phpFiles)
    {
        $prefix = $rootDirectory.'/';
        $originalExtractComments = \Gettext\Extractors\PhpCode::$extractComments;
        \Gettext\Extractors\PhpCode::$extractComments = 'i18n';
        $originalFunctions = \Gettext\Extractors\PhpCode::$functions;
        \Gettext\Extractors\PhpCode::$functions['t'] = '__';
        \Gettext\Extractors\PhpCode::$functions['t2'] = 'n__';
        \Gettext\Extractors\PhpCode::$functions['tc'] = 'p__';
        try {
            $absFiles = array_map(
                function ($phpFile) use ($prefix) {
                    return $prefix.$phpFile;
                },
                $phpFiles
            );
            $newTranslations = \Gettext\Translations::fromPhpCodeFile($absFiles);
            \Gettext\Extractors\PhpCode::$extractComments = $originalExtractComments;
            \Gettext\Extractors\PhpCode::$functions = $originalFunctions;
        } catch (\Exception $x) {
            \Gettext\Extractors\PhpCode::$extractComments = $originalExtractComments;
            \Gettext\Extractors\PhpCode::$functions = $originalFunctions;
            throw $x;
        }

        $startAt = strlen($prefix);
        foreach ($newTranslations as $newTranslation) {
            $references = $newTranslation->getReferences();
            $newTranslation->deleteReferences();
            foreach ($references as $reference) {
                $newTranslation->addReference(substr($reference[0], $startAt), $reference[1]);
            }
        }

        return $newTranslations;
    }
}
