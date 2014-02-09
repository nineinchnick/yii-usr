<?php
/* @var $this ManagerController */
/* @var $model User */

$this->pageTitle = Yii::t('UsrModule.manager', 'Create user');

$this->breadcrumbs=array(
	'Users'=>array('index'),
	$this->pageTitle,
);

$this->menu=array(
	array('label'=>Yii::t('UsrModule.manager', 'List users'), 'url'=>array('index')),
);
?>

<h1><?php echo $this->pageTitle; ?></h1>

<?php $this->renderPartial('_form', array('model'=>$model)); ?>
