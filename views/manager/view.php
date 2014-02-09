<?php
/* @var $this ManagerController */
/* @var $model User */

$this->pageTitle = Yii::t('UsrModule.manager', 'View user {id}', array('{id}' => $model->id));

$this->breadcrumbs=array(
	Yii::t('UsrModule.manager', 'Users manager')=>array('index'),
	$this->pageTitle,
);

$this->menu=array(
	array('label'=>Yii::t('UsrModule.manager', 'List users'), 'url'=>array('index')),
	array('label'=>Yii::t('UsrModule.manager', 'Create user'), 'url'=>array('create')),
	array('label'=>Yii::t('UsrModule.manager', 'Update user {id}', array('{id}' => $model->id)), 'url'=>array('update', 'id'=>$model->id)),
	array('label'=>Yii::t('UsrModule.manager', 'Delete user {id}', array('{id}' => $model->id)), 'url'=>'#', 'linkOptions'=>array(
		'submit'=>array('delete','id'=>$model->id),'confirm'=>Yii::t('UsrModule.manager', 'Are you sure you want to delete this user?'),
	)),
);
?>

<h1><?php echo $this->pageTitle; ?></h1>

<?php $this->widget('zii.widgets.CDetailView', array(
	'data'=>$model,
	'attributes'=>array(
		'id',
		'username',
		'email',
		'firstname',
		'lastname',
		'created_on',
		'updated_on',
		'last_visit_on',
		'password_set_on',
		'email_verified:boolean',
		'is_active:boolean',
		'is_disabled:boolean',
	),
)); ?>
