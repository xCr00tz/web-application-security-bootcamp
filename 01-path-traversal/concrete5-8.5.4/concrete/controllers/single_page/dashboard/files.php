<?php
namespace Concrete\Controller\SinglePage\Dashboard;

use Concrete\Core\Page\Controller\DashboardPageController;

class Files extends DashboardPageController
{
    public function view()
    {
        $this->redirect('/dashboard/files/search');
    }
}
