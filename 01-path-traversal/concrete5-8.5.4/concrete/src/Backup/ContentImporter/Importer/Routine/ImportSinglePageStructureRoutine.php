<?php
namespace Concrete\Core\Backup\ContentImporter\Importer\Routine;

use Concrete\Core\Page\Single;
use Concrete\Core\Support\Facade\Application;

class ImportSinglePageStructureRoutine extends AbstractRoutine implements SpecifiableHomePageRoutineInterface
{
    public function getHandle()
    {
        return 'single_pages';
    }

    public function setHomePage($page)
    {
        $this->home = $page;
    }

    public function import(\SimpleXMLElement $sx)
    {

        if (isset($sx->singlepages)) {
            $app = Application::getFacadeApplication();
            $defaultSite = $app->make('site')->getDefault();
            foreach ($sx->singlepages->page as $p) {
                $pkg = static::getPackageObject($p['package']);

                if (isset($p['global']) && (string) $p['global'] === 'true') {
                    $spl = Single::addGlobal($p['path'], $pkg);
                } else {
                    $root = false;
                    if (isset($p['root']) && (string) $p['root'] === 'true') {
                        $root = true;
                    }

                    $siteTree = null;
                    if (isset($this->home)) {
                        $siteTree = $this->home->getSiteTreeObject();
                    } else {
                        $home = \Page::getByID(\Page::getHomePageID());
                        $siteTree = $home->getSiteTreeObject();
                    }

                    if ($siteTree === null) {
                        $siteTree = $defaultSite;
                    }

                    $spl = Single::createPageInTree($p['path'], $siteTree, $root, $pkg);
                }

                if (is_object($spl)) {
                    if ($p['name']) {
                        $spl->update(array('cName' => $p['name'], 'cDescription' => $p['description']));
                    }

                    if ($p['custom-path']) {
                        $spl->setCanonicalPagePath((string) $p['custom-path'], false);
                    }
                }
            }
        }
    }
}
