<?php
/* @var $this ManagerController */
/* @var $model User */

$this->pageTitle = Yii::t('UsrModule.manager', 'List users');

$this->breadcrumbs=array(
	Yii::t('UsrModule.manager', 'Users manager')=>array('index'),
	$this->pageTitle,
);

$this->menu=array(
	array('label'=>Yii::t('UsrModule.manager', 'List users'), 'url'=>array('index')),
	array('label'=>Yii::t('UsrModule.manager', 'Create user'), 'url'=>array('create')),
);

Yii::app()->clientScript->registerScript('search', "
$('.search-button').click(function(){
	$('.search-form').toggle();
	return false;
});
$('.search-form form').submit(function(){
	$('#user-grid').yiiGridView('update', {
		data: $(this).serialize()
	});
	return false;
});
");
?>

<h1><?php echo $this->pageTitle; ?></h1>

<p>
<?php echo Yii::t('UsrModule.manager', 'You may optionally enter a comparison operator (<b>&lt;</b>, <b>&lt;=</b>, <b>&gt;</b>, <b>&gt;=</b>, <b>&lt;&gt;</b> or <b>=</b>) at the beginning of each of your search values to specify how the comparison should be done.'); ?>
</p>

<?php echo CHtml::link(Yii::t('UsrModule.manager', 'Advanced Search'),'#',array('class'=>'search-button')); ?>
<div class="search-form" style="display:none">
<?php $this->renderPartial('_search',array(
	'model'=>$model,
)); ?>
</div><!-- search-form -->

<?php $this->widget('zii.widgets.grid.CGridView', array(
	'id'=>'user-grid',
	'dataProvider'=>$model->search(),
	'filter'=>$model,
	'columns'=>array(
		'id',
		'username',
		'email',
		'firstname',
		'lastname',
		'created_on',
		'last_visit_on',
		'email_verified:boolean',
		'is_active:boolean',
		'is_disabled:boolean',
		/*
		'activation_key',
		'created_on',
		'updated_on',
		'password_set_on',
		'email_verified',
		'is_active',
		'is_disabled',
		'one_time_password_secret',
		'one_time_password_code',
		'one_time_password_counter',
		*/
		array(
			'class'=>'CButtonColumn',
		),
	),
)); ?>
