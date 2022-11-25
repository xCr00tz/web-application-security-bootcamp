<?php
namespace Concrete\Core\Block\View;

use Concrete\Core\Block\Events\BlockBeforeRender;
use Concrete\Core\Block\Events\BlockOutput;
use Concrete\Core\Localization\Localization;
use Concrete\Core\Support\Facade\Application;
use Concrete\Core\View\AbstractView;
use Config;
use Concrete\Core\Area\Area;
use Environment;
use Concrete\Core\User\User;
use Page;
use Concrete\Core\Block\Block;
use View;

/**
 * Work with the rendered view of a block.
 *
 * <code>
 * $b = $this->getBlockObject();
 * $bv = new BlockView($b);
 * </code>
 */
class BlockView extends AbstractView
{
    protected $block;
    protected $area;
    protected $blockType;
    protected $blockTypePkgHandle;
    protected $blockViewHeaderFile;
    protected $blockViewFooterFile;
    protected $outputContent = false;
    protected $viewToRender = false;
    protected $viewPerformed = false;
    protected $showControls = true;
    protected $didPullFromOutputCache = false;

    /**
     * Construct a block view object.
     *
     * @param mixed $mixed block or block type to view
     */
    protected function constructView($mixed)
    {
        if ($mixed instanceof Block) {
            $this->blockType = $mixed->getBlockTypeObject();
            $this->block = $mixed;
            $this->area = $mixed->getBlockAreaObject();
        } else {
            $this->blockType = $mixed;
            if ($this->blockType->controller) {
                $this->controller = $this->blockType->controller;
            }
        }
        $this->blockTypePkgHandle = $this->blockType->getPackageHandle();
        if (!isset($this->controller)) {
            if (isset($this->block)) {
                $this->controller = $this->block->getInstance();
                $this->controller->setBlockObject($this->block);
            } else {
                $this->controller = $this->blockType->getController();
            }
        }
    }

    public function showControls()
    {
        return $this->showControls;
    }

    public function disableControls()
    {
        $this->showControls = false;
    }

    public function setAreaObject(Area $area)
    {
        $this->area = $area;
    }

    public function getAreaObject()
    {
        return $this->area;
    }

    public function start($state)
    {
        if (is_object($this->area)) {
            $this->controller->setAreaObject($this->area);
        }
        /*
         * Legacy shit
         */
        if ($state instanceof Block) {
            $this->block = $state;
            $this->viewToRender = 'view';
        } else {
            $this->viewToRender = $state;
        }
    }

    /**
     * @deprecated in views, use $controller->getActionURL() using the same arguments
     *
     * @return \Concrete\Core\Url\UrlImmutable|null
     */
    public function action($task)
    {
        return call_user_func_array([$this->controller, 'getActionURL'], func_get_args());
    }

    public function startRender()
    {
    }

    public function setupRender()
    {
        $this->runControllerTask();

        $view = $this->viewToRender;

        $env = Environment::get();
        if ($this->viewToRender == 'scrapbook') {
            $scrapbookTemplate = $this->getBlockPath(
                    FILENAME_BLOCK_VIEW_SCRAPBOOK
                ) . '/' . FILENAME_BLOCK_VIEW_SCRAPBOOK;
            if (file_exists($scrapbookTemplate)) {
                $view = 'scrapbook';
            } else {
                $view = 'view';
            }
        }
        $customFilenameToRender = null;
        if (!in_array($this->viewToRender, ['view', 'add', 'edit', 'scrapbook'])) {
            // then we're trying to render a custom view file, which we'll pass to the bottom functions as $_filename
            $customFilenameToRender = $view . '.php';
            $view = 'view';
        }
        switch ($view) {
            case 'view':
                if (is_object($this->block) && is_object($this->area)) {
                    $this->setBlockViewHeaderFile(DIR_FILES_ELEMENTS_CORE . '/block_header_view.php');
                    $this->setBlockViewFooterFile(DIR_FILES_ELEMENTS_CORE . '/block_footer_view.php');
                }
                if ($this->controller->blockViewRenderOverride) {
                    $template = DIRNAME_BLOCKS . '/' . $this->blockType->getBlockTypeHandle(
                        ) . '/' . $this->controller->blockViewRenderOverride . '.php';
                    $this->setViewTemplate($env->getPath($template, $this->blockTypePkgHandle));
                } else {
                    $bFilename = false;
                    if ($this->block) {
                        $bFilename = $this->block->getBlockFilename();
                        $bvt = new BlockViewTemplate($this->block);
                        if (!$bFilename && is_object($this->area)) {
                            $templates = $this->area->getAreaCustomTemplates();
                            if (isset($templates[$this->block->getBlockTypeHandle()])) {
                                $bFilename = $templates[$this->block->getBlockTypeHandle()];
                            }
                        }
                    } else {
                        $bvt = new BlockViewTemplate($this->blockType);
                    }
                    if ($bFilename) {
                        $bvt->setBlockCustomTemplate(
                            $bFilename
                        ); // this is PROBABLY already set by the method above, but in the case that it's passed by area we have to set it here
                    } else {
                        if ($customFilenameToRender) {
                            $bvt->setBlockCustomRender($customFilenameToRender);
                        }
                    }

                    $this->setViewTemplate($bvt->getTemplate());
                }
                break;
            case 'add':
                if ($this->controller->blockViewRenderOverride) {
                    $template = DIRNAME_BLOCKS . '/' . $this->blockType->getBlockTypeHandle(
                        ) . '/' . $this->controller->blockViewRenderOverride . '.php';
                } else {
                    $template = DIRNAME_BLOCKS . '/' . $this->blockType->getBlockTypeHandle(
                        ) . '/' . FILENAME_BLOCK_ADD;
                }
                $this->setViewTemplate($env->getPath($template, $this->blockTypePkgHandle));
                break;
            case 'scrapbook':
                $this->setViewTemplate(
                    $env->getPath(
                        DIRNAME_BLOCKS . '/' . $this->blockType->getBlockTypeHandle(
                        ) . '/' . FILENAME_BLOCK_VIEW_SCRAPBOOK,
                        $this->blockTypePkgHandle
                    )
                );
                break;
            case 'edit':
                if ($this->controller->blockViewRenderOverride) {
                    $template = DIRNAME_BLOCKS . '/' . $this->blockType->getBlockTypeHandle(
                        ) . '/' . $this->controller->blockViewRenderOverride . '.php';
                } else {
                    $template = DIRNAME_BLOCKS . '/' . $this->blockType->getBlockTypeHandle(
                        ) . '/' . FILENAME_BLOCK_EDIT;
                }
                $this->setBlockViewHeaderFile(DIR_FILES_ELEMENTS_CORE . '/block_header_edit.php');
                $this->setBlockViewFooterFile(DIR_FILES_ELEMENTS_CORE . '/block_footer_edit.php');
                $this->setViewTemplate($env->getPath($template, $this->blockTypePkgHandle));
                break;
        }

        $this->viewPerformed = $view;
    }

