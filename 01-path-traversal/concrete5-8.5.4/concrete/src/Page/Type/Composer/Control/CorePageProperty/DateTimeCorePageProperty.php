<?php

namespace Concrete\Core\Page\Type\Composer\Control\CorePageProperty;

use Loader;
use Concrete\Core\Page\Page;
use Concrete\Core\Attribute\FontAwesomeIconFormatter;

class DateTimeCorePageProperty extends CorePageProperty
{
    public function __construct()
    {
        $this->setCorePagePropertyHandle('date_time');
        $this->setPageTypeComposerControlIconFormatter(new FontAwesomeIconFormatter('calendar'));
    }

    public function getPageTypeComposerControlName()
    {
        return tc('PageTypeComposerControlName', 'Public Date/Time');
    }

    public function publishToPage(Page $c, $data, $controls)
    {
        $this->addPageTypeComposerControlRequestValue('cDatePublic', Loader::helper('form/date_time')->translate('date_time', $data));
        parent::publishToPage($c, $data, $controls);
    }

    public function validate()
    {
        $e = Loader::helper('validation/error');
        
        $val = $this->getRequestValue();
        
        if ($val['date_time_dt'] && $val['date_time_h'] && $val['date_time_m']) {
            $datetime = $val['date_time_dt'] . ' ' . $val['date_time_h'] . ':' . $val['date_time_m'] . ':00';
        } else {
            $datetime = $this->getPageTypeComposerControlDraftValue();
        }
        
        if (!strtotime($datetime)) {
            $control = $this->getPageTypeComposerFormLayoutSetControlObject();
            $e->add(t('You haven\'t chosen a valid %s', $control->getPageTypeComposerControlDisplayLabel()));

            return $e;
        }
    }

    public function getPageTypeComposerControlDraftValue()
    {
        if (is_object($this->page)) {
            $c = $this->page;

            return $c->getCollectionDatePublic();
        }
    }
}
