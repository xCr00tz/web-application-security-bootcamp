<?php

namespace Concrete\Block\Content;

use Concrete\Core\Block\BlockController;
use Concrete\Core\Editor\LinkAbstractor;
use Concrete\Core\File\Tracker\FileTrackableInterface;
use Concrete\Core\Statistics\UsageTracker\AggregateTracker;

/**
 * The controller for the content block.
 *
 * @package Blocks
 * @subpackage Content
 *
 * @author Andrew Embler <andrew@concrete5.org>
 * @copyright  Copyright (c) 2003-2012 Concrete5. (http://www.concrete5.org)
 * @license    http://www.concrete5.org/license/     MIT License
 */
class Controller extends BlockController implements FileTrackableInterface
{
    public $content;
    protected $btTable = 'btContentLocal';
    protected $btInterfaceWidth = 600;
    protected $btInterfaceHeight = 465;
    protected $btCacheBlockRecord = true;
    protected $btCacheBlockOutput = true;
    protected $btCacheBlockOutputOnPost = true;
    protected $btSupportsInlineEdit = true;
    protected $btSupportsInlineAdd = true;
    protected $btCacheBlockOutputForRegisteredUsers = false;
    protected $btCacheBlockOutputLifetime = 0; //until manually updated or cleared

    /**
     * @var \Concrete\Core\Statistics\UsageTracker\AggregateTracker|null
     */
    protected $tracker;

    public function __construct($obj = null, AggregateTracker $tracker = null)
    {
        parent::__construct($obj);
        $this->tracker = $tracker;
    }

    public function getBlockTypeDescription()
    {
        return t('HTML/WYSIWYG Editor Content.');
    }

    public function getBlockTypeName()
    {
        return t('Content');
    }

    public function getContent()
    {
        return LinkAbstractor::translateFrom($this->content);
    }

    public function getSearchableContent()
    {
        return $this->content;
    }

    public function br2nl($str)
    {
        $str = str_replace("\r\n", "\n", $str);
        $str = str_replace("<br />\n", "\n", $str);

        return $str;
    }

    public function registerViewAssets($outputContent = '')
    {
        if (preg_match('/data-concrete5-link-lightbox/i', $outputContent)) {
            $this->requireAsset('core/lightbox');
        }
    }

    public function view()
    {
        $this->set('content', $this->getContent());
    }

    public function getContentEditMode()
    {
        return LinkAbstractor::translateFromEditMode($this->content);
    }

    public function getImportData($blockNode, $page)
    {
        $content = $blockNode->data->record->content;
        $content = LinkAbstractor::import($content);
        $args = ['content' => $content];

        return $args;
    }

    public function export(\SimpleXMLElement $blockNode)
    {
        $data = $blockNode->addChild('data');
        $data->addAttribute('table', $this->btTable);
        $record = $data->addChild('record');
        $cnode = $record->addChild('content');
        $node = dom_import_simplexml($cnode);
        $no = $node->ownerDocument;
        $content = LinkAbstractor::export($this->content);
        $cdata = $no->createCDataSection($content);
        $node->appendChild($cdata);
    }

    public function save($args)
    {
        if (isset($args['content'])) {
            $args['content'] = LinkAbstractor::translateTo($args['content']);
        }
        parent::save($args);
        $this->getTracker()->track($this);
    }

    /**
     * Tell the tracker to forget us when we are deleted.
     */
    public function delete()
    {
        parent::delete();
        $this->getTracker()->forget($this);
    }

    public function getUsedFiles()
    {
        return array_merge(
            $this->getUsedFilesImages(),
            $this->getUsedFilesDownload()
        );
    }

    public function getUsedCollection()
    {
        return $this->getCollectionObject();
    }

    protected function getUsedFilesImages()
    {
        $files = [];
        $matches = [];
        if (preg_match_all('/\<concrete-picture[^>]*?fID\s*=\s*[\'"]([^\'"]*?)[\'"]/i', $this->content, $matches)) {
            list(, $ids) = $matches;
            foreach ($ids as $id) {
                $files[] = (int) $id;
            }
        }

        return $files;
    }

    protected function getUsedFilesDownload()
    {
        preg_match_all('(FID_DL_\d+)', $this->content, $matches);

        return array_map(
            function ($match) {
                return (int) (explode('_', $match)[2]);
            },
            $matches[0]
        );
    }

    /**
     * @return \Concrete\Core\Statistics\UsageTracker\AggregateTracker
     */
    protected function getTracker()
    {
        if ($this->tracker === null) {
            $this->tracker = $this->app->make(AggregateTracker::class);
        }

        return $this->tracker;
    }
}
