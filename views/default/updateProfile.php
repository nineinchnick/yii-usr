<?php /*
@var $this DefaultController
@var $model ProfileForm
@var $passwordForm PasswordForm
 */

$title = $model->scenario == 'register' ? Yii::t('UsrModule.usr', 'Registration') : Yii::t('UsrModule.usr', 'User profile');
if (isset($this->breadcrumbs)) {
    $this->breadcrumbs = array($this->module->id, $title);
}
$this->pageTitle = Yii::app()->name.' - '.$title;
?>
<h1><?php echo $title; ?></h1>

<?php $this->widget('usr.components.UsrAlerts', array('cssClassPrefix' => $this->module->alertCssClassPrefix)); ?>

<div class="<?php echo $this->module->formCssClass; ?>">
<?php $form = $this->beginWidget($this->module->formClass, array(
    'id' => 'profile-form',
    'enableAjaxValidation' => true,
    'enableClientValidation' => false,
    'clientOptions' => array(
        'validateOnSubmit' => true,
    ),
    'htmlOptions' => array('enctype' => 'multipart/form-data'),
    'focus' => array($model, 'username'),
)); ?>

	<p class="note"><?php echo Yii::t('UsrModule.usr', 'Fields marked with <span class="required">*</span> are required.'); ?></p>

	<?php echo $form->errorSummary($model); ?>

<?php $this->renderPartial('_form', array('form' => $form, 'model' => $model, 'passwordForm' => $passwordForm)); ?>

<?php if ($model->asa('captcha') !== null): ?>
<?php $this->renderPartial('_captcha', array('form' => $form, 'model' => $model)); ?>
<?php endif; ?>

	<div class="buttons">
		<?php echo CHtml::submitButton(Yii::t('UsrModule.usr', 'Submit'), array('class' => $this->module->submitButtonCssClass)); ?>
	</div>

<?php $this->endWidget(); ?>
</div><!-- form -->
