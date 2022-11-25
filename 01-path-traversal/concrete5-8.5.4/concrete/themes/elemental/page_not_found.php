<?php
defined('C5_EXECUTE') or die("Access Denied.");

$this->inc('elements/header.php');
?>

<main>
    <div class="container">
        <div class="row">
            <div class="col-sm-9 col-sm-offset-3">
                <div class="jumbo">
                    <h1><?php echo t('404 Error') ?></h1>
                    <p><?php echo t('Page not found.') ?></p>
                </div>
            </div>
        </div>
    </div>
</main>

<?php
$this->inc('elements/footer.php');
