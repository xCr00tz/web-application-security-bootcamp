<?php
defined('C5_EXECUTE') or die("Access Denied.");
/* @var $control Concrete\Core\Page\Type\Composer\FormLayoutSetControl */

$der = Concrete\Core\Page\Type\Composer\Control\Type\Type::getByID($control->getPageTypeComposerControlTypeID());
$pto = $control->getPageTypeComposerControlObject();
$name = '';
if (strlen($control->getPageTypeComposerFormLayoutSetControlCustomLabel())) {
    $name = $pto->getPageTypeComposerControlName() . ' ';
}

?>
<tr class="ccm-item-set-control" data-page-type-composer-form-layout-control-set-control-id="<?=$control->getPageTypeComposerFormLayoutSetControlID()?>">
	<td style="white-space: nowrap; width: 20%;">
		<?= $control->getPageTypeComposerControlDisplayLabel(); ?>
	</td>

	<td style="width: 100%;">
		<span class="text-muted"><?= $der->getPageTypeComposerControlTypeDisplayName() ?></span>
		<?php if ($name): ?>
			<span class="text-muted">(<?= trim($name) ?>)</span>
		<?php endif ?>
	</td>

	<td>
		<span class="text-muted"><?php if ($control->isPageTypeComposerFormLayoutSetControlRequired()) { echo t('Required'); } ?></span>
	</td>

	<td style="text-align: right; white-space: nowrap;">
		<ul class="ccm-item-set-controls">
			<li><a href="#" data-command="move-set-control" style="cursor: move"><i class="fa fa-arrows"></i></a></li>
			<li><a data-command="edit-form-set-control" href="<?=REL_DIR_FILES_TOOLS_REQUIRED?>/page_types/composer/form/edit_control?ptComposerFormLayoutSetControlID=<?=$control->getPageTypeComposerFormLayoutSetControlID()?>" class="dialog-launch" dialog-width="400" dialog-height="auto" dialog-modal="true" dialog-title="<?=t('Edit Form Control')?>"><i class="fa fa-pencil"></i></a></li>
			<li><a href="#" data-delete-set-control="<?=$control->getPageTypeComposerFormLayoutSetControlID()?>"><i class="fa fa-trash-o"></i></a></li>
		</ul>

		<div style="display: none">
			<div data-delete-set-control-dialog="<?=$control->getPageTypeComposerFormLayoutSetControlID()?>">
				<?=t("Delete this control? This cannot be undone.")?>
				<?=Core::make('helper/validation/token')->output('delete_set_control')?>

				<div class="dialog-buttons">
					<button class="btn btn-default pull-left" onclick="jQuery.fn.dialog.closeTop()"><?=t('Cancel')?></button>
					<button class="btn btn-danger pull-right" onclick="Composer.deleteFromLayoutSetControl(<?=$control->getPageTypeComposerFormLayoutSetControlID()?>)"><?=t('Delete Control')?></button>
				</div>

			</div>
		</div>
	</td>
</tr>
