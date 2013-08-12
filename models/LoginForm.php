<?php

/**
 * LoginForm class.
 * LoginForm is the data structure for keeping
 * user login form data. It is used by the 'login' action of 'DefaultController'.
 */
class LoginForm extends CFormModel {
	public $username;
	public $password;
	public $rememberMe;
	public $newPassword;
	public $newVerify;

	private $_identity;

	/**
	 * Declares the validation rules.
	 * The rules state that username and password are required,
	 * and password needs to be authenticated.
	 */
	public function rules() {
		return array(
			array('username, password', 'filter', 'filter'=>'trim'),
			array('username, password', 'required'),
			array('rememberMe', 'boolean'),
			array('password', 'authenticate'),

			array('newPassword, newVerify', 'filter', 'filter'=>'trim', 'on'=>'reset'),
			array('newPassword, newVerify', 'required', 'on'=>'reset'),
			array('newPassword', 'unusedNewPassword', 'on'=>'reset'),
			array('newPassword', 'length', 'min' => 8, 'message' => Yii::t('UsrModule.usr', 'New password must contain at least 8 characters.'), 'on'=>'reset'),
			array('newPassword', 'match', 'pattern' => '/^.*(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).*$/', 'message'	=> Yii::t('UsrModule.usr', 'New password must contain at least one lower and upper case character and a digit.'), 'on'=>'reset'),
			array('newVerify', 'compare', 'compareAttribute'=>'newPassword', 'message' => Yii::t('UsrModule.usr', 'Please type the same new password twice to verify it.'), 'on'=>'reset'),
			array('newPassword', 'compare', 'compareAttribute'=>'password', 'operator' => '!=', 'message' => Yii::t('UsrModule.usr', 'New password must be different than the old one.'), 'on'=>'reset'),
		);
	}

	/**
	 * Declares attribute labels.
	 */
	public function attributeLabels() {
		return array(
			'username'		=> Yii::t('UsrModule.usr','Username'),
			'password'		=> Yii::t('UsrModule.usr','Password'),
			'rememberMe'	=> Yii::t('UsrModule.usr','Remember me when logging in next time'),
			'newPassword'	=> Yii::t('UsrModule.usr','New password'),
			'newVerify'		=> Yii::t('UsrModule.usr','Verify'),
		);
	}

	protected function getIdentity() {
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
	public function authenticate($attribute,$params) {
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
	public function unusedNewPassword() {
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
	public function passwordIsFresh() {
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
	 * Logs in the user using the given username and password in the model.
	 * @param string $password if null, model's password attribute will be used
	 * @return boolean whether login is successful
	 */
	public function login($password = null) {
		$identity = $this->getIdentity();
		if ($password !== null) {
			$identity->password = $password;
			$identity->authenticate();
		}
		if($identity->getIsAuthenticated()) {
			$duration=$this->rememberMe ? Yii::app()->controller->module->rememberMeDuration : 0;
			return Yii::app()->user->login($identity,$duration);
		}
		return false;
	}

	/**
	 * Resets user password using the new one given in the model.
	 * @return boolean whether password reset was successful
	 */
	public function resetPassword() {
		$identity = $this->getIdentity();
		return $identity->resetPassword($this->newPassword);
	}
}
