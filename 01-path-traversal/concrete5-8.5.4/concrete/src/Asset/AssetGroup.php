<?php
namespace Concrete\Core\Asset;

class AssetGroup
{
    /**
     * @var \Concrete\Core\Asset\AssetPointer[]
     */
    protected $assetPointers = array();

    /**
     * @var Asset[]
     */
    protected $assets = array();

    /**
     * @param AssetPointer $ap
     *
     * @return bool
     */
    public function contains(AssetPointer $ap)
    {
        return isset($this->assetPointers[$ap->getIdentifier()]);
    }

    /**
     * @param AssetGroup $item
     */
    public function addGroup(AssetGroup $item)
    {
        $assetPointers = $item->getAssetPointers();
        foreach ($assetPointers as $assetPointer) {
            if (!$this->contains($assetPointer)) {
                $this->assetPointers[$assetPointer->getIdentifier()] = $assetPointer;
            }
        }
    }

    /**
     * @param Asset $asset
     */
    public function addAsset(Asset $asset)
    {
        if ($asset->getAssetHandle()) {
            $this->add($asset->getAssetPointer());
        } else {
            // doesn't check anything. this is useful for layouts, etc... other handle-less assets.
            $this->assets[] = $asset;
        }
    }

    /**
     * @param AssetPointer $ap
     */
    public function add(AssetPointer $ap)
    {
        if (!$this->contains($ap)) {
            $this->assetPointers[$ap->getIdentifier()] = $ap;
        }
    }

    /**
     * @return Asset[]
     */
    public function getAssets()
    {
        return $this->assets;
    }

    /**
     * @return AssetPointer[]
     */
    public function getAssetPointers()
    {
        return $this->assetPointers;
    }
}
