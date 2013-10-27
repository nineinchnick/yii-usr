<?php

/**
 * LoginForm class.
 * LoginForm is the data structure for keeping
 * user login form data. It is used by the 'login' action of 'DefaultController'.
 */
class LoginForm extends CFormModel
{
	public $username;
	public $password;
	public $rememberMe;
	public $newPassword;
	public $newVerify;
	public $oneTimePassword;

	private $_identity;

	private $_oneTimePasswordConfig = array(
		'authenticator' => null,
		'mode' => null,
		'required' => null,
		'timeout' => null,
		'secret' => null,
		'previousCode' => null,
		'previousCounter' => null,
	);

	/**
	 * Declares the validation rules.
	 * The rules state that username and password are required,
	 * and password needs to be authenticated.
	 */
	public function rules()
	{
		return array(
			array('username, password', 'filter', 'filter'=>'trim'),
			array('username, password', 'required'),
			array('rememberMe', 'boolean'),
			array('password', 'authenticate'),
			array('password', 'passwordIsFresh', 'except'=>'reset, hybridauth, verifyOTP'),

			array('newPassword, newVerify', 'filter', 'filter'=>'trim', 'on'=>'reset'),
			array('newPassword, newVerify', 'required', 'on'=>'reset'),
			array('newPassword', 'unusedNewPassword', 'on'=>'reset'),
			array('newPassword', 'length', 'min' => 8, 'message' => Yii::t('UsrModule.usr', 'New password must contain at least 8 characters.'), 'on'=>'reset'),
			array('newPassword', 'match', 'pattern' => '/^.*(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).*$/', 'message'	=> Yii::t('UsrModule.usr', 'New password must contain at least one lower and upper case character and a digit.'), 'on'=>'reset'),
			array('newVerify', 'compare', 'compareAttribute'=>'newPassword', 'message' => Yii::t('UsrModule.usr', 'Please type the same new password twice to verify it.'), 'on'=>'reset'),
			array('newPassword', 'compare', 'compareAttribute'=>'password', 'operator' => '!=', 'message' => Yii::t('UsrModule.usr', 'New password must be different than the old one.'), 'on'=>'reset'),
			array('newPassword', 'resetPassword', 'on'=>'reset'),

			array('oneTimePassword', 'filter', 'filter'=>'trim', 'on'=>'verifyOTP'),
			array('oneTimePassword', 'default', 'setOnEmpty'=>true, 'value' => null, 'on'=>'verifyOTP'),
			array('oneTimePassword', 'required', 'on'=>'verifyOTP'),
			array('oneTimePassword', 'validOneTimePassword', 'except'=>'hybridauth'),
		);
	}

	/**
	 * Declares attribute labels.
	 */
	public function attributeLabels()
	{
		return array(
			'username'		=> Yii::t('UsrModule.usr','Username'),
			'password'		=> Yii::t('UsrModule.usr','Password'),
			'rememberMe'	=> Yii::t('UsrModule.usr','Remember me when logging in next time'),
			'newPassword'	=> Yii::t('UsrModule.usr','New password'),
			'newVerify'		=> Yii::t('UsrModule.usr','Verify'),
			'oneTimePassword' => Yii::t('UsrModule.usr','One Time Password'),
		);
	}

	public function setOneTimePasswordConfig(array $config)
	{
		foreach($config as $key => $value) {
			if ($this->_oneTimePasswordConfig[$key] === null)
				$this->_oneTimePasswordConfig[$key] = $value;
		}
		return $this;
	}

	protected function loadOneTimePasswordConfig()
	{
		$module = Yii::app()->controller->module;
		$identity = $this->getIdentity();
		list($previousCode, $previousCounter) = $identity->getOneTimePassword();
		$this->setOneTimePasswordConfig(array(
			'authenticator' => $module->googleAuthenticator,
			'mode' => $module->oneTimePasswordMode,
			'required' => $module->oneTimePasswordRequired,
			'timeout' => $module->oneTimePasswordTimeout,
			'secret' => $identity->getOneTimePasswordSecret(),
			'previousCode' => $previousCode,
			'previousCounter' => $previousCounter,
		));
		return $this;
	}

