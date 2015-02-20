<?php /*
@var $this HybridauthController */

$title = Yii::t('UsrModule.usr', 'Log in');
if (isset($this->breadcrumbs)) {
    $this->breadcrumbs = array($this->module->id, $title);
}
$this->pageTitle = Yii::app()->name.' - '.$title;
?>
<h1><?php echo $title; ?></h1>

<?php $this->widget('usr.components.UsrAlerts', array('cssClassPrefix' => $this->module->alertCssClassPrefix)); ?>

<?php if ($this->module->registrationEnabled): ?>

<div class="<?php echo $this->module->formCssClass; ?>">
<?php $form = $this->beginWidget($this->module->formClass, array(
    'id' => 'localProfile-form',
    'action' => array($this->action->id),
    'enableClientValidation' => true,
    'clientOptions' => array(
        'validateOnSubmit' => true,
    ),
    'focus' => array($localProfile, 'username'),
)); ?>

	<?php echo $form->hiddenField($remoteLogin, 'provider'); ?>
	<?php echo $form->hiddenField($remoteLogin, 'openid_identifier'); ?>

	<div>
		<h3><?php echo Yii::t('UsrModule.usr', 'Create a new account'); ?></h3>

		<div class="buttons">
			<?php echo CHtml::submitButton(Yii::t('UsrModule.usr', 'Continue'), array('name' => 'ProfileForm[continue]', 'class' => $this->module->submitButtonCssClass)); ?>
		</div>
	</div>

<?php $this->endWidget(); ?>
</div><!-- form -->

<?php endif; ?>

<div class="<?php echo $this->module->formCssClass; ?>">
<?php $form = $this->beginWidget($this->module->formClass, array(
    'id' => 'localLogin-form',
    'action' => array($this->action->id),
    'enableClientValidation' => true,
    'clientOptions' => array(
        'validateOnSubmit' => true,
    ),
    'focus' => array($localLogin, 'username'),
)); ?>

	<?php echo $form->hiddenField($remoteLogin, 'provider'); ?>
	<?php echo $form->hiddenField($remoteLogin, 'openid_identifier'); ?>

	<div>
		<h3><?php echo Yii::t('UsrModule.usr', 'Log in into existing account'); ?></h3>

		<p class="note"><?php echo Yii::t('UsrModule.usr', 'A matching local account has been found. Please type in your password to associate it.'); ?></p>

		<?php echo $form->errorSummary($localLogin); ?>

<?php if ($localLogin->scenario != 'reset'): ?>
		<div class="control-group">
			<?php echo $form->labelEx($localLogin, 'password'); ?>
			<?php echo $form->passwordField($localLogin, 'password'); ?>
			<?php echo $form->error($localLogin, 'password'); ?>
		</div>

		<div class="buttons">
			<?php echo CHtml::submitButton(Yii::t('UsrModule.usr', 'Log in'), array('class' => $this->module->submitButtonCssClass)); ?>
		</div>
<?php else: ?>
		<?php echo $form->hiddenField($localLogin, 'username'); ?>
		<?php echo $form->hiddenField($localLogin, 'password'); ?>
		<?php echo $form->hiddenField($localLogin, 'rememberMe'); ?>

<?php $this->renderPartial('_newpassword', array('form' => $form, 'model' => $localLogin)); ?>

		<div class="buttons">
			<?php echo CHtml::submitButton(Yii::t('UsrModule.usr', 'Change password'), array('class' => $this->module->submitButtonCssClass)); ?>
		</div>
<?php endif; ?>
	</div>
<?php $this->endWidget(); ?>
</div><!-- form -->
