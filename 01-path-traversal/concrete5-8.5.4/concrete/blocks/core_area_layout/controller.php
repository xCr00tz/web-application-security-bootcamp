<?php

namespace Concrete\Block\CoreAreaLayout;

use Concrete\Core\Area\Layout\CustomLayout;
use Concrete\Core\Area\Layout\CustomLayout as CustomAreaLayout;
use Concrete\Core\Area\Layout\Layout as AreaLayout;
use Concrete\Core\Area\Layout\Preset\Preset as AreaLayoutPreset;
use Concrete\Core\Area\Layout\PresetLayout;
use Concrete\Core\Area\Layout\ThemeGridLayout;
use Concrete\Core\Area\Layout\ThemeGridLayout as ThemeGridAreaLayout;
use Concrete\Core\Area\SubArea;
use Concrete\Core\Asset\CssAsset;
use Concrete\Core\Block\BlockController;
use Concrete\Core\StyleCustomizer\Inline\StyleSet;
use Core;
use Database;
use Page;
use URL;

class Controller extends BlockController
{
    protected $btSupportsInlineAdd = true;
    protected $btSupportsInlineEdit = true;
    protected $btTable = 'btCoreAreaLayout';
    protected $btIsInternal = true;
    protected $btCacheSettingsInitialized = false;

    public function cacheBlockOutput()
    {
        $this->setupCacheSettings();

        return $this->btCacheBlockOutput;
    }

    public function cacheBlockOutputOnPost()
    {
        $this->setupCacheSettings();

        return $this->btCacheBlockOutputOnPost;
    }

    public function getBlockTypeCacheOutputLifetime()
    {
        $this->setupCacheSettings();

        return $this->btCacheBlockOutputLifetime;
    }

    public function getBlockTypeDescription()
    {
        return t('Proxy block for area layouts.');
    }

    public function getBlockTypeName()
    {
        return t('Area Layout');
    }

    public function registerViewAssets($outputContent = '')
    {
        if (is_object($this->block) && $this->block->getBlockFilename() == 'parallax') {
            $this->requireAsset('javascript', 'jquery');
            $this->requireAsset('javascript', 'core/frontend/parallax-image');
        }

        $arLayout = $this->getAreaLayoutObject();
        if (is_object($arLayout)) {            
            if ($arLayout instanceof CustomLayout) {
                $asset = new CssAsset();
                $asset->setAssetURL(URL::to('/ccm/system/css/layout', $arLayout->getAreaLayoutID()));
                $asset->setAssetSupportsMinification(false);
                $asset->setAssetSupportsCombination(false);
                $this->requireAsset($asset);
            }
        }
    }

    public function duplicate($newBID)
    {
        $db = Database::connection();
        parent::duplicate($newBID);
        $ar = AreaLayout::getByID($this->arLayoutID);
        $nr = $ar->duplicate();
        $db->Execute(
            'update btCoreAreaLayout set arLayoutID = ? where bID = ?',
            [$nr->getAreaLayoutID(), $newBID]
        );
    }

    public function getAreaLayoutObject()
    {
        if ($this->arLayoutID) {
            $arLayout = AreaLayout::getByID($this->arLayoutID);
            $b = $this->getBlockObject();
            if (is_object($arLayout) && is_object($b)) {
                $arLayout->setBlockObject($b);
            }

            return $arLayout;
        }
    }

    public function delete()
    {
        $arLayout = $this->getAreaLayoutObject();
        if (is_object($arLayout)) {
            $arLayout->delete();
        }
        parent::delete();
    }

    public function export(\SimpleXMLElement $blockNode)
    {
        $layout = $this->getAreaLayoutObject();
        $layout->export($blockNode);
    }

