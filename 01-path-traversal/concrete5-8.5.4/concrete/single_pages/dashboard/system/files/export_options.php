<?php
defined('C5_EXECUTE') or die('Access Denied.');

/* @var Concrete\Core\Form\Service\Form $form */
/* @var Concrete\Core\Validation\CSRF\Token $token */
/* @var Concrete\Core\Page\View\PageView $view */

/* @var bool $csvAddBom */
?>
<form method="POST" action="<?= $view->action('submit') ?>">
    <?= $token->output('ccm-export-options') ?>

    <?= $form->label('', t('CSV')) ?>
    <div class="checkbox">
        <label>
            <?= $form->checkbox('csvAddBom', '1', $csvAddBom) ?>
            <?= t('Include the BOM (Byte-Order Mark) in generated CSV files') ?>
        </label>
    </div>

    <div class="ccm-dashboard-form-actions-wrapper">
        <div class="ccm-dashboard-form-actions">
            <div class="pull-right">
                <button type="submit" class="btn btn-primary"><?= t('Save') ?></button>
            </div>
        </div>
    </div>

</form>
