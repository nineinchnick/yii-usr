<?php /*
@var $this DefaultController
@var $model ProfileForm */

if ($model->scenario == 'register') {
	$title = Yii::t('UsrModule.usr', 'Registration');
} else {
	$title = Yii::t('UsrModule.usr', 'User profile');
}
if (isset($this->breadcrumbs))
	$this->breadcrumbs=array($this->module->id, $title);
$this->pageTitle = Yii::app()->name.' - '.$title;
?>
<h1><?php echo $title; ?></h1>

<?php $this->widget('usr.components.UsrAlerts', array('cssClassPrefix'=>$this->module->alertCssClassPrefix)); ?>

<div class="<?php echo $this->module->formCssClass; ?>">
<?php $form=$this->beginWidget($this->module->formClass, array(
	'id'=>'profile-form',
	'enableAjaxValidation'=>true,
	'enableClientValidation'=>false,
	'clientOptions'=>array(
		'validateOnSubmit'=>true,
	),
	'htmlOptions' => array('enctype' => 'multipart/form-data'),
	'focus'=>array($model,'username'),
)); ?>

	<p class="note"><?php echo Yii::t('UsrModule.usr', 'Fields marked with <span class="required">*</span> are required.'); ?></p>

	<?php echo $form->errorSummary($model); ?>

	<div class="control-group">
		<?php echo $form->labelEx($model,'username'); ?>
		<?php echo $form->textField($model,'username'); ?>
		<?php echo $form->error($model,'username'); ?>
	</div>

	<div class="control-group">
		<?php echo $form->labelEx($model,'email'); ?>
		<?php echo $form->textField($model,'email'); ?>
		<?php echo $form->error($model,'email'); ?>
	</div>

<?php if ($model->scenario !== 'register'): ?>
	<div class="control-group">
		<?php echo $form->labelEx($model,'password'); ?>
		<?php echo $form->passwordField($model,'password', array('autocomplete'=>'off')); ?>
		<?php echo $form->error($model,'password'); ?>
	</div>
<?php endif; ?>
<?php $this->renderPartial('_newpassword', array('form'=>$form, 'model'=>$passwordForm)); ?>

	<div class="control-group">
		<?php echo $form->labelEx($model,'firstName'); ?>
		<?php echo $form->textField($model,'firstName'); ?>
		<?php echo $form->error($model,'firstName'); ?>
	</div>

	<div class="control-group">
		<?php echo $form->labelEx($model,'lastName'); ?>
		<?php echo $form->textField($model,'lastName'); ?>
		<?php echo $form->error($model,'lastName'); ?>
	</div>

<?php if ($model->getIdentity() instanceof IPictureIdentity && !empty($model->pictureUploadRules)):
	$picture = $model->getIdentity()->getPictureUrl(80,80);
	$url = $picture['url'];
	unset($picture['url']);
?>
	<div class="control-group">
		<?php echo $form->labelEx($model,'picture'); ?>
		<?php echo CHtml::image($url, Yii::t('UsrModule.usr', 'Profile picture'), $picture); ?><br/>
		<?php echo $form->fileField($model,'picture'); ?>
		<?php echo $form->error($model,'picture'); ?>
	</div>
	<div class="control-group">
		<?php echo $form->label($model,'removePicture', array('label'=>$form->checkBox($model,'removePicture').$model->getAttributeLabel('removePicture'), 'class'=>'checkbox')); ?>
		<?php echo $form->error($model,'removePicture'); ?>
	</div>
<?php endif; ?>

<?php if($model->asa('captcha') !== null): ?>
<?php $this->renderPartial('_captcha', array('form'=>$form, 'model'=>$model)); ?>
<?php endif; ?>

	<div class="buttons">
		<?php echo CHtml::submitButton(Yii::t('UsrModule.usr', 'Submit'), array('class'=>$this->module->submitButtonCssClass)); ?>
	</div>

<?php $this->endWidget(); ?>
</div><!-- form -->
