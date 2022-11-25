<?php
namespace Concrete\Core\Activity;

use Config;
use Marketplace;
use Concrete\Core\File\Service\File;

/**
 * Class Newsflow.
 *
 * A class used for retrieving the latest news and updates from Concrete5. This is a singleton class that should be
 * instantiated via Newsflow::getInstance(). This object is prevented from being created if the config file has the
 * 'concrete.external.news' setting set to false.
 *
 * \@package Concrete\Core\Activity
 */
class Newsflow
{
    /**
     * Constant for if newsflow is manually disabled (like through a config entry).
     */
    const E_NEWSFLOW_SUPPORT_MANUALLY_DISABLED = 21;

    /**
     * @var bool if the site is connected to concrete5.org
     */
    protected $isConnected = false;
    /**
     * @var bool|int if there is a connection error and the error number
     */
    protected $connectionError = false;
    /**
     * @var null|NewsflowSlotItem[]
     */
    protected $slots = null;

    public function __construct()
    {
        if (!Config::get('concrete.external.news')) {
            $this->connectionError = self::E_NEWSFLOW_SUPPORT_MANUALLY_DISABLED;

            return;
        }
    }

    /**
     * @return bool Returns true if there is a connection error, false if there is no error.
     */
    public function hasConnectionError()
    {
        return $this->connectionError !== false;
    }

    /**
     * @return bool|int Returns false if there are no errors, or an int corresponding to one of the E_* class constants
     */
    public function getConnectionError()
    {
        return $this->connectionError;
    }

    /**
     * Retrieves a NewsflowItem object for a given collection ID.
     *
     * @param int $cID
     *
     * @return bool|NewsflowItem Returns a NewsflowItem object, false if there was an error or one could not be located.
     */
    public function getEditionByID($cID)
    {
        if (!$this->hasConnectionError()) {
            $fileService = new File();
            $appVersion = Config::get('concrete.version');
            $cfToken = Marketplace::getSiteToken();
            $path = Config::get('concrete.urls.newsflow') . '/' . DISPATCHER_FILENAME . '/?_ccm_view_external=1&appVersion=' . $appVersion . '&cID=' . rawurlencode($cID) . '&cfToken=' . rawurlencode($cfToken);
            $response = $fileService->getContents($path);
            $ni = new NewsflowItem();
            $obj = $ni->parseResponse($response);

            return $obj;
        }

        return false;
    }

    /**
     * Retrieves a NewsflowItem object for a given collection path.
     *
     * @param $cPath
     *
     * @return bool|NewsflowItem
     */
    public function getEditionByPath($cPath)
    {
        $cPath = trim($cPath, '/');
        if (!$this->hasConnectionError()) {
            $fileService = new File();
            $appVersion = Config::get('concrete.version');
            $cfToken = Marketplace::getSiteToken();
            $path = Config::get('concrete.urls.newsflow') . '/' . DISPATCHER_FILENAME . '/' . $cPath . '/-/view_external?cfToken=' . rawurlencode($cfToken) . '&appVersion=' . $appVersion;
            $response = $fileService->getContents($path);
            $ni = new NewsflowItem();
            $obj = $ni->parseResponse($response);

            return $obj;
        }

        return false;
    }

    /**
     * Retrieves an array of NewsflowSlotItems.
     *
     * @return NewsflowSlotItem[]|null
     */
    public function getSlotContents()
    {
        if ($this->slots === null) {
            $fileService = new File();
            $appVersion = Config::get('concrete.version');
            $cfToken = Marketplace::getSiteToken();
            $url = Config::get('concrete.urls.newsflow') . Config::get('concrete.urls.paths.newsflow_slot_content');
            $path = $url . '?cfToken=' . rawurlencode($cfToken) . '&appVersion=' . $appVersion;
            $response = $fileService->getContents($path);
            $nsi = new NewsflowSlotItem();
            $this->slots = $nsi->parseResponse($response);
        }

        return $this->slots;
    }
}