    protected function onBeforeGetContents()
    {
        if (in_array($this->viewPerformed, ['scrapbook', 'view'])) {
            $this->controller->runAction('on_page_view', [$this]);
            $this->controller->outputAutoHeaderItems();
        }
    }

    /**
     * Echo block contents.
     *
     * @param array $scopeItems array of items to render (outputContent, blockViewHeaderFile, blockViewFooterFile)
     */
    public function renderViewContents($scopeItems)
    {
        $shouldRender = function () {
            $app = Application::getFacadeApplication();

            // If you hook into this event and use `preventRendering()`,
            // you can prevent the block from being displayed.
            $event = new BlockBeforeRender($this->block);
            $app->make('director')->dispatch('on_block_before_render', $event);

            return $event->proceed();
        };

        if (!$shouldRender()) {
            return;
        }

        unset($shouldRender);

        extract($scopeItems);
        if (!$this->outputContent) {
            ob_start();
            include $this->template;
            $this->outputContent = ob_get_contents();
            ob_end_clean();
        }

        // In case the view changes any scope items, the block header/footer
        // could break without extracting the scope items again. This can happen
        // if the block view changes any local variables such as the `$b`
        // variable which is possible as they can be user defined.
        extract($scopeItems);

        // The translatable texts in the block header/footer need to be printed
        // out in the system language.
        $loc = Localization::getInstance();
        $loc->pushActiveContext(Localization::CONTEXT_UI);

        if ($this->blockViewHeaderFile) {
            include $this->blockViewHeaderFile;
        }

        $this->controller->registerViewAssets($this->outputContent);

        $this->onBeforeGetContents();
        $this->fireOnBlockOutputEvent();
        echo $this->outputContent;
        $this->onAfterGetContents();

        if ($this->blockViewFooterFile) {
            include $this->blockViewFooterFile;
        }

        $loc->popActiveContext();
    }

    protected function setBlockViewHeaderFile($file)
    {
        $this->blockViewHeaderFile = $file;
    }

    protected function setBlockViewFooterFile($file)
    {
        $this->blockViewFooterFile = $file;
    }

    public function postProcessViewContents($contents)
    {
        return $contents;
    }

    /**
     * Returns the path to the current block's directory.
     *
     *
     * @deprecated
     *
     * @return string
     */
    public function getBlockPath($filename = null)
    {
        $obj = $this->blockType;
        if (file_exists(DIR_FILES_BLOCK_TYPES . '/' . $obj->getBlockTypeHandle() . '/' . $filename)) {
            $base = DIR_FILES_BLOCK_TYPES . '/' . $obj->getBlockTypeHandle();
        } else {
            if ($obj->getPackageID() > 0) {
                if (is_dir(DIR_PACKAGES . '/' . $obj->getPackageHandle())) {
                    $base = DIR_PACKAGES . '/' . $obj->getPackageHandle(
                        ) . '/' . DIRNAME_BLOCKS . '/' . $obj->getBlockTypeHandle();
                } else {
                    $base = DIR_PACKAGES_CORE . '/' . $obj->getPackageHandle(
                        ) . '/' . DIRNAME_BLOCKS . '/' . $obj->getBlockTypeHandle();
                }
            } else {
                $base = DIR_FILES_BLOCK_TYPES_CORE . '/' . $obj->getBlockTypeHandle();
            }
        }

        return $base;
    }