    public function save($post)
    {
        if (isset($post['arLayoutID']) && !isset($post['arLayoutEdit'])) {
            // terribly lame, but in import we pass arLayoutID and we also pass it in the post of editing a layout
            // We need to somehow differentiate the two. If it's JUST arLayoutID we're using the migration tool
            // if it includes arLayoutEdit (which is included in the form) then run the standrd block save.
            // we are passing it in directly –likely from import
            $values = ['arLayoutID' => $post['arLayoutID']];
            parent::save($values);

            return;
        }
        $db = Database::connection();
        $arLayoutID = $db->GetOne('select arLayoutID from btCoreAreaLayout where bID = ?', [$this->bID]);
        if (!$arLayoutID) {
            $arLayout = $this->addFromPost($post);
        } else {
            $arLayout = AreaLayout::getByID($arLayoutID);
            if ($arLayout instanceof PresetLayout) {
                return;
            }
            // save spacing
            if ($arLayout->isAreaLayoutUsingThemeGridFramework()) {
                $columns = $arLayout->getAreaLayoutColumns();
                for ($i = 0; $i < count($columns); ++$i) {
                    $col = $columns[$i];
                    $span = ($post['span'][$i]) ? $post['span'][$i] : 0;
                    $offset = ($post['offset'][$i]) ? $post['offset'][$i] : 0;
                    $col->setAreaLayoutColumnSpan($span);
                    $col->setAreaLayoutColumnOffset($offset);
                }
            } else {
                $arLayout->setAreaLayoutColumnSpacing($post['spacing']);
                if ($post['isautomated']) {
                    $arLayout->disableAreaLayoutCustomColumnWidths();
                } else {
                    $arLayout->enableAreaLayoutCustomColumnWidths();
                    $columns = $arLayout->getAreaLayoutColumns();
                    for ($i = 0; $i < count($columns); ++$i) {
                        $col = $columns[$i];
                        $width = ($post['width'][$i]) ? $post['width'][$i] : 0;
                        $col->setAreaLayoutColumnWidth($width);
                    }
                }
            }
        }

        $values = ['arLayoutID' => $arLayout->getAreaLayoutID()];
        parent::save($values);
    }

    public function getImportData($blockNode, $page)
    {
        $args = [];
        if (isset($blockNode->arealayout)) {
            $type = (string) $blockNode->arealayout['type'];
            $node = $blockNode->arealayout;
            switch ($type) {
                case 'theme-grid':
                    $args['gridType'] = 'TG';
                    $args['arLayoutMaxColumns'] = (string) $node['columns'];
                    $args['themeGridColumns'] = (int) (count($node->columns->column));
                    $args['offset'] = [];
                    $args['span'] = [];
                    $i = 0;
                    foreach ($node->columns->column as $column) {
                        $args['span'][$i] = (int) ($column['span']);
                        $args['offset'][$i] = (int) ($column['offset']);
                        ++$i;
                    }
                    break;
                case 'custom':
                    $args['gridType'] = 'FF';
                    $args['isautomated'] = true;
                    $args['spacing'] = (int) ($node['spacing']);
                    $args['columns'] = (int) (count($node->columns->column));
                    $customWidths = (int) ($node['custom-widths']);
                    if ($customWidths == 1) {
                        $args['isautomated'] = false;
                    }
                    $args['width'] = [];
                    $i = 0;
                    foreach ($node->columns->column as $column) {
                        $args['width'][$i] = (int) ($column['width']);
                        ++$i;
                    }
                    break;
            }
        }

        return $args;
    }

