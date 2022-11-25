<?php
defined('C5_EXECUTE') or die("Access Denied.");
?>

<section class="ccm-ui">
	<header><?=t('SEO')?></header>
	<form method="post" action="<?=$controller->action('submit')?>" class="ccm-panel-detail-content-form" data-dialog-form="seo" data-panel-detail-form="seo" data-action-after-save="reload">

	<?php if ($allowEditName) {
    ?>
	<div class="form-group">
		<label class="control-label" for="cName"><?=t('Name')?></label>
		<div>
			<input type="text" class="form-control" name="cName" id="cName" value="<?php echo h($c->getCollectionName())?>">
    	</div>
	</div>
	<?php
} ?>

	<?php if ($allowEditPaths && !$c->isGeneratedCollection()) {
    ?>
	<div class="form-group">
		<label class="control-label launch-tooltip" data-placement="bottom" title="<?=t('This page must always be available from at least one URL. This is that URL.')?>" class="launch-tooltip"><?=t('URL Slug')?></label>
		<div>
			<input type="text" class="form-control" name="cHandle" value="<?php echo $c->getCollectionHandle()?>" id="cHandle"><input type="hidden" name="oldCHandle" id="oldCHandle" value="<?php echo $c->getCollectionHandle()?>">
		</div>
	</div>
	<?php
} ?>

	<?php foreach ($attributes as $ak) {
		$av = $c->getAttributeValueObject($ak);
		$view = $ak->getControlView(new \Concrete\Core\Attribute\Context\ComposerContext());
		$renderer = $view->getControlRenderer();
		$view->setValue($av);
		print $renderer->render();

    ?>
	<?php
} ?>

        <?php if (isset($sitemap) && $sitemap) {
    ?>
            <input type="hidden" name="sitemap" value="1" />
        <?php
} ?>

	</form>
	<div class="ccm-panel-detail-form-actions dialog-buttons">
		<button class="pull-left btn btn-default" type="button" data-dialog-action="cancel" data-panel-detail-action="cancel"><?=t('Cancel')?></button>
		<button class="pull-right btn btn-success" type="button" data-dialog-action="submit" data-panel-detail-action="submit"><?=t('Save Changes')?></button>
	</div>
</section>

<script type="text/javascript">
    $(function() {
        ConcreteEvent.unsubscribe('AjaxFormSubmitSuccess.saveSeo');
        ConcreteEvent.subscribe('AjaxFormSubmitSuccess.saveSeo', function(e, data) {
            if (data.form == 'seo') {
				ConcreteToolbar.disableDirectExit();
                ConcreteEvent.publish('SitemapUpdatePageRequestComplete', {'cID': data.response.cID});
            }
        });
		$('#ccm-panel-detail-page-seo .form-control').textcounter({
			type: "character",
			max: -1,
			countSpaces: true,
			stopInputAtMaximum: false,
			counterText: '<?php echo t('Characters'); ?>: ',
			countContainerClass: 'help-block'
		});
    });
</script>
