<?php

namespace C5TL\Parser\DynamicItem;

/**
 * Extract translatable data from AuthenticationTypes.
 */
class AuthenticationType extends DynamicItem
{
    /**
     * {@inheritdoc}
     *
     * @see \C5TL\Parser\DynamicItem\DynamicItem::getParsedItemNames()
     */
    public function getParsedItemNames()
    {
        return function_exists('t') ? t('Authentiation type names') : 'Authentiation type names';
    }

    /**
     * {@inheritdoc}
     *
     * @see \C5TL\Parser\DynamicItem\DynamicItem::parseManual()
     */
    public function parseManual(\Gettext\Translations $translations, $concrete5version)
    {
        if (class_exists('\Concrete\Core\Authentication\AuthenticationType', true) && method_exists('\Concrete\Core\Authentication\AuthenticationType', 'getList')) {
            foreach (\Concrete\Core\Authentication\AuthenticationType::getList() as $at) {
                $this->addTranslation($translations, $at->getAuthenticationTypeName(), 'AuthenticationType');
            }
        }
    }
}