    /**
     * Returns a relative path to the current block's directory. If a filename is specified it will be appended and searched for as well.
     *
     * @return string
     */
    public function getBlockURL($filename = null)
    {
        $obj = $this->blockType;
        if ($obj->getPackageID() > 0) {
            if (is_dir(DIR_PACKAGES_CORE . '/' . $obj->getPackageHandle())) {
                $base = ASSETS_URL . '/' . DIRNAME_PACKAGES . '/' . $obj->getPackageHandle(
                    ) . '/' . DIRNAME_BLOCKS . '/' . $obj->getBlockTypeHandle();
            } else {
                $base = DIR_REL . '/' . DIRNAME_PACKAGES . '/' . $obj->getPackageHandle(
                    ) . '/' . DIRNAME_BLOCKS . '/' . $obj->getBlockTypeHandle();
            }
        } else {
            if (file_exists(DIR_FILES_BLOCK_TYPES . '/' . $obj->getBlockTypeHandle() . '/' . $filename)) {
                $base = REL_DIR_APPLICATION . '/' . DIRNAME_BLOCKS . '/' . $obj->getBlockTypeHandle();
            } else {
                $base = ASSETS_URL . '/' . DIRNAME_BLOCKS . '/' . $obj->getBlockTypeHandle();
            }
        }

        return $base;
    }

    public function inc($fileToInclude, $args = [])
    {
        extract($args);
        extract($this->getScopeItems());
        $env = Environment::get();
        include $env->getPath(
            DIRNAME_BLOCKS . '/' . $this->blockType->getBlockTypeHandle() . '/' . $fileToInclude,
            $this->blockTypePkgHandle
        );
    }

    public function getScopeItems()
    {
        $items = parent::getScopeItems();
        $items['b'] = $this->block;
        $items['bt'] = $this->blockType;
        $items['a'] = $this->area;

        return $items;
    }

    protected function useBlockCache()
    {
        $u = Application::getFacadeApplication()->make(User::class);
        $c = Page::getCurrentPage();
        if ($this->viewToRender == 'view' && Config::get('concrete.cache.blocks') && $this->block instanceof Block
            && $this->block->cacheBlockOutput() && is_object($c) && $c->isPageDraft() === false
        ) {
            if ((!$u->isRegistered() || ($this->block->cacheBlockOutputForRegisteredUsers())) &&
                (($_SERVER['REQUEST_METHOD'] != 'POST' || ($this->block->cacheBlockOutputOnPost() == true)))
            ) {
                return true;
            }
        }

        return false;
    }

    public function field($field)
    {
        return $field;
    }

    public function usedBlockCacheDuringRender()
    {
        return $this->didPullFromOutputCache;
    }

    public function finishRender($contents)
    {
        if ($this->useBlockCache() && !$this->didPullFromOutputCache) {
            $this->block->setBlockCachedOutput(
                $this->outputContent,
                $this->block->getBlockOutputCacheLifetime(),
                $this->area
            );
        }

        return $contents;
    }

    public function runControllerTask()
    {
        $this->controller->on_start();

        if ($this->useBlockCache()) {
            $this->didPullFromOutputCache = true;
            $this->outputContent = $this->block->getBlockCachedOutput($this->area);
        }

        if (!$this->outputContent) {
            $this->didPullFromOutputCache = false;
            if (in_array($this->viewToRender, ['view', 'add', 'edit', 'composer'])) {
                $method = $this->viewToRender;
            } else {
                $method = 'view';
            }
            $passthru = false;
            if ($method == 'view' && is_object($this->block)) {
                $c = Page::getCurrentPage();
                if (is_object($c)) {
                    $cnt = $c->getController();
                    $controller = $cnt->getPassThruBlockController($this->block);
                    if (is_object($controller)) {
                        $passthru = true;
                        $this->controller = $controller;
                    }
                }
            }

            $parameters = [];
            if (!$passthru) {
                $this->controller->runAction($method, $parameters);
            }
            $this->controller->on_before_render();
        }
    }

    /**
     * Legacy.
     */
    public function getThemePath()
    {
        $v = View::getInstance();

        return $v->getThemePath();
    }

    /**
     * Fire an event just before the block is outputted on the page.
     *
     * Custom code can modify the block contents before
     * the block contents are 'echoed' out on the page.
     *
     * @since 8.4.1
     */
    private function fireOnBlockOutputEvent()
    {
        $event = new BlockOutput($this->block);
        $event->setContents($this->outputContent);

        $app = Application::getFacadeApplication();
        $app->make('director')->dispatch('on_block_output', $event);

        $this->outputContent = $event->getContents();
    }
}
