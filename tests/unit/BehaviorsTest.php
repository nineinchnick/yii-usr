<?php

Yii::import('vendors.nineinchnick.yii-usr.tests.User');
Yii::import('vendors.nineinchnick.yii-usr.tests.UserIdentity');
Yii::import('vendors.nineinchnick.yii-usr.components.FormModelBehavior');

class BehaviorsTest extends CTestCase
{
	public $identity;
	public $owner;

	protected function setUp()
	{
		$this->identity = new UserIdentity(null, null);

		$this->owner = $this->getMock('stdClass', array('getIdentity', 'hasErrors'));
		$this->owner->username = 'xx';
		$this->owner->expects($this->any())
			->method('getIdentity')
			->will($this->returnValue($this->identity));
		$this->owner->expects($this->any())
			->method('hasErrors')
			->will($this->returnValue(array()));
	}

	public function testOTP()
	{
		Yii::import('vendors.nineinchnick.yii-usr.components.OneTimePasswordFormBehavior');

		require dirname(__FILE__) . '/../../extensions/GoogleAuthenticator.php/lib/GoogleAuthenticator.php';
		$googleAuthenticator = new GoogleAuthenticator;
		$otp = Yii::createComponent(array(
			'class' => 'OneTimePasswordFormBehavior',
			'oneTimePasswordConfig' => array(
				'authenticator' => $googleAuthenticator,
				'mode' => UsrModule::OTP_COUNTER,
				'required' => false,
				'timeout' => 300,
			),
		));
		$otp->setEnabled(true);
		$otp->attach($this->owner);

		$this->assertEquals(array('oneTimePassword'), $otp->attributeNames());
		$this->assertEquals(array('oneTimePassword'), $otp->attributeNames());
		$this->assertEquals(array('oneTimePassword' => Yii::t('UsrModule.usr','One Time Password')), $otp->attributeLabels());
		$rules = $otp->rules();

		$ruleOptions = array('on'=>'reset');
		$otp->setRuleOptions($ruleOptions);
		$this->assertEquals($ruleOptions, $otp->getRuleOptions());

		$modifiedRules = $otp->rules();
		foreach($modifiedRules as $rule) {
			foreach($ruleOptions as $key=>$value) {
				$this->assertEquals($value, $rule[$key]);
			}
		}

		$secret = $googleAuthenticator->generateSecret();
		$this->identity->setOneTimePasswordSecret($secret);
		$code = $otp->getNewCode();
		$this->assertInternalType('string', $code);
		$this->assertTrue(is_numeric($code));
		$this->assertEquals(6,strlen($code));
	}
}