    public function addFromPost($post)
    {
        // we are adding a new layout
        switch ($post['gridType']) {
            case 'TG':
                $arLayout = ThemeGridAreaLayout::add();
                $arLayout->setAreaLayoutMaxColumns($post['arLayoutMaxColumns']);
                for ($i = 0; $i < $post['themeGridColumns']; ++$i) {
                    $span = ($post['span'][$i]) ? $post['span'][$i] : 0;
                    $offset = ($post['offset'][$i]) ? $post['offset'][$i] : 0;
                    $column = $arLayout->addLayoutColumn();
                    $column->setAreaLayoutColumnSpan($span);
                    $column->setAreaLayoutColumnOffset($offset);
                }
                break;
            case 'FF':
                if ((!$post['isautomated']) && $post['columns'] > 1) {
                    $iscustom = 1;
                } else {
                    $iscustom = 0;
                }
                $arLayout = CustomAreaLayout::add($post['spacing'], $iscustom);
                for ($i = 0; $i < $post['columns']; ++$i) {
                    $width = ($post['width'][$i]) ? $post['width'][$i] : 0;
                    $column = $arLayout->addLayoutColumn();
                    $column->setAreaLayoutColumnWidth($width);
                }
                break;
            default: // a preset
                $arLayoutPreset = AreaLayoutPreset::getByID($post['arLayoutPresetID']);
                $arLayout = PresetLayout::add($arLayoutPreset);
                foreach ($arLayoutPreset->getColumns() as $column) {
                    $arLayout->addLayoutColumn();
                }
                break;
        }

        return $arLayout;
    }

    public function view()
    {
        $b = $this->getBlockObject();
        $a = $b->getBlockAreaObject();
        $this->arLayout = $this->getAreaLayoutObject();
        if (is_object($this->arLayout)) {
            $this->arLayout->setAreaObject($a);
            $this->set('columns', $this->arLayout->getAreaLayoutColumns());
            $c = Page::getCurrentPage();
            $this->set('c', $c);

            $gf = false;
            if ($this->arLayout->isAreaLayoutUsingThemeGridFramework()) {
                $pt = $c->getCollectionThemeObject();
                $gf = $pt->getThemeGridFrameworkObject();
            }

            $formatter = $this->arLayout->getFormatter();
            $this->set('formatter', $formatter);
        } else {
            $this->set('columns', []);
        }
    }

    public function edit()
    {
        $this->addHeaderItem(Core::make('helper/html')->javascript('layouts.js'));
        $this->view();
        // since we set a render override in view() we have to explicitly declare edit
        if ($this->arLayout->isAreaLayoutUsingThemeGridFramework()) {
            $c = Page::getCurrentPage();
            $pt = $c->getCollectionThemeObject();
            $gf = $pt->getThemeGridFrameworkObject();
        }
        if ($this->arLayout instanceof ThemeGridLayout) {
            $this->set('enableThemeGrid', true);
            $this->set('themeGridFramework', $gf);
            $this->set('themeGridMaxColumns', $this->arLayout->getAreaLayoutMaxColumns());
            $this->set('themeGridName', $gf->getPageThemeGridFrameworkName());
            $this->render('edit_grid');
        } elseif ($this->arLayout instanceof CustomLayout) {
            $this->set('enableThemeGrid', false);
            $this->set('spacing', $this->arLayout->getAreaLayoutSpacing());
            $this->set('iscustom', $this->arLayout->hasAreaLayoutCustomColumnWidths());
            $this->set('maxColumns', 12);
            $this->render('edit');
        } else {
            $preset = $this->arLayout->getPresetObject();
            $this->set('selectedPreset', $preset);
            $this->render('edit_preset');
        }
        $this->set('columnsNum', count($this->arLayout->getAreaLayoutColumns()));
        $this->requireAsset('core/style-customizer');
    }

    public function add()
    {
        $this->addHeaderItem(Core::make('helper/html')->javascript('layouts.js'));
        $maxColumns = 12; // normally
        // now we check our active theme and see if it has other plans
        $c = Page::getCurrentPage();
        $pt = $c->getCollectionThemeObject();
        if (is_object($pt) && $pt->supportsGridFramework() && is_object(
                $this->area
            ) && $this->area->getAreaGridMaximumColumns()
        ) {
            $gf = $pt->getThemeGridFrameworkObject();
            $this->set('enableThemeGrid', true);
            $this->set('themeGridName', $gf->getPageThemeGridFrameworkName());
            $this->set('themeGridFramework', $gf);
            $this->set('themeGridMaxColumns', $this->area->getAreaGridMaximumColumns());
        } else {
            $this->set('enableThemeGrid', false);
        }
        $this->set('columnsNum', 1);
        $this->set('maxColumns', $maxColumns);
        $this->requireAsset('core/style-customizer');
    }

