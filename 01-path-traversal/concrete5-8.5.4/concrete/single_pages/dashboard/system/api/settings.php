<?php
defined('C5_EXECUTE') or die("Access Denied.");
?>

<form method="post" action="<?php echo $view->action('submit'); ?>">
    <?php echo $token->output('submit'); ?>

    <div class="form-group">
        <div class="checkbox">
            <label for="enable_api">
                <?php echo $form->checkbox('enable_api', 1, $enable_api) ?>
                <span><?php echo t('Enable API'); ?></span>
            </label>
        </div>
    </div>


<?php if ($enable_api) { ?>
    <section>
        <h3><?=t('API Integrations')?></h3>
        <?php if ($clients && count($clients)) { ?>
            <ul class="item-select-list">
                <?php foreach($clients as $client) { ?>
                    <li><a href="<?=URL::to('/dashboard/system/api/integrations', 'view_client', $client->getIdentifier())?>">
                        <i class="fa fa-handshake-o"></i>
                        <?=$client->getName()?>
                    </a></li>
                    <?php
                }
                ?>
            </ul>
        <?php } else { ?>
            <div class="text-muted"><?=t('None')?></div>
        <?php } ?>
        <hr>
        <div><a href="<?=URL::to('/dashboard/system/api/integrations', 'add')?>" class="btn btn-default btn-xs"><?=t('Add Integration')?></a></div>
    </section>

    <section>
        <h3><?=t('Enabled Grant Types')?></h3>
        <div class="help-block"><?=t('Choose which OAuth2 grant types you want to have access to the API for this site.')?></div>

        <?php foreach($availableGrantTypes as $key => $label) {
            $enabled = false;
            if (isset($grantTypes[$key]) && $grantTypes[$key] === true) {
                $enabled = true;
            }
            ?>
            <div class="checkbox">
                <label>
                    <?=$form->checkbox('enabledGrantTypes[]', $key, $enabled)?>
                    <?=$label?>
                </label>
            </div>
        <?php } ?>

    </section>

<?php } ?>

    <div class="ccm-dashboard-form-actions-wrapper">
        <div class="ccm-dashboard-form-actions">
            <button class="btn btn-primary pull-right" type="submit"><?=t("Save")?></button>
        </div>
    </div>
</form>
