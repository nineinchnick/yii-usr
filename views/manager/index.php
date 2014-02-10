<?php
/* @var $this ManagerController */
/* @var $model SearchForm */

$this->pageTitle = Yii::t('UsrModule.manager', 'List users');

$this->breadcrumbs=array(
	Yii::t('UsrModule.manager', 'Users manager')=>array('index'),
	$this->pageTitle,
);

$this->menu=array(
	array('label'=>Yii::t('UsrModule.manager', 'List users'), 'url'=>array('index')),
	array('label'=>Yii::t('UsrModule.manager', 'Create user'), 'url'=>array('create')),
);

$booleanFilter = array('0'=>Yii::t('UsrModule.manager', 'No'), '1'=>Yii::t('UsrModule.manager', 'Yes'));

$script = <<<JavaScript
$('.search-button').click(function(){
	$('.search-form').toggle();
	return false;
});
$('.search-form form').submit(function(){
	$('#identity-grid').yiiGridView('update', {data: $(this).serialize()});
	return false;
});
JavaScript;
Yii::app()->clientScript->registerScript('search', $script);
?>

<h1><?php echo $this->pageTitle; ?></h1>

<p>
<?php echo Yii::t('UsrModule.manager', 'You may optionally enter a comparison operator (<b>&lt;</b>, <b>&lt;=</b>, <b>&gt;</b>, <b>&gt;=</b>, <b>&lt;&gt;</b> or <b>=</b>) at the beginning of each of your search values to specify how the comparison should be done.'); ?>
</p>

<?php echo CHtml::link(Yii::t('UsrModule.manager', 'Advanced Search'),'#',array('class'=>'search-button')); ?>
<div class="search-form" style="display:none">
<?php $this->renderPartial('_search',array('model'=>$model)); ?>
</div><!-- search-form -->

<?php $this->widget('zii.widgets.grid.CGridView', array(
	'id'=>'identity-grid',
	'dataProvider'=>$model->getIdentity()->getDataProvider($model),
	'filter'=>$model,
	'columns'=>array(
		'id',
		'username',
		'email',
		'firstName',
		'lastName',
		'createdOn',
		'lastVisitOn',
		array(
			'name'=>'emailVerified',
			'type'=>'boolean',
			'filter'=>$booleanFilter,
		),
		array(
			'name'=>'isActive',
			'type'=>'boolean',
			'filter'=>$booleanFilter,
			'value'=>'$data->isActive()',
		),
		array(
			'name'=>'isDisabled',
			'type'=>'boolean',
			'filter'=>$booleanFilter,
			'value'=>'$data->isDisabled()',
		),
		array(
			'class'=>'CButtonColumn',
			'viewButtonUrl'=>'Yii::app()->controller->createUrl("view",array("id"=>$data->id))',
			'updateButtonUrl'=>'Yii::app()->controller->createUrl("update",array("id"=>$data->id))',
			'deleteButtonUrl'=>'Yii::app()->controller->createUrl("delete",array("id"=>$data->id))',
		),
	),
)); ?>
