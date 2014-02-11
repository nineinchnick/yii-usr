<?php
/* @var $this ManagerController */
/* @var $model User */

$this->pageTitle = $id === null ? Yii::t('UsrModule.manager', 'Create user') : Yii::t('UsrModule.manager', 'Update user {id}', array('{id}' => $model->id));

$this->breadcrumbs=array(
	Yii::t('UsrModule.manager', 'Users manager')=>array('index'),
	$this->pageTitle,
);

$this->menu=array(
	array('label'=>Yii::t('UsrModule.manager', 'List users'), 'url'=>array('index')),
);
if ($id === null) {
	$this->menu[] = array('label'=>Yii::t('UsrModule.manager', 'Create user'), 'url'=>array('update')),
}
?>

<h1><?php echo $this->pageTitle; ?></h1>

<?php $this->renderPartial('_form', array('model'=>$model)); ?>
