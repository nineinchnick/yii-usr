<?php

/**
 * RecoveryForm class.
 * RecoveryForm is the data structure for keeping
 * password recovery form data. It is used by the 'recovery' action of 'DefaultController'.
 */
class RecoveryForm extends CFormModel {
	public $username;
	public $email;
	public $activationKey;
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
			array('username, email', 'filter', 'filter'=>'trim'),
			array('username, email', 'default', 'setOnEmpty'=>true, 'value' => null),
			array('username, email', 'existingIdentity'),

			array('activationKey', 'filter', 'filter'=>'trim', 'on'=>'reset,verify'),
			array('activationKey', 'default', 'setOnEmpty'=>true, 'value' => null, 'on'=>'reset,verify'),
			array('activationKey', 'required', 'on'=>'reset,verify'),
			array('newPassword, newVerify', 'required', 'on'=>'reset'),
			array('activationKey', 'validActivationKey', 'on'=>'reset,verify'),
			array('newPassword', 'unusedNewPassword', 'on'=>'reset'),
			array('newPassword', 'length', 'min' => 8, 'message' => Yii::t('UsrModule.usr', 'New password must contain at least 8 characters.'), 'on'=>'reset'),
			array('newPassword', 'match', 'pattern' => '/^.*(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).*$/', 'message'	=> Yii::t('UsrModule.usr', 'New password must contain at least one lower and upper case character and a digit.'), 'on'=>'reset'),
			array('newVerify', 'compare', 'compareAttribute'=>'newPassword', 'message' => Yii::t('UsrModule.usr', 'Please type the same new password twice to verify it.'), 'on'=>'reset'),
		);
	}

	/**
	 * Declares attribute labels.
	 */
	public function attributeLabels() {
		return array(
			'username'		=> Yii::t('UsrModule.usr','Username'),
			'email'			=> Yii::t('UsrModule.usr','Email'),
			'activationKey'	=> Yii::t('UsrModule.usr','Activation Key'),
			'newPassword'	=> Yii::t('UsrModule.usr','New password'),
			'newVerify'		=> Yii::t('UsrModule.usr','Verify'),
		);
	}

	public function getIdentity() {
		if($this->_identity===null) {
			$userIdentityClass = Yii::app()->controller->module->userIdentityClass;
			// generate a fake object just to check if it implements a correct interface
			$fakeIdentity = new $userIdentityClass(null, null);
			if (!($fakeIdentity instanceof IActivatedIdentity)) {
				throw new CException(Yii::t('UsrModule.usr','The {class} class must implement the IActivatedIdentity interface.',array('{class}'=>$userIdentityClass)));
			}
			$attributes = array();
			if ($this->username !== null) $attributes['username'] = $this->username;
			if ($this->email !== null) $attributes['email'] = $this->email;
			if (!empty($attributes))
				$this->_identity=$userIdentityClass::find($attributes);
		}
		return $this->_identity;
	}

	public function existingIdentity($attribute,$params) {
		if($this->hasErrors()) {
			return;
		}
		$identity = $this->getIdentity();
		if ($identity === null) {
			if ($this->username !== null) {
				$this->addError('username',Yii::t('UsrModule.usr','No user found matching this username.'));
			} elseif ($this->email !== null) {
				$this->addError('email',Yii::t('UsrModule.usr','No user found matching this email address.'));
			} else {
				$this->addError('username',Yii::t('UsrModule.usr','Please specify username or email.'));
			}
			return false;
		}
		return true;
	}

	/**
	 * Validates the activation key.
	 */
	public function validActivationKey($attribute,$params) {
		if($this->hasErrors()) {
			return;
		}
		if (($identity = $this->getIdentity()) === null)
			return false;

		$errorCode = $identity->verifyActivationKey($this->activationKey);
		switch($errorCode) {
			default:
			case $identity::ERROR_AKEY_INVALID:
				$this->addError('activationKey',Yii::t('UsrModule.usr','Activation key is invalid.'));
				return false;
			case $identity::ERROR_AKEY_TOO_OLD:
				$this->addError('activationKey',Yii::t('UsrModule.usr','Activation key is too old.'));
				return false;
			case $identity::ERROR_AKEY_NONE:
				return true;
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
			throw new CException(Yii::t('UsrModule.usr','The {class} class must implement the IPasswordHistoryIdentity interface.',array('{class}'=>get_class($identity))));
		if (($lastUsed = $identity->getPasswordDate($this->newPassword)) !== null) {
			$this->addError('password',Yii::t('UsrModule.usr','New password has been already used on {date}.'), array('{date}'=>$lastUsed));
			return false;
		}
		return true;
	}

	/**
	 * Logs in the user using the given username and new password.
	 * @return boolean whether login is successful
	 */
	public function login() {
		$identity = $this->getIdentity();

		$identity->password = $this->newPassword;
		$identity->authenticate();
		if($identity->getIsAuthenticated()) {
			return Yii::app()->user->login($identity,0);
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