    protected function setupCacheSettings()
    {
        if ($this->btCacheSettingsInitialized || Page::getCurrentPage()->isEditMode()) {
            return;
        }

        $this->btCacheSettingsInitialized = true;

        $btCacheBlockOutput = true;
        $btCacheBlockOutputOnPost = true;
        $btCacheBlockOutputLifetime = 0;

        $c = $this->getCollectionObject();

        $blocks = [];
        $layout = $this->getAreaLayoutObject();
        $layout->setAreaObject($this->getAreaObject());
        if ($layout) {
            foreach ($layout->getAreaLayoutColumns() as $column) {
                $area = $column->getSubAreaObject();
                if ($area) {
                    foreach ($area->getAreaBlocksArray($c) as $block) {
                        $blocks[] = $block;
                    }
                }
            }
        }

        $arrAssetBlocks = [];

        foreach ($blocks as $b) {
            if ($b->overrideAreaPermissions()) {
                $btCacheBlockOutput = false;
                $btCacheBlockOutputOnPost = false;
                $btCacheBlockOutputLifetime = 0;
                break;
            }

            $btCacheBlockOutput = $btCacheBlockOutput && $b->cacheBlockOutput();
            $btCacheBlockOutputOnPost = $btCacheBlockOutputOnPost && $b->cacheBlockOutputOnPost();

            //As soon as we find something which cannot be cached, entire block cannot be cached, so stop checking.
            if (!$btCacheBlockOutput) {
                return;
            }

            if ($expires = $b->getBlockOutputCacheLifetime()) {
                if ($expires && $btCacheBlockOutputLifetime < $expires) {
                    $btCacheBlockOutputLifetime = $expires;
                }
            }

            $objController = $b->getController();
            if (is_callable([$objController, 'registerViewAssets'])) {
                $arrAssetBlocks[] = $objController;
            }
        }

        $this->btCacheBlockOutput = $btCacheBlockOutput;
        $this->btCacheBlockOutputOnPost = $btCacheBlockOutputOnPost;
        $this->btCacheBlockOutputLifetime = $btCacheBlockOutputLifetime;

        foreach ($arrAssetBlocks as $objController) {
            $objController->on_start();
            $objController->outputAutoHeaderItems();
            $objController->registerViewAssets();
        }
    }

    protected function importAdditionalData($b, $blockNode)
    {
        $controller = $b->getController();
        $arLayout = $controller->getAreaLayoutObject();

        $columns = $arLayout->getAreaLayoutColumns();
        $layoutArea = $b->getBlockAreaObject();
        $arLayout->setAreaObject($b->getBlockAreaObject());
        $page = $b->getBlockCollectionObject();

        $i = 0;
        foreach ($blockNode->arealayout->columns->column as $columnNode) {
            $column = $columns[$i];
            $as = new SubArea($column->getAreaLayoutColumnDisplayID(), $layoutArea->getAreaHandle(), $layoutArea->getAreaID());
            $as->load($page);
            $column->setAreaID($as->getAreaID());
            $area = $column->getAreaObject();
            if ($columnNode->style) {
                $set = StyleSet::import($columnNode->style);
                $page->setCustomStyleSet($area, $set);
            }
            foreach ($columnNode->block as $bx) {
                $bt = \BlockType::getByHandle($bx['type']);
                if (!is_object($bt)) {
                    throw new \Exception(t('Invalid block type handle: %s', (string) ($bx['type'])));
                }
                $btc = $bt->getController();
                $btc->import($page, $area->getAreaHandle(), $bx);
            }
            ++$i;
        }
    }
}
