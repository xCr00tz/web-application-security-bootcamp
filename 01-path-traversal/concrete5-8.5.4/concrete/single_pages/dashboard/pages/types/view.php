<?php defined('C5_EXECUTE') or die("Access Denied."); ?>

<?php if (($this->controller->getTask() == 'submit' || $this->controller->getTask() == 'edit') && is_object($pagetype)) {
    ?>

<form method="post" action="<?=$view->action('submit', $pagetype->getPageTypeID())?>">
<?=View::element('page_types/form/base', array('pagetype' => $pagetype)); ?>
<div class="ccm-dashboard-form-actions-wrapper">
<div class="ccm-dashboard-form-actions">
	<a href="<?=$view->url('/dashboard/pages/types')?>" class="btn btn-default pull-left"><?=t('Cancel')?></a>
	<button class="pull-right btn btn-primary" type="submit"><?=t('Save')?></button>
</div>
</div>

</form>

<?php
} else {
    $pk = PermissionKey::getByHandle('access_page_type_permissions');
    ?>

    <?php if (count($pagetypes) > 0) {
    ?>

	<table class="table table-striped">
	<thead>
		<tr>
			<th><?=t('Name')?></th>
            <th></th>
		</tr>
	</thead>
	<tbody>
		<?php foreach ($pagetypes as $cm) {
    $cmp = new Permissions($cm);
    ?>
		<tr>
			<td class="page-type-name"><?=$cm->getPageTypeDisplayName()?></td>
			<td class="page-type-tasks">
                <?php if ($cmp->canEditPageType()) {
    ?>
    				<a href="<?=$view->action('edit', $cm->getPageTypeID())?>" class="btn btn-default btn-xs"><?=t('Basic Details')?></a>
	    			<a href="<?=$view->url('/dashboard/pages/types/form', $cm->getPageTypeID())?>" class="btn btn-default btn-xs"><?=t('Edit Form')?></a>
		    		<a href="<?=$view->url('/dashboard/pages/types/output', $cm->getPageTypeID())?>" class="btn btn-default btn-xs"><?=t('Output')?></a>
                    <a href="<?=$view->url('/dashboard/pages/types/attributes', $cm->getPageTypeID())?>" class="btn btn-default btn-xs"><?=t('Attributes')?></a>
                <?php
}
    ?>
                <?php if ($cmp->canEditPageTypePermissions()) {
    ?>
					<a href="<?=$view->url('/dashboard/pages/types/permissions', $cm->getPageTypeID())?>" class="btn btn-default btn-xs"><?=t('Permissions')?></a>
				<?php
}
    ?>
                <a href="#" data-duplicate="<?=$cm->getPageTypeID()?>" class="btn btn-default btn-xs"><?=t('Copy')?></a>
                <div style="display: none">
                    <div data-duplicate-dialog="<?=$cm->getPageTypeID()?>" class="ccm-ui">
                        <form class="form-stacked" data-duplicate-form="<?=$cm->getPageTypeID()?>" action="<?=$view->action('duplicate', $cm->getPageTypeID())?>" method="post">
                            <div class="form-group">
                                <label class="control-label"><?=t('Name')?></label>
                                <input type="text" name="ptName" class="form-control">
                            </div>
                            <div class="form-group">
                                <label class="control-label"><?=t('Handle')?></label>
                                <input type="text" name="ptHandle" class="form-control">
                            </div>
							<?php if (count($siteTypes)) { ?>
								<div class="form-group">
									<label class="control-label"><?=t('Site Type')?></label>
									<?=$form->select('siteType', $siteTypes)?>
								</div>
							<?php } ?>
                            <?=Loader::helper('validation/token')->output('duplicate_page_type')?>
                        </form>
                        <div class="dialog-buttons">
                            <button onclick="jQuery.fn.dialog.closeTop()" class="btn btn-default pull-left"><?=t('Cancel')?></button>
                            <button onclick="$('form[data-duplicate-form=<?=$cm->getPageTypeID()?>]').submit()" class="btn btn-primary pull-right"><?=t('Copy')?></button>
                        </div>
                    </div>
                </div>

                <?php if ($cmp->canDeletePageType()) {
    ?>
    				<a href="#" data-delete="<?=$cm->getPageTypeID()?>" class="btn btn-default btn-xs btn-danger"><?=t('Delete')?></a>
                <?php
}
    ?>
				<div style="display: none">
					<div data-delete-dialog="<?=$cm->getPageTypeID()?>" class="ccm-ui">
						<form data-delete-form="<?=$cm->getPageTypeID()?>" action="<?=$view->action('delete', $cm->getPageTypeID())?>" method="post">
						<?=t("Delete this page type? This cannot be undone.")?>
						<?=Loader::helper('validation/token')->output('delete_page_type')?>
						</form>
                        <div class="dialog-buttons">
                            <button onclick="jQuery.fn.dialog.closeTop()" class="btn btn-default pull-left"><?=t('Cancel')?></button>
                            <button onclick="$('form[data-delete-form=<?=$cm->getPageTypeID()?>]').submit()" class="btn btn-danger pull-right"><?=t('Delete')?></button>
                        </div>
					</div>
				</div>
			</td>
		</tr>
		<?php
}
    ?>
	</tbody>
	</table>

	<?php
} else {
    ?>
		<p><?=t('You have not created any page types yet.')?></p>
		<a href="<?=$view->url('/dashboard/pages/types/add', $siteTypeID)?>" class="btn btn-primary"><?=t('Add Page Type')?></a>
	<?php
}
    ?>

	<style>
	td.page-type-name {
		width: 100%;
	}

	td.page-type-tasks {
		text-align: right !important;
		white-space: nowrap;
	}
	</style>

	<script>
	$(function() {
		$('a[data-delete]').on('click', function() {
			var ptID = $(this).attr('data-delete');
            jQuery.fn.dialog.open({
                element: 'div[data-delete-dialog=' + ptID + ']',
                modal: true,
                width: 410,
                title: '<?=t("Delete Page Type")?>',
                height: 150
            });
		});
        $('a[data-duplicate]').on('click', function() {
            var ptID = $(this).attr('data-duplicate');
            jQuery.fn.dialog.open({
                element: 'div[data-duplicate-dialog=' + ptID + ']',
                modal: true,
                width: 320,
                title: '<?=t("Copy Page Type")?>',
                height: 360
            });
        });
    });
	</script>

<?php
} ?>
