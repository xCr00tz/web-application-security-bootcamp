<?php
namespace Concrete\Core\Page\Search\Field\Field;

use Concrete\Core\Search\Field\AbstractField;
use Concrete\Core\Search\ItemList\ItemList;

class IncludePageAliasesField extends AbstractField
{
    protected $requestVariables = [
        'includeAliases',
    ];

    public function getKey()
    {
        return 'include_page_aliases';
    }

    public function getDisplayName()
    {
        return t('Include Page Aliases');
    }

    /**
     * @param \Concrete\Core\Page\PageList $list
     */
    public function filterList(ItemList $list)
    {
        if ($this->data['includeAliases'] === '1') {
            $list->includeAliases();
        }
    }

    public function renderSearchField()
    {
        $form = \Core::make('helper/form');
        $html = '<div>';
        $html .= '<div class="radio"><label>' . $form->radio('includeAliases', 0, $this->data['includeAliases']) . ' ' . t('No') . '</label></div>';
        $html .= '<div class="radio"><label>' . $form->radio('includeAliases', 1, $this->data['includeAliases']) . ' ' . t('Yes') . '</label></div>';
        $html .= '</div>';
        return $html;
    }
}
