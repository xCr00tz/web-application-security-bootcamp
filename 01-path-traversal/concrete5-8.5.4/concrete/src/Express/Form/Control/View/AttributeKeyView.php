<?php
namespace Concrete\Core\Express\Form\Control\View;

use Concrete\Core\Entity\Express\Control\AttributeKeyControl;
use Concrete\Core\Entity\Express\Control\Control;
use Concrete\Core\Filesystem\TemplateLocator;
use Concrete\Core\Express\Form\Context\ContextInterface;

class AttributeKeyView extends View
{

    /**
     * AttributeKeyView constructor.
     * @param ContextInterface $context
     * @param AttributeKeyControl $control
     */
    public function __construct(ContextInterface $context, Control $control)
    {
        parent::__construct($context, $control);
        if ($entry = $context->getEntry()) {
            $value = $entry->getAttributeValueObject($control->getAttributeKey());
            if ($value) {
                $value = $value->getDisplaySanitizedValue();
            } else {
                $value = '';
            }
            $this->addScopeItem('value', $value);
        }
    }

    public function getControlID()
    {
        return null;
    }

    public function createTemplateLocator()
    {
        $locator = new TemplateLocator('attribute_key');
        return $locator;
    }


}
