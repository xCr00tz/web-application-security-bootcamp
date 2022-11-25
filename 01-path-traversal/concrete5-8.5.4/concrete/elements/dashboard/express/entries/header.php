<?php defined('C5_EXECUTE') or die("Access Denied."); ?>

<div class="ccm-header-search-form ccm-ui" data-header="express-search">
    <form class="form-inline" method="get" action="<?php echo URL::to('/ccm/system/search/express/basic')?>?exEntityID=<?=$entity->getID()?>">

        <div class="ccm-header-search-form-input">
            <a class="ccm-header-reset-search" href="#"
               data-button-action-url="<?= URL::to('/ccm/system/search/express/clear') ?>?exEntityID=<?=$entity->getID()?>"
               data-button-action="clear-search"><?= t('Reset Search') ?></a>
            <a class="ccm-header-launch-advanced-search"
               href="<?php echo URL::to('/ccm/system/dialogs/express/advanced_search')?>?exEntityID=<?=$entity->getID()?>"
               data-launch-dialog="advanced-search"><?= t('Advanced') ?></a>
            <input type="text" class="form-control" autocomplete="off" name="eKeywords"
                   placeholder="<?= t('Search') ?>">
        </div>

        <button class="btn btn-info" type="submit"><i class="fa fa-search"></i></button>

        <?php if ($exportURL != null && $createURL != null) { ?>
            <ul class="ccm-header-search-navigation">
                <li>
                    <a href="<?= $exportURL ?>" class="link-primary">
                        <i class="fa fa-download"></i> <?= t('Export to CSV') ?>
                    </a>
                </li>
                <li><a href="<?= $createURL ?>" class="link-primary"><i class="fa fa-plus"></i> <?= t('New %s',
                        $entity->getEntityDisplayName()) ?></a></li>
            </ul>
        <?php } ?>
    </form>
</div>
