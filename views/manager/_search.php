<?php
/* @var $this ManagerController */
/* @var $model User */
/* @var $form CActiveForm */

$booleanData = array(1=>Yii::t('UsrModule.manager', 'Yes'), 0=>Yii::t('UsrModule.manager', 'No'));
$booleanOptions = array('empty'=>Yii::t('UsrModule.manager', 'Any'), 'separator' => '', 'labelOptions' => array('style'=>'display: inline; float: none;'));
?>

<div class="wide form">

<?php $form=$this->beginWidget('CActiveForm', array(
	'action'=>Yii::app()->createUrl($this->route),
	'method'=>'get',
)); ?>

	<div class="row">
		<?php echo $form->label($model,'id'); ?>
		<?php echo $form->textField($model,'id'); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model,'username'); ?>
		<?php echo $form->textField($model,'username',array('size'=>60,'maxlength'=>255)); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model,'email'); ?>
		<?php echo $form->textField($model,'email',array('size'=>60,'maxlength'=>255)); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model,'firstname'); ?>
		<?php echo $form->textField($model,'firstname',array('size'=>60,'maxlength'=>255)); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model,'lastname'); ?>
		<?php echo $form->textField($model,'lastname',array('size'=>60,'maxlength'=>255)); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model,'created_on'); ?>
		<?php echo $form->textField($model,'created_on'); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model,'updated_on'); ?>
		<?php echo $form->textField($model,'updated_on'); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model,'last_visit_on'); ?>
		<?php echo $form->textField($model,'last_visit_on'); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model,'email_verified'); ?>
		<?php echo $form->radioButtonList($model,'email_verified', $booleanData, $booleanOptions); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model,'is_active'); ?>
		<?php echo $form->radioButtonList($model,'is_active', $booleanData, $booleanOptions); ?>
	</div>

	<div class="row">
		<?php echo $form->label($model,'is_disabled'); ?>
		<?php echo $form->radioButtonList($model,'is_disabled', $booleanData, $booleanOptions); ?>
	</div>

	<div class="row buttons">
		<?php echo CHtml::submitButton('Search'); ?>
	</div>

<?php $this->endWidget(); ?>

</div><!-- search-form -->
