<?php

Yii::import('vendors.nineinchnick.yii-usr.models.BaseUsrForm');
Yii::import('vendors.nineinchnick.yii-usr.models.LoginForm');

class CModelTest extends CTestCase
{
	public function testRules()
	{
		$form = new LoginForm;
		$form->setAttributes(array(
			'username'=>'',
			'password'=>'',
		));
		$this->assertTrue($form->validate());
		$this->assertEmpty($form->getErrors());
	}
}
