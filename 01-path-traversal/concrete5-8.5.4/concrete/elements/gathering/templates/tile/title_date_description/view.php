<?php defined('C5_EXECUTE') or die("Access Denied."); ?>
<div class="ccm-gathering-tile-title-description">
	<div class="ccm-gathering-tile-headline"><a href="<?=$link?>"><?=$title?></a></div>
	<div class="ccm-gathering-tile-date"><?=Core::make('helper/date')->formatDateTime($date_time, true)?></div>
	<div class="ccm-gathering-tile-description">
	<?=$description?>
	</div>
</div>



