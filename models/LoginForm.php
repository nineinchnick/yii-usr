<?php

/**
 * LoginForm class.
 * LoginForm is the data structure for keeping
 * user login form data. It is used by the 'login' action of 'DefaultController'.
 */
class LoginForm extends BaseUsrForm
{
	public $username;
	public $password;
	public $rememberMe;

	private $_identity;

	/**
	 * Declares the validation rules.
	 * The rules state that username and password are required,
	 * and password needs to be authenticated.
	 */
	public function rules()
	{
		$rules = array_merge(array(
			array('username, password', 'filter', 'filter'=>'trim'),
			array('username, password', 'required'),
			array('rememberMe', 'boolean'),
			array('password', 'authenticate'),
			array('password', 'passwordIsFresh', 'except'=>'reset, hybridauth, verifyOTP'),
		), $this->getBehaviorRules());

		return $rules;
	}

	/**
	 * Declares attribute labels.
	 */
	public function attributeLabels()
	{
		return array_merge($this->getBehaviorLabels(), array(
			'username'		=> Yii::t('UsrModule.usr','Username'),
			'password'		=> Yii::t('UsrModule.usr','Password'),
			'rememberMe'	=> Yii::t('UsrModule.usr','Remember me when logging in next time'),
		));
	}

	public function behaviors()
	{
		if (Yii::app()->controller->module->oneTimePasswordMode != UsrModule::OTP_NONE) {
			return array(
				'oneTimePasswordBehavior' => array('class' => 'OneTimePasswordFormBehavior'),
			);
		}
		return array();
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
	 * A wrapper for the validOneTimePassword method from OneTimePasswordBehavior.
	 * @param $attribute string
	 * @param $params array
	 */
	public function validOneTimePassword($attribute, $params)
	{
		if (($behavior=$this->asa('oneTimePasswordBehavior')) !== null) {
			return $behavior->validOneTimePassword($attribute, $params);
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
		if (!$identity->resetPassword($this->newPassword)) {
			$this->addError('newPassword',Yii::t('UsrModule.usr','Failed to reset the password.'));
			return false;
		}
		return true;
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
}
