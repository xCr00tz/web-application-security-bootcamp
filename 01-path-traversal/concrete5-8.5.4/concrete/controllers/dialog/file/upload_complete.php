<?php
namespace Concrete\Controller\Dialog\File;

use Concrete\Controller\Dialog\File\Bulk\Properties as BulkPropertiesController;

class UploadComplete extends BulkPropertiesController
{
    protected $viewPath = '/dialogs/file/upload_complete';

    protected function checkPermissions($file)
    {
        $fp = new \Permissions($file);
        return $fp->canViewFileInFileManager();
    }

    public function view()
    {
        parent::view();
        $this->requireAsset('javascript', 'jquery/tristate');

        $sets = array();
        $ids = array();
        $canEditFiles = true;
        foreach ($this->files as $file) {
            $fp = new \Permissions($file);
            if (!$fp->canEditFileProperties()) {
                $canEditFiles = false;
            }
            $ids[] = $file->getFileID();
            foreach ($file->getFileSets() as $set) {
                $o = $set->getJSONObject();
                if (!in_array($o, $sets)) {
                    $sets[] = $o;
                }
            }
        }
        $this->set('canEditFiles', $canEditFiles);
        $this->set('filesets', $sets);
        $this->set('fileIDs', $ids);

        if (count($this->files) == 1) {
            $propertiesController = new Properties();
            $propertiesController->setFileObject($this->files[0]);
            $propertiesController->on_start();
            $this->set('propertiesController', $propertiesController);
        }

        $bulkPropertiesController = new BulkPropertiesController();
        $bulkPropertiesController->setFiles($this->files);
        $bulkPropertiesController->on_start();
        // stupid hoops we have to go through due to tokens
        $this->set('bulkPropertiesController', $bulkPropertiesController);
    }
}
