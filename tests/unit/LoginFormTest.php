<?php

Yii::import('vendors.nineinchnick.yii-usr.tests.User');
Yii::import('vendors.nineinchnick.yii-usr.tests.UserIdentity');
Yii::import('vendors.nineinchnick.yii-usr.models.BaseUsrForm');
Yii::import('vendors.nineinchnick.yii-usr.models.LoginForm');

class LoginFormTest extends CDbTestCase
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

	public function testWithBehavior()
	{
		$form = new LoginForm;
		$formAttributes = $form->attributeNames();
		$formRules = $form->rules();
		$formLabels = $form->attributeLabels();
		$form->attachBehavior('captcha', array('class' => 'CaptchaFormBehavior'));
		$behaviorAttributes = $form->asa('captcha')->attributeNames();
		$behaviorRules = $form->asa('captcha')->rules();
		$behaviorLabels = $form->asa('captcha')->attributeLabels();
		$this->assertEquals(array_merge($formAttributes, $behaviorAttributes), $form->attributeNames());
		$this->assertEquals(array_merge($formRules, $behaviorRules), $form->rules());
		$this->assertEquals(array_merge($formLabels, $behaviorLabels), $form->attributeLabels());
		$form->detachBehavior('captcha');
		$this->assertEquals($formAttributes, $form->attributeNames());
		$this->assertEquals($formAttributes, $form->attributeNames());
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
