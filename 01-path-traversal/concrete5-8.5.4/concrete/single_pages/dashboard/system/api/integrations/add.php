<?php defined('C5_EXECUTE') or die("Access Denied."); ?>
<?php
/**
 * @var \Concrete\Core\Entity\OAuth\Client $client
 */
?>

<form method="post" class="ccm-dashboard-content-form" action="<?=$view->action('create')?>">
    <?=$this->controller->token->output('create')?>
    <fieldset>
        <legend><?=t('Add OAuth2 Integration')?></legend>
        <div class="form-group">
            <label for="name" class="control-label"><?php echo t('Name'); ?></label>
            <div class="input-group">
                <?php echo $form->text('name', array('autofocus' => 'autofocus', 'autocomplete' => 'off', 'required' => 'required')); ?>
                <span class="input-group-addon"><i class="fa fa-asterisk"></i></span>
            </div>
        </div>

        <div class="form-group">
            <label for="redirect" class="control-label"><?php echo t('Redirect'); ?></label>
            <div class="input-group">
                <?php echo $form->url('redirect', array('autocomplete' => 'off')); ?>
                <span class="input-group-addon"><i class="fa fa-asterisk"></i></span>
            </div>
        </div>

        <div class="alert alert-info"><?=t('When you add an Oauth2 integration, the client key and secret will automatically be generated.')?></div>
    </fieldset>

    <div class="ccm-dashboard-form-actions-wrapper">
        <div class="ccm-dashboard-form-actions">
            <a href="<?=URL::to('/dashboard/system/api/settings')?>" class="pull-left btn btn-default"><?=t('Cancel')?></a>
            <button class="pull-right btn btn-primary" type="submit" ><?=t('Add Integration')?></button>
        </div>
    </div>

</form>
