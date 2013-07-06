<?php /*
@var $this DefaultController
@var $model ProfileForm */

$title = Yii::t('UsrModule.usr', 'User profile');
if (isset($this->breadcrumbs))
	$this->breadcrumbs=array($this->module->id, $title);
$this->pageTitle = Yii::app()->name.' - '.$title;
?>
<h1><?php echo $title; ?><small style="margin-left: 1em;"><?php echo CHtml::link(Yii::t('UsrModule.usr', 'update'), array('profile', 'update'=>true)); ?></small></h1>

<?php if (($flashMessages = Yii::app()->user->getFlashes())): ?>
<ul class="flashes">
<?php foreach($flashMessages as $key => $message): ?>
	<li><div class="<?php echo $this->module->alertCssClassPrefix.$key; ?>"><?php echo $message; ?></div></li>
<?php endforeach; ?>
</ul>
<?php endif; ?>


<?php $this->widget($this->module->detailViewClass, array(
	'data' => $model,
	'attributes' => array(
		'username',
		'email',
		'firstName',
		'lastName',
	),
)); ?>

