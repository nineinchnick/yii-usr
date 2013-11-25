<?php

Yii::import('vendors.nineinchnick.yii-usr.tests.User');
Yii::import('vendors.nineinchnick.yii-usr.tests.UserIdentity');
Yii::import('vendors.nineinchnick.yii-usr.models.BaseUsrForm');
Yii::import('vendors.nineinchnick.yii-usr.models.LoginForm');

class CModelTest extends CDbTestCase
{
	public $fixtures=array(
		'users'=>'User',
	);

	public static function validDataProvider() {
		return array(
			array(array(
				'username'=>'neo',
				'password'=>'one',
			)),
		);
	}

	public static function invalidDataProvider() {
		return array(
			array(array(
				'username'=>'',
				'password'=>'',
			)),
		);
	}

	public static function allDataProvider() {
		return array_merge(self::validDataProvider(), self::invalidDataProvider());
	}

	/**
	 * @dataProvider validDataProvider
	 */
	public function testRules($attributes)
	{
		$form = new LoginForm;
		$form->userIdentityClass = 'UserIdentity';
		$form->setAttributes(array(
			'username'=>'',
			'password'=>'',
		));
		$this->assertFalse($form->validate());
		$this->assertEquals(array('username'=>array('Username cannot be blank.'), 'password'=>array('Password cannot be blank.')), $form->getErrors());

		$form = new LoginForm;
		$form->userIdentityClass = 'UserIdentity';
		$form->setAttributes(array(
			'username'=>'neo',
			'password'=>'xx',
		));
		$this->assertFalse($form->validate());
		$this->assertEquals(array('password'=>array('Invalid username or password.')), $form->getErrors());

		$form = new LoginForm;
		$form->userIdentityClass = 'UserIdentity';
		$form->setAttributes(array(
			'username'=>'neo',
			'password'=>'Test1233',
		));
		$this->assertTrue($form->validate(), 'Failed with following validation errors: '.print_r($form->getErrors(),true));
		$this->assertEmpty($form->getErrors());
	}
}
