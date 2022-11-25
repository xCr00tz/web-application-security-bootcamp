<?php defined('C5_EXECUTE') or die("Access Denied."); ?>

<div class="ccm-header-search-form ccm-ui" data-header="page-search">
    <?php if (isset($includeBreadcrumb) && $includeBreadcrumb) { ?>
        <div class="ccm-search-results-breadcrumb">
        </div>
    <?php } ?>

    <form class="form-inline" method="get" action="<?php echo URL::to('/ccm/system/search/pages/basic') ?>">

        <div class="ccm-header-search-form-input">
            <a class="ccm-header-reset-search" href="#" data-button-action-url="<?php echo URL::to('/ccm/system/search/pages/clear')?>" data-button-action="clear-search"><?php echo t('Reset Search')?></a>
            <a class="ccm-header-launch-advanced-search" href="<?php echo URL::to('/ccm/system/dialogs/page/advanced_search')?>" data-launch-dialog="advanced-search"><?php echo t('Advanced')?></a>
            <input type="text" class="form-control" autocomplete="off" name="cKeywords" placeholder="<?php echo t('Search')?>">
        </div>

        <?php
        $site = Core::make('site')->getActiveSiteForEditing();
        $locales = $site->getLocales();
        if (count($locales) > 1) {
            $selector = new Concrete\Core\Form\Service\Widget\SiteLocaleSelector();
            print $selector->selectLocale('localeID', $site, null, ['allowNull' => true, 'noLocaleText' => t('Any Locale')]);
        } ?>

        <button class="btn btn-info" type="submit"><i class="fa fa-search"></i></button>

    </form>

</div>

