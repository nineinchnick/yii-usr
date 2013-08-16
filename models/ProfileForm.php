<?php

/**
 * ProfileForm class.
 * ProfileForm is the data structure for keeping
 * password recovery form data. It is used by the 'recovery' action of 'DefaultController'.
 */
class ProfileForm extends CFormModel {
	public $username;
	public $email;
	public $newPassword;
	public $newVerify;
	public $firstName;
	public $lastName;

	private $_identity;

	/**
	 * Declares the validation rules.
	 * The rules state that username and password are required,
	 * and password needs to be authenticated.
	 */
	public function rules() {
		return array(
			array('username, email, newPassword, newVerify, firstName, lastName', 'filter', 'filter'=>'trim'),
			array('username, email, newPassword, newVerify, firstName, lastName', 'default', 'setOnEmpty'=>true, 'value' => null),

			array('username, email', 'required'),
			array('username, email', 'uniqueIdentity'),
			array('newPassword, newVerify', 'required', 'on' => 'register'),
			array('newPassword', 'length', 'min' => 8, 'message' => Yii::t('UsrModule.usr', 'New password must contain at least 8 characters.')),
			array('newPassword', 'match', 'pattern' => '/^.*(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).*$/', 'message'	=> Yii::t('UsrModule.usr', 'New password must contain at least one lower and upper case character and a digit.')),
			array('newVerify', 'compare', 'compareAttribute'=>'newPassword', 'message' => Yii::t('UsrModule.usr', 'Please type the same new password twice to verify it.')),
		);
	}

	/**
	 * Declares attribute labels.
	 */
	public function attributeLabels() {
		return array(
			'username'		=> Yii::t('UsrModule.usr','Username'),
			'email'			=> Yii::t('UsrModule.usr','Email'),
			'newPassword'	=> Yii::t('UsrModule.usr','New password'),
			'newVerify'		=> Yii::t('UsrModule.usr','Verify'),
			'firstName'		=> Yii::t('UsrModule.usr','First name'),
			'lastName'		=> Yii::t('UsrModule.usr','Last name'),
		);
	}

	public function getIdentity() {
		if($this->_identity===null) {
			$userIdentityClass = Yii::app()->controller->module->userIdentityClass;
			if ($this->scenario == 'register') {
				$this->_identity = new $userIdentityClass(null, null);
			} else {
				$this->_identity = $userIdentityClass::find(array('id'=>Yii::app()->user->getId()));
			}
			if (!($this->_identity instanceof IEditableIdentity)) {
				throw new CException(Yii::t('UsrModule.usr','The {class} class must implement the {interface} interface.',array('{class}'=>get_class($this->_identity),'{interface}'=>'IEditableIdentity')));
			}
		}
		return $this->_identity;
	}

	public function uniqueIdentity($attribute,$params) {
		if($this->hasErrors()) {
			return;
		}
		$userIdentityClass = Yii::app()->controller->module->userIdentityClass;
		if (($identity=$userIdentityClass::find(array($attribute => $this->$attribute))) !== null && ($this->scenario == 'register' || $identity->getId() != $this->getIdentity()->getId())) {
			$this->addError($attribute,Yii::t('UsrModule.usr','{attribute} has already been used by another user.', array('{attribute}'=>$this->$attribute)));
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
	 * Updates the identity with this models attributes and saves it.
	 */
	public function save() {
		$identity = $this->getIdentity();
		return $identity!==null && $identity->setAttributes(array(
			'username'	=> $this->username,
			'email'		=> $this->email,
			'firstName'	=> $this->firstName,
			'lastName'	=> $this->lastName,
		)) && $identity->save();
	}

	/**
	 * Resets user password using the new one given in the model.
	 * @return boolean whether password reset was successful
	 */
	public function resetPassword() {
		return empty($this->newPassword) || $this->getIdentity()->resetPassword($this->newPassword);
	}
}