	public function getOTP($key)
	{
		if ($this->_oneTimePasswordConfig[$key] === null) {
			$this->loadOneTimePasswordConfig();
		}
		return $this->_oneTimePasswordConfig[$key];
	}

	public function getIdentity()
	{
		if($this->_identity===null) {
			$userIdentityClass = Yii::app()->controller->module->userIdentityClass;
			$this->_identity=new $userIdentityClass($this->username,$this->password);
			$this->_identity->authenticate();
		}
		return $this->_identity;
	}

	/**
	 * Authenticates the password.
	 * This is the 'authenticate' validator as declared in rules().
	 */
	public function authenticate($attribute,$params)
	{
		if($this->hasErrors()) {
			return;
		}
		$identity = $this->getIdentity();
		if(!$identity->getIsAuthenticated()) {
			$this->addError('password',Yii::t('UsrModule.usr','Invalid username or password.'));
			return false;
		}
		return true;
	}

	/**
	 * Checkes if current password hasn't been used before.
	 * This is the 'unusedNewPassword' validator as declared in rules().
	 */
	public function unusedNewPassword()
	{
		if($this->hasErrors()) {
			return;
		}

		$identity = $this->getIdentity();
		if (!($identity instanceof IPasswordHistoryIdentity))
			throw new CException(Yii::t('UsrModule.usr','The {class} class must implement the {interface} interface.',array('{class}'=>get_class($identity),'{interface}'=>'IPasswordHistoryIdentity')));
		if (($lastUsed = $identity->getPasswordDate($this->newPassword)) !== null) {
			$this->addError('password',Yii::t('UsrModule.usr','New password has been already used on {date}.'), array('{date}'=>$lastUsed));
			return false;
		}
		return true;
	}

	/**
	 * Checkes if current password has timed out and needs to be reset.
	 */
	public function passwordIsFresh()
	{
		if($this->hasErrors()) {
			return;
		}
		$passwordTimeout = Yii::app()->controller->module->passwordTimeout;
		if ($passwordTimeout === null)
			return true;

		$identity = $this->getIdentity();
		if (!($identity instanceof IPasswordHistoryIdentity))
			throw new CException(Yii::t('UsrModule.usr','The {class} class must implement the {interface} interface.',array('{class}'=>get_class($identity),'{interface}'=>'IPasswordHistoryIdentity')));
		$lastUsed = $identity->getPasswordDate();
		$lastUsedDate = new DateTime($lastUsed);
		$today = new DateTime();
		if ($lastUsed === null || $today->diff($lastUsedDate)->days >= $passwordTimeout) {
			if ($lastUsed === null) {
				$this->addError('password',Yii::t('UsrModule.usr','This is the first time you login. Current password needs to be changed.'));
			} else {
				$this->addError('password',Yii::t('UsrModule.usr','Current password has been used too long and needs to be changed.'));
			}
			$this->scenario = 'reset';
			return false;
		}

		return true;
	}

	/**
	 * Resets user password using the new one given in the model.
	 * @return boolean whether password reset was successful
	 */
	public function resetPassword()
	{
		if($this->hasErrors()) {
			return;
		}
		$identity = $this->getIdentity();
		return $identity->resetPassword($this->newPassword);
	}

	/**
	 * Logs in the user using the given username and password in the model.
	 * @return boolean whether login is successful
	 */
	public function login()
	{
		$identity = $this->getIdentity();
		if ($this->scenario === 'reset') {
			$identity->password = $this->newPassword;
			$identity->authenticate();
		}
		if($identity->getIsAuthenticated()) {
			$duration=$this->rememberMe ? Yii::app()->controller->module->rememberMeDuration : 0;
			return Yii::app()->user->login($identity,$duration);
		}
		return false;
	}

	public function getNewCode()
	{
		$this->loadOneTimePasswordConfig();
		// extracts: $authenticator, $mode, $required, $timeout, $secret, $previousCode, $previousCounter
		extract($this->_oneTimePasswordConfig);
		return $authenticator->getCode($secret, $mode == UsrModule::OTP_TIME ? null : $previousCounter);
	}

