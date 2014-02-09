<?php
/* @var $this ManagerController */
/* @var $model User */

$this->pageTitle = Yii::t('UsrModule.manager', 'Update user {id}', array('{id}' => $model->id));

$this->breadcrumbs=array(
	Yii::t('UsrModule.manager', 'Users manager')=>array('index'),
	Yii::t('UsrModule.manager', 'View user {id}', array('{id}' => $model->id))=>array('view','id'=>$model->id),
	Yii::t('UsrModule.manager', 'Update'),
);

$this->menu=array(
	array('label'=>Yii::t('UsrModule.manager', 'List users'), 'url'=>array('index')),
	array('label'=>Yii::t('UsrModule.manager', 'Create user'), 'url'=>array('create')),
	array('label'=>Yii::t('UsrModule.manager', 'View user {id}', array('{id}' => $model->id)), 'url'=>array('view', 'id'=>$model->id)),
);
?>

<h1><?php echo $this->pageTitle; ?></h1>

<?php $this->renderPartial('_form', array('model'=>$model)); ?>
