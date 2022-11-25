<?php
if (!empty($showPrivacyPolicyNotice)) { ?>
<div class="ccm-dashboard-privacy-policy">
    <div class="ccm-dashboard-privacy-policy-inner">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-12">
                    <p>
                    <?=t('concrete5 collects some information about your website to assist in upgrading and checking add-on compatibility. This information can be disabled in configuration.')?></p>
                </div>
            </div>
            <div class="row">
                <div class="col-sm-6">
                    <a target="_blank" href="<?=Config::get('concrete.urls.privacy_policy')?>" class="btn-block btn btn-default"><?=t('View Privacy Policy')?></a>
                </div>
                <div class="col-sm-6">
                    <button data-action="agree-privacy-policy" data-token="<?=Core::make('token')->generate('accept_privacy_policy')?>" class="btn-block btn btn-primary"><?=t('Accept and Close')?></button>
                </div>
            </div>
        </div>
    </div>
</div>
<?php } ?>

</div>
</div>


<?php View::element('footer_required', ['disableTrackingCode' => true]); ?>
<script type="text/javascript">
(function() {
    ConcretePanelManager.register({'overlay': false, 'identifier': 'dashboard', 'position': 'right', url: '<?=URL::to('/ccm/system/panels/dashboard')?>'});
    ConcretePanelManager.register({'identifier': 'sitemap', 'position': 'right', url: '<?=URL::to('/ccm/system/panels/sitemap')?>'});
    var panel = ConcretePanelManager.getByIdentifier('dashboard');
    <?php
    if (!(isset($hideDashboardPanel) && $hideDashboardPanel)) {
        ?>
        panel.isOpen = true;
        panel.onPanelLoad();
        <?php 
    }
    ?>
    var savePanelStatus = false;
    ConcreteEvent.subscribe('PanelOpen', function(e, data) {
        if (savePanelStatus && data.panel === panel) {
            $.cookie('dashboardPanelStatus', null, {path: '<?=DIR_REL?>/'});
            savePanelStatus = false;
        }
    });
    ConcreteEvent.subscribe('PanelClose', function(e, data) {
        if (savePanelStatus && data.panel === panel) {
            $.cookie('dashboardPanelStatus', 'closed', {path: '<?=DIR_REL?>/'});
            savePanelStatus = false;
        }
    });
    $(document).ready(function() {
        $('a[data-launch-panel=dashboard]').on('click', function() {
            savePanelStatus = true;
        });
    });
})();
</script>
</body>
</html>