	public function validOneTimePassword($attribute,$params)
	{
		if($this->hasErrors()) {
			return;
		}
		$this->loadOneTimePasswordConfig();
		// extracts: $authenticator, $mode, $required, $timeout, $secret, $previousCode, $previousCounter
		extract($this->_oneTimePasswordConfig);

		if (($mode !== UsrModule::OTP_TIME && $mode !== UsrModule::OTP_COUNTER) || (!$required && $secret === null)) {
			return true;
		}
		if ($required && $secret === null) {
			// generate and save a new secret only if required to do so, in other cases user must verify that the secret works
			$secret = $this->_oneTimePasswordConfig['secret'] = $authenticator->generateSecret();
			$this->getIdentity()->setOneTimePasswordSecret($secret);
		}

		if ($this->hasValidOTPCookie($this->username, $secret, $timeout)) {
			return true;
		}
		if (empty($this->$attribute)) {
			$this->addError($attribute,Yii::t('UsrModule.usr','Enter a valid one time password.'));
			$this->scenario = 'verifyOTP';
			Yii::app()->controller->sendEmail($this, 'oneTimePassword');
			if (YII_DEBUG) {
				$this->oneTimePassword = $authenticator->getCode($secret, $mode === UsrModule::OTP_TIME ? null : $previousCounter);
			}
			return false;
		}
		if ($mode === UsrModule::OTP_TIME) {
			$valid = $authenticator->checkCode($secret, $this->$attribute);
		} elseif ($mode === UsrModule::OTP_COUNTER) {
			$valid = $authenticator->getCode($secret, $previousCounter) == $this->$attribute;
		} else {
			$valid = false;
		}
		if (!$valid) {
			$this->addError($attribute,Yii::t('UsrModule.usr','Entered code is invalid.'));
			$this->scenario = 'verifyOTP';
			return false;
		}
		if ($this->$attribute == $previousCode) {
			if ($mode === UsrModule::OTP_TIME) {
				$message = Yii::t('UsrModule.usr','Please wait until next code will be generated.');
			} elseif ($mode === UsrModule::OTP_COUNTER) {
				$message = Yii::t('UsrModule.usr','Please log in again to request a new code.');
			}
			$this->addError($attribute,Yii::t('UsrModule.usr','Entered code has already been used.').' '.$message);
			$this->scenario = 'verifyOTP';
			return false;
		}
		$this->setOTPCookie($this->username, $secret, $timeout);
		$this->getIdentity()->setOneTimePassword($this->$attribute, $mode === UsrModule::OTP_TIME ? floor(time() / 30) : $previousCounter + 1);
		return true;
	}

	public function setOTPCookie($username, $secret, $timeout, $time = null) {
		if ($time === null)
			$time = time();
		$cookie=new CHttpCookie(UsrModule::OTP_COOKIE,'');
		$cookie->expire=time() + ($timeout <= 0 ? 10*365*24*3600 : $timeout);
		$cookie->httpOnly=true;
		$data=array('username'=>$username, 'time'=>$time, 'timeout'=>$timeout);
		$cookie->value=$time.':'.Yii::app()->getSecurityManager()->computeHMAC(serialize($data), $secret);
		Yii::app()->request->cookies->add($cookie->name,$cookie);
	}

	public function hasValidOTPCookie($username, $secret, $timeout, $time = null) {
		if ($time === null)
			$time = time();

		$cookie=Yii::app()->request->cookies->itemAt(UsrModule::OTP_COOKIE);
		if(!$cookie || empty($cookie->value) || !is_string($cookie->value)) {
			return false;
		}
		$parts = explode(":",$cookie->value,2);
		if (count($parts)!=2) {
			return false;
		}
		list($creationTime,$hash) = $parts;
		$data=array('username'=>$username, 'time'=>(int)$creationTime, 'timeout'=>$timeout);
		$validHash = Yii::app()->getSecurityManager()->computeHMAC(serialize($data), $secret);
		return ($timeout <= 0 || $creationTime + $timeout <= $time) && $hash === $validHash;
	}
}
