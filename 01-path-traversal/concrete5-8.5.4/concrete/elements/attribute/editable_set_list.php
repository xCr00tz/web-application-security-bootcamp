<?php
/**
 * @var \Concrete\Core\Attribute\Key\Category
 * @var $objects
 * @var $object
 * @var callback $permissionsCallback
 * @var array $permissionsArguments
 * @var string $clearAction
 * @var string $saveAction
 */
$sets = $category->getController()->getSetManager()->getAttributeSets();
foreach ($sets as $set) {
    echo '<h3>' . $set->getAttributeSetDisplayName() . '</h3><hr/>';
    foreach ($set->getAttributeKeys() as $key => $ak) {
        Loader::element(
            'attribute/editable_attribute',
            array(
                'ak' => $ak,
                'object' => isset($object) ? $object : null,
                'objects' => isset($objects) ? $objects : null,
                'saveAction' => $saveAction,
                'clearAction' => $clearAction,
                'permissionsCallback' => $permissionsCallback,
                'permissionArguments' => isset($permissionsArguments) ? $permissionsArguments : null,
            )
        );
    }
}

$attributeKeys = $category->getController()->getSetManager()->getUnassignedAttributeKeys();
if (count($attributeKeys) > 0) {
    echo '<h3>' . t('Other') . '</h3><hr/>';
    foreach ($attributeKeys as $key => $ak) {
        Loader::element(
            'attribute/editable_attribute',
            array(
                'ak' => $ak,
                'object' => isset($object) ? $object : null,
                'objects' => isset($objects) ? $objects : null,
                'saveAction' => $saveAction,
                'clearAction' => $clearAction,
                'permissionsCallback' => $permissionsCallback,
                'permissionArguments' => $permissionsArguments,
            )
        );
    }
}
