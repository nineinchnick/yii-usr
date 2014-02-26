<?php /*
@var $this DefaultController
@var $model LoginForm */

$title = Yii::t('UsrModule.usr', 'Log in');
if (isset($this->breadcrumbs))
	$this->breadcrumbs=array($this->module->id, $title);
$this->pageTitle = Yii::app()->name.' - '.$title;
?>
<h1><?php echo $title; ?></h1>

<?php $this->widget('usr.components.UsrAlerts', array('cssClassPrefix'=>$this->module->alertCssClassPrefix)); ?>

<div class="<?php echo $this->module->formCssClass; ?>">
<?php $form=$this->beginWidget($this->module->formClass, array(
	'id'=>'login-form',
	'enableClientValidation'=>true,
	'clientOptions'=>array(
		'validateOnSubmit'=>true,
	),
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
		<?php echo $form->labelEx($model,'password'); ?>
		<?php echo $form->passwordField($model,'password'); ?>
		<?php echo $form->error($model,'password'); ?>
	</div>

<?php if ($this->module->rememberMeDuration > 0): ?>
	<div class="rememberMe control-group">
		<?php echo $form->label($model,'rememberMe', array('label'=>$form->checkBox($model,'rememberMe').$model->getAttributeLabel('rememberMe'), 'class'=>'checkbox')); ?>
		<?php echo $form->error($model,'rememberMe'); ?>
	</div>
<?php endif; ?>

	<div class="buttons">
		<?php echo CHtml::submitButton(Yii::t('UsrModule.usr', 'Log in'), array('class'=>$this->module->submitButtonCssClass)); ?>
	</div>
<?php if ($this->module->recoveryEnabled): ?>
	<p>
		<?php echo Yii::t('UsrModule.usr', 'Don\'t remember username or password?'); ?>
		<?php echo Yii::t('UsrModule.usr', 'Go to {link}.', array(
			'{link}'=>CHtml::link(Yii::t('UsrModule.usr', 'password recovery'), array('recovery')),
		)); ?>
	</p>
<?php endif; ?>
<?php if ($this->module->registrationEnabled): ?>
	<p>
		<?php echo Yii::t('UsrModule.usr', 'Don\'t have an account yet?'); ?>
		<?php echo Yii::t('UsrModule.usr', 'Go to {link}.', array(
			'{link}'=>CHtml::link(Yii::t('UsrModule.usr', 'registration'), array('register')),
		)); ?>
	</p>
<?php endif; ?>
<?php if ($this->module->hybridauthEnabled()): ?>
	<p>
		<?php //echo CHtml::link(Yii::t('UsrModule.usr', 'Sign in using one of your social sites account.'), array('hybridauth/login')); ?>
		<ul>
<?php Yii::app()->clientScript->registerCssFile(Yii::app()->getAssetManager()->publish(Yii::getPathOfAlias($this->module->id.'.components.assets.zocial')).'/zocial.css'); ?>
<?php Yii::app()->clientScript->registerScript(__CLASS__.'#popup', "function PopupCenter(url, title, w, h) {
    // credits: http://www.xtf.dk/2011/08/center-new-popup-window-even-on.html
    // Fixes dual-screen position                         Most browsers      Firefox
    var dualScreenLeft = window.screenLeft != undefined ? window.screenLeft : screen.left;
    var dualScreenTop = window.screenTop != undefined ? window.screenTop : screen.top;

    width = window.innerWidth ? window.innerWidth : document.documentElement.clientWidth ? document.documentElement.clientWidth : screen.width;
    height = window.innerHeight ? window.innerHeight : document.documentElement.clientHeight ? document.documentElement.clientHeight : screen.height;

    var left = ((width / 2) - (w / 2)) + dualScreenLeft;
    var top = ((height / 2) - (h / 2)) + dualScreenTop;
    var options = 'toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=no, resizable=no, copyhistory=no';
    var newWindow = window.open(url, title, options+', width=' + w + ', height=' + h + ', top=' + top + ', left=' + left);

    if (window.focus) {
        newWindow.focus();
    }
    return false;
}", CClientScript::POS_END); ?>
<?php foreach ($this->module->hybridauthProviders as $provider => $settings): if(!$settings['enabled']) continue; ?>
			<li>
                <a class="zocial <?php echo strtolower($provider); ?>" href="<?php echo $this->createUrl('hybridauth/popup', array('provider'=>$provider)); ?>"
                    onclick="return PopupCenter($(this).attr('href'), 'Hybridauth', 400, 550);">
					<?php echo Yii::t('UsrModule.usr', 'Log in using {provider}', array('{provider}'=>$provider)); ?>
				</a>
			</li>
<?php endforeach; ?>
		</ul>
	</p>
<?php endif; ?>

<?php $this->endWidget(); ?>
</div><!-- form -->
