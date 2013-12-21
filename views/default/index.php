<?php
/* @var $this DefaultController */

if (isset($this->breadcrumbs))
	$this->breadcrumbs=array($this->module->id);
?>
<h1><?php echo $this->uniqueId . '/' . $this->action->id; ?></h1>

<?php $this->widget('usr.components.UsrAlerts', array('cssClassPrefix'=>$this->module->alertCssClassPrefix)); ?>
