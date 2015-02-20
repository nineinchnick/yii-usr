<?php /*
@var $this CController
@var $model ProfileForm
@var $passwordForm PasswordForm
 */
?>

	<div class="control-group">
		<?php echo $form->labelEx($model, 'username'); ?>
		<?php echo $form->textField($model, 'username'); ?>
		<?php echo $form->error($model, 'username'); ?>
	</div>

	<div class="control-group">
		<?php echo $form->labelEx($model, 'email'); ?>
		<?php echo $form->textField($model, 'email'); ?>
		<?php echo $form->error($model, 'email'); ?>
	</div>

<?php if ($model->scenario !== 'register'): ?>
	<div class="control-group">
		<?php echo $form->labelEx($model, 'password'); ?>
		<?php echo $form->passwordField($model, 'password', array('autocomplete' => 'off')); ?>
		<?php echo $form->error($model, 'password'); ?>
	</div>
<?php endif; ?>

<?php if (isset($passwordForm) && $passwordForm !== null): ?>
<?php $this->renderPartial('/default/_newpassword', array('form' => $form, 'model' => $passwordForm)); ?>
<?php endif; ?>

	<div class="control-group">
		<?php echo $form->labelEx($model, 'firstName'); ?>
		<?php echo $form->textField($model, 'firstName'); ?>
		<?php echo $form->error($model, 'firstName'); ?>
	</div>

	<div class="control-group">
		<?php echo $form->labelEx($model, 'lastName'); ?>
		<?php echo $form->textField($model, 'lastName'); ?>
		<?php echo $form->error($model, 'lastName'); ?>
	</div>

<?php if ($model->getIdentity() instanceof IPictureIdentity && !empty($model->pictureUploadRules)):
    $picture = $model->getIdentity()->getPictureUrl(80, 80);
    if ($picture !== null) {
        $url = $picture['url'];
        unset($picture['url']);
    }
?>
	<div class="control-group">
		<?php echo $form->labelEx($model, 'picture'); ?>
		<?php echo $picture === null ? '' : CHtml::image($url, Yii::t('UsrModule.usr', 'Profile picture'), $picture); ?><br/>
		<?php echo $form->fileField($model, 'picture'); ?>
		<?php echo $form->error($model, 'picture'); ?>
	</div>
	<div class="control-group">
		<?php echo $form->label($model, 'removePicture', array('label' => $form->checkBox($model, 'removePicture').$model->getAttributeLabel('removePicture'), 'class' => 'checkbox')); ?>
		<?php echo $form->error($model, 'removePicture'); ?>
	</div>
<?php endif; ?>
