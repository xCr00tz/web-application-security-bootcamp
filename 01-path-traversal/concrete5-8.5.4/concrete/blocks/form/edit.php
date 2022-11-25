<?php 
defined('C5_EXECUTE') or die('Access Denied.');
use Concrete\Block\Form\MiniSurvey;

//$miniSurveyInfo['surveyName']= $bs->surveyName;
$miniSurvey = new MiniSurvey($b);
$miniSurveyInfo = $miniSurvey->getMiniSurveyBlockInfo($b->getBlockID());
MiniSurvey::questionCleanup((int) ($miniSurveyInfo['questionSetId']), $b->getBlockID());

$u = Core::make(Concrete\Core\User\User::class);
$ui = UserInfo::getByID($u->uID);
?>

<script>
<?php if (is_object($b->getProxyBlock())) {
    ?>
	var thisbID=parseInt(<?php echo $b->getProxyBlock()->getBlockID()?>); 
<?php 
} else {
    ?>
	var thisbID=parseInt(<?php echo $b->getBlockID()?>); 
<?php 
} ?>
var thisbtID=parseInt(<?php echo $b->getBlockTypeID()?>); 
</script>

<?php  $this->inc('form_setup_html.php', ['c' => $c, 'b' => $b, 'miniSurveyInfo' => $miniSurveyInfo, 'miniSurvey' => $miniSurvey, 'a' => $a, 'bt' => $bt]); ?>
