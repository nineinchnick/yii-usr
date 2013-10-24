<?php /*
@var $this HybridauthController */

$title = Yii::t('UsrModule.usr', 'Log in');
if (isset($this->breadcrumbs))
	$this->breadcrumbs=array($this->module->id, $title);
$this->pageTitle = Yii::app()->name.' - '.$title;

$assetsUrl = Yii::app()->getAssetManager()->publish(Yii::getPathOfAlias($this->module->id.'.components.assets.zocial'));
Yii::app()->clientScript->registerCssFile($assetsUrl.'/zocial.css');
?>
<h1><?php echo $title; ?></h1>

<?php if (($flashMessages = Yii::app()->user->getFlashes())): ?>
<ul class="flashes">
<?php foreach($flashMessages as $key => $message): ?>
	<li><div class="<?php echo $this->module->alertCssClassPrefix.$key; ?>"><?php echo $message; ?></div></li>
<?php endforeach; ?>
</ul>
<?php endif; ?>


<div class="<?php echo $this->module->formCssClass; ?>">
	<ul>
<?php foreach ($this->module->hybridauthProviders as $provider => $settings): if(!$settings['enabled']) continue; ?>
		<li>
			<a class="zocial <?php echo strtolower($provider); ?>" href="<?php echo $this->createUrl('login', array('provider'=>$provider)); ?>">
				<?php echo Yii::t('UsrModule.usr', 'Log in using {provider}.', array('{provider}'=>$provider)); ?>
			</a>
		</li>
<?php endforeach; ?>
	</ul>
<?php $form=$this->beginWidget($this->module->formClass, array(
	'id'=>'remoteLogin-form',
	'action'=>array($this->action->id),
	'enableClientValidation'=>true,
	'clientOptions'=>array(
		'validateOnSubmit'=>true,
	),
	'focus'=>$remoteLogin->requiresFilling() ? array($remoteLogin,'openid_identifier') : null,
)); ?>

	<?php echo $form->hiddenField($remoteLogin,'provider'); ?>

	<div style="<?php echo $remoteLogin->requiresFilling() ? '' : 'display: none;'; ?>">
		<p class="note"><?php echo Yii::t('UsrModule.usr', 'Fields marked with <span class="required">*</span> are required.'); ?></p>

		<?php echo $form->errorSummary($remoteLogin); ?>

		<div class="control-group">
			<?php echo $form->labelEx($remoteLogin,'openid_identifier'); ?>
			<?php echo $form->textField($remoteLogin,'openid_identifier'); ?>
			<?php echo $form->error($remoteLogin,'openid_identifier'); ?>
		</div>

		<div class="buttons">
			<?php echo CHtml::submitButton(Yii::t('UsrModule.usr', 'Log in'), array('class'=>$this->module->submitButtonCssClass)); ?>
		</div>
	</div>

	</div>
<?php endif; ?>
<?php $this->endWidget(); ?>
</div><!-- form -->

