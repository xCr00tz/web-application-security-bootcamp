<?php
namespace Concrete\Controller\Dialog\Block;

use Concrete\Controller\Backend\UserInterface\Block as BackendInterfaceBlockController;
use Concrete\Core\Block\CustomStyle;
use Concrete\Core\Block\View\BlockView;
use Concrete\Core\Page\EditResponse;
use Concrete\Core\Page\Type\Composer\Control\BlockControl;
use Concrete\Core\Page\Type\Composer\FormLayoutSetControl;
use Concrete\Core\StyleCustomizer\Inline\StyleSet;

class Design extends BackendInterfaceBlockController
{
    protected $viewPath = '/dialogs/block/design';

    protected function canAccess()
    {
        return $this->permissions->canEditBlockDesign()
        || $this->permissions->canEditBlockCustomTemplate();
    }

    public function reset()
    {
        $b = $this->getBlockToEdit();
        $b->resetCustomStyle();
        $pr = new EditResponse();
        $pr->setPage($this->page);
        $pr->setAdditionalDataAttribute('aID', $this->area->getAreaID());
        $pr->setAdditionalDataAttribute('arHandle', $this->area->getAreaHandle());
        $pr->setAdditionalDataAttribute('originalBlockID', $this->block->getBlockID());
        $pr->setAdditionalDataAttribute('bID', $b->getBlockID());
        $pr->setMessage(t('Custom design reset.'));
        $pr->outputJSON();
    }

    public function submit()
    {
        if ($this->validateAction() && $this->canAccess()) {
            $b = $this->getBlockToEdit();
            $oldStyle = $b->getCustomStyle();
            if (is_object($oldStyle)) {
                $oldStyleSet = $oldStyle->getStyleSet();
            } else {
                $oldStyleSet = null;
            }

            $r = $this->request->request->all();
            $set = StyleSet::populateFromRequest($this->request);
            if (is_object($set)) {
                $set->save();
                $b->setCustomStyleSet($set);
            } elseif ($oldStyleSet) {
                $b->resetCustomStyle();
            }

            if (isset($r['enableBlockContainer'])) {
                if ($r['enableBlockContainer'] === '-1') {
                    $b->resetBlockContainerSettings();
                }
                if ($r['enableBlockContainer'] === '0' ||
                    $r['enableBlockContainer'] === '1') {
                    $b->setCustomContainerSettings($r['enableBlockContainer']);
                }
            }

            if ($this->permissions->canEditBlockCustomTemplate()) {
                $data = [];
                $data['bFilename'] = $r['bFilename'];
                $b->updateBlockInformation($data);
            }

            $pr = new EditResponse();
            $pr->setPage($this->page);
            $pr->setAdditionalDataAttribute('aID', $this->area->getAreaID());
            $pr->setAdditionalDataAttribute('arHandle', $this->area->getAreaHandle());
            $pr->setAdditionalDataAttribute('originalBlockID', $this->block->getBlockID());

            if (is_object($oldStyleSet)) {
                $pr->setAdditionalDataAttribute('oldIssID', $oldStyleSet->getID());
            }

            if (is_object($set)) {
                $pr->setAdditionalDataAttribute('issID', $set->getID());
                if ($this->area->isGlobalArea()) {
                    $b->setBlockAreaObject($this->area); // We need this for CSS: https://github.com/concrete5/concrete5/issues/3135
                }
                $style = new CustomStyle($set, $b, $this->page->getCollectionThemeObject());
                $css = $style->getCSS();
                if ($css !== '') {
                    $pr->setAdditionalDataAttribute('css', $style->getStyleWrapper($css));
                }
            }

            $pr->setAdditionalDataAttribute('bID', $b->getBlockID());
            $pr->setMessage(t('Design updated.'));
            $pr->outputJSON();
        }
    }

    public function view()
    {
        $btc = $this->block->getInstance();
        $btc->outputAutoHeaderItems();
        $bv = new BlockView($this->block);
        $bv->addScopeItems(['c' => $this->page, 'a' => $this->area, 'dialogController' => $this]);
        $this->set('bv', $bv);

        $canEditCustomTemplate = false;
        if ($this->permissions->canEditBlockCustomTemplate()) {
            $canEditCustomTemplate = true;
            switch ($this->block->getBlockTypeHandle()) {
                case BLOCK_HANDLE_SCRAPBOOK_PROXY:
                    $bi = $this->block->getInstance();
                    $bx = \Block::getByID($bi->getOriginalBlockID());
                    $bt = \BlockType::getByID($bx->getBlockTypeID());
                    $bFilename = $bx->getBlockFilename();
                    break;
                case BLOCK_HANDLE_PAGE_TYPE_OUTPUT_PROXY:
                    $bi = $this->block->getInstance();
                    $output = $bi->getComposerOutputControlObject();
                    $control = FormLayoutSetControl::getByID($output->getPageTypeComposerFormLayoutSetControlID());
                    $object = $control->getPageTypeComposerControlObject();
                    if ($object instanceof BlockControl) {
                        $bt = $object->getBlockTypeObject();
                    }
                    $bFilename = $this->block->getBlockFilename();
                    break;
                default:
                    $bt = \BlockType::getByID($this->block->getBlockTypeID());
                    $bFilename = $this->block->getBlockFilename();
                    break;
            }
            $templates = [];
            if (is_object($bt)) {
                $templates = $bt->getBlockTypeCustomTemplates($this->block);
            }
            $this->set('bFilename', $bFilename);
            $this->set('templates', $templates);
        }
        $this->set('canEditCustomTemplate', $canEditCustomTemplate);
    }
}
