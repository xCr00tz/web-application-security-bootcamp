<?php
namespace Concrete\Attribute\UserSelector;

use Concrete\Core\Attribute\Controller as AttributeTypeController;
use Concrete\Core\Attribute\FontAwesomeIconFormatter;
use Concrete\Core\Entity\Attribute\Value\Value\NumberValue;
use Concrete\Core\User\User;
use Concrete\Core\User\UserInfo;

class Controller extends AttributeTypeController
{
    public function getIconFormatter()
    {
        return new FontAwesomeIconFormatter('user');
    }

    public function getAttributeValueClass()
    {
        return NumberValue::class;
    }

    public function form()
    {
        $value = null;
        if (is_object($this->attributeValue)) {
            $value = $this->getAttributeValue()->getValue();
        }
        if (!$value) {
            if ($this->request->query->has($this->attributeKey->getAttributeKeyHandle())) {
                $value = $this->createAttributeValue((int) $this->request->query->get($this->attributeKey->getAttributeKeyHandle()));
            }
        }
        $this->set('value', $value);
        $this->set('user_selector', $this->app->make('helper/form/user_selector'));
    }

    public function getDisplayValue()
    {
        $uID = $this->getAttributeValue()->getValue();
        $ui = UserInfo::getByID($uID);
        if (is_object($ui)) {
            return '<a href="'.$ui->getUserPublicProfileUrl().'">'.$ui->getUserName().'</a>';
        }
    }

    public function getPlainTextValue()
    {
        $uID = $this->getAttributeValue()->getValue();
        $user = User::getByUserID($uID);
        if (is_object($user)) {
            return $user->getUserName();
        }
    }

    public function createAttributeValue($value)
    {
        $av = new NumberValue();
        if ($value instanceof User) {
            $value = $value->getUserID();
        }
        $av->setValue($value);

        return $av;
    }

    public function createAttributeValueFromRequest()
    {
        $data = $this->post();
        if (isset($data['value'])) {
            return $this->createAttributeValue((int) $data['value']);
        }
    }

    public function importValue(\SimpleXMLElement $akv)
    {
        if (isset($akv->value)) {
            $user = User::getByUserID($akv->value);
            if (is_object($user)) {
                return $user->getUserID();
            }
        }
    }

    public function exportValue(\SimpleXMLElement $akn)
    {
        if (is_object($this->attributeValue)) {
            $uID = $this->getAttributeValue()->getValue();
            $user = User::getByUserID($uID);
            $avn = $akn->addChild('value', $user->getUserID());
        }
    }
}
