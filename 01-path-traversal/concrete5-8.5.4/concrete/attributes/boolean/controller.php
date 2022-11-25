<?php

namespace Concrete\Attribute\Boolean;

use Concrete\Core\Attribute\Controller as AttributeTypeController;
use Concrete\Core\Attribute\FontAwesomeIconFormatter;
use Concrete\Core\Attribute\SimpleTextExportableAttributeInterface;
use Concrete\Core\Entity\Attribute\Key\Settings\BooleanSettings;
use Concrete\Core\Entity\Attribute\Value\Value\BooleanValue;
use Concrete\Core\Error\ErrorList\ErrorList;
use Concrete\Core\Search\ItemList\Database\AttributedItemList;
use Core;

class Controller extends AttributeTypeController implements SimpleTextExportableAttributeInterface
{
    protected $searchIndexFieldDefinition = ['type' => 'boolean', 'options' => ['default' => 0, 'notnull' => false]];

    public function getIconFormatter()
    {
        return new FontAwesomeIconFormatter('check-square');
    }

    public function searchForm($list)
    {
        $list->filterByAttribute($this->attributeKey->getAttributeKeyHandle(), $this->request('value'));

        return $list;
    }

    public function search()
    {
        $f = $this->app->make('helper/form');
        $checked = $this->request('value') == '1' ? true : false;
        echo '<div class="checkbox"><label>' . $f->checkbox($this->field('value'), 1, $checked) . ' ' . t('Yes') . '</label></div>';
    }

    public function filterByAttribute(AttributedItemList $list, $boolean, $comparison = '=')
    {
        $qb = $list->getQueryObject();
        $column = sprintf('ak_%s', $this->attributeKey->getAttributeKeyHandle());
        switch ($comparison) {
            case '<>':
            case '!=':
                $boolean = $boolean ? false : true;
                break;
        }
        if ($boolean) {
            $qb->andWhere("{$column} = 1");
        } else {
            $qb->andWhere("{$column} <> 1 or {$column} is null");
        }
    }

    public function exportValue(\SimpleXMLElement $akv)
    {
        $val = $this->attributeValue->getValue();
        $cnode = $akv->addChild('value', $val ? '1' : '0');

        return $cnode;
    }

    public function getCheckboxLabel()
    {
        if ($this->akCheckboxLabel) {
            return $this->akCheckboxLabel;
        }

        return $this->attributeKey->getAttributeKeyDisplayName();
    }

    public function exportKey($akey)
    {
        $this->load();
        $type = $akey->addChild('type');
        $type->addAttribute('checked', $this->akCheckedByDefault);
        $type->addAttribute('checkbox-label', $this->akCheckboxLabel);

        return $akey;
    }

    public function importKey(\SimpleXMLElement $akey)
    {
        $type = $this->getAttributeKeySettings();
        if (isset($akey->type)) {
            $checked = (string) $akey->type['checked'];
            $label = (string) $akey->type['checkbox-label'];
            if ($checked != '') {
                $type->setIsCheckedByDefault(true);
            }
            if ($label != '') {
                $type->setCheckboxLabel($label);
            }
        }

        return $type;
    }

    public function form()
    {
        $this->load();
        $checked = false;
        if (is_object($this->attributeValue)) {
            $value = $this->getAttributeValue()->getValue();
            $checked = $value == 1 ? true : false;
        } else {
            if ($this->akCheckedByDefault) {
                $checked = true;
            }
        }
        $this->set('checked', $checked);
    }

    public function type_form()
    {
        $this->set('form', Core::make('helper/form'));
        $this->load();
    }

    // run when we call setAttribute(), instead of saving through the UI
    public function createAttributeValue($value)
    {
        $v = new BooleanValue();
        $value = ($value == false || $value == '0') ? false : true;
        $v->setValue($value);

        return $v;
    }

    /**
     * {@inheritdoc}
     *
     * @see AttributeTypeController::createDefaultAttributeValue()
     */
    public function createDefaultAttributeValue()
    {
        $this->load();

        return $this->createAttributeValue($this->akCheckedByDefault ? true : false);
    }

    public function validateValue()
    {
        $v = $this->getAttributeValue()->getValue();

        return $v == 1;
    }

    public function getSearchIndexValue()
    {
        return $this->attributeValue->getValue() ? 1 : 0;
    }

    public function saveKey($data)
    {
        $type = $this->getAttributeKeySettings();

        $akCheckedByDefault = 0;
        if (isset($data['akCheckedByDefault']) && $data['akCheckedByDefault']) {
            $akCheckedByDefault = 1;
        }

        $type->setIsCheckedByDefault($akCheckedByDefault);
        $type->setCheckboxLabel($data['akCheckboxLabel']);

        return $type;
    }

    public function getAttributeValueClass()
    {
        return BooleanValue::class;
    }

    public function createAttributeValueFromRequest()
    {
        $data = $this->post();

        return $this->createAttributeValue(isset($data['value']) ? $data['value'] : false);
    }

    // if this gets run we assume we need it to be validated/checked
    public function validateForm($data)
    {
        return isset($data['value']) && $data['value'] == 1;
    }

    public function getAttributeKeySettingsClass()
    {
        return BooleanSettings::class;
    }

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Attribute\SimpleTextExportableAttributeInterface::getAttributeValueTextRepresentation()
     */
    public function getAttributeValueTextRepresentation()
    {
        $value = $this->getAttributeValueObject();
        if ($value === null || $value->getValue() === null) {
            $result = '';
        } else {
            $result = $value->getValue() ? '1' : '0';
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Attribute\SimpleTextExportableAttributeInterface::updateAttributeValueFromTextRepresentation()
     */
    public function updateAttributeValueFromTextRepresentation($textRepresentation, ErrorList $warnings)
    {
        $value = $this->getAttributeValueObject();
        $textRepresentation = trim($textRepresentation);
        if ($textRepresentation === '') {
            if ($value !== null) {
                $value->setValue(null);
            }
        } else {
            // false values: '0', 'no', 'true' (case insensitive)
            // true values: '1', 'yes', 'false' (case insensitive)
            $bool = filter_var($textRepresentation, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($bool === null) {
                $warnings->add(t('"%1$s" is not a valid boolean value for the attribute with handle %2$s', $textRepresentation, $this->attributeKey->getAttributeKeyHandle()));
            } else {
                if ($value === null) {
                    $value = $this->createAttributeValue($bool);
                } else {
                    $value->setValue($bool);
                }
            }
        }

        return $value;
    }

    protected function load()
    {
        $ak = $this->getAttributeKey();
        if (!is_object($ak)) {
            return false;
        }

        $settings = $ak
            ->getAttributeKeySettings();
        if ($settings) {
            $this->akCheckedByDefault = $settings->isCheckedByDefault();
            $this->akCheckboxLabel = $settings->getCheckboxLabel();
        }

        $this->set('akCheckboxLabel', $this->akCheckboxLabel);
        $this->set('akCheckedByDefault', $this->akCheckedByDefault);
    }
}
