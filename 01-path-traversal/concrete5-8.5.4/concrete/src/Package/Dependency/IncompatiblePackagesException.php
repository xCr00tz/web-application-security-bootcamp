<?php

namespace Concrete\Core\Package\Dependency;

use Concrete\Core\Package\Package;

/**
 * Package dependency failure: a package doesn't want another package.
 */
class IncompatiblePackagesException extends DependencyException
{
    /**
     * The package that doesn't want the other package.
     *
     * @var \Concrete\Core\Package\Package
     */
    protected $blockingPackage;

    /**
     * The incompatible package.
     *
     * @var \Concrete\Core\Package\Package
     */
    protected $incompatiblePackage;

    /**
     * Initialize the instance.
     *
     * @param \Concrete\Core\Package\Package $blockingPackage the package that doesn't want the other package
     * @param \Concrete\Core\Package\Package $incompatiblePackage the incompatible package
     */
    public function __construct(Package $blockingPackage, Package $incompatiblePackage)
    {
        $this->blockingPackage = $blockingPackage;
        $this->incompatiblePackage = $incompatiblePackage;
        parent::__construct(t(
            'The package "%1$s" can\'t be installed if the package "%2$s" is installed.',
            $incompatiblePackage->getPackageName(),
            $blockingPackage->getPackageName()
        ));
    }

    /**
     * Get the package that can't be uninstalled.
     *
     * @return \Concrete\Core\Package\Package
     */
    public function getBlockingPackage()
    {
        return $this->blockingPackage;
    }

    /**
     * Get the incompatible package.
     *
     * @return \Concrete\Core\Package\Package
     */
    public function getIncompatiblePackage()
    {
        return $this->incompatiblePackage;
    }
}
