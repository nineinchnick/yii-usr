<?php

/**
 * ProfileForm class.
 * ProfileForm is the data structure for keeping
 * password recovery form data. It is used by the 'recovery' action of 'DefaultController'.
 */
class ProfileForm extends BaseUsrForm
{
	public $username;
	public $email;
	public $firstName;
	public $lastName;
	public $picture;
	public $removePicture;
	public $password;

	/**
	 * @var IdentityInterface cached object returned by @see getIdentity()
	 */
	private $_identity;
	/**
	 * @var array Picture upload validation rules.
	 */
	private $_pictureUploadRules;

	/**
	 * Returns rules for picture upload or an empty array if they are not set.
	 * @return array
	 */
	public function getPictureUploadRules()
	{
		return $this->_pictureUploadRules === null ? array() : $this->_pictureUploadRules;
	}

	/**
	 * Sets rules to validate uploaded picture. Rules should NOT contain attribute name as this method adds it.
	 * @param array $rules
	 */
	public function setPictureUploadRules($rules)
	{
		$this->_pictureUploadRules = array();
		if (!is_array($rules))
			return;
		foreach($rules as $rule) {
			$this->_pictureUploadRules[] = array_merge(array('picture'), $rule);
		}
	}

	/**
	 * Declares the validation rules.
	 */
	public function rules()
	{
		return array_merge($this->getBehaviorRules(), array(
			array('username, email, firstName, lastName, removePicture', 'filter', 'filter'=>'trim'),
			array('username, email, firstName, lastName, removePicture', 'default', 'setOnEmpty'=>true, 'value' => null),

			array('username, email', 'required'),
			array('username, email', 'uniqueIdentity'),
			array('removePicture', 'boolean'),
			array('password', 'validCurrentPassword', 'except'=>'register'),
		), $this->pictureUploadRules);
	}

	/**
	 * Declares attribute labels.
	 */
	public function attributeLabels()
	{
		return array_merge($this->getBehaviorLabels(), array(
			'username'		=> Yii::t('UsrModule.usr','Username'),
			'email'			=> Yii::t('UsrModule.usr','Email'),
			'firstName'		=> Yii::t('UsrModule.usr','First name'),
			'lastName'		=> Yii::t('UsrModule.usr','Last name'),
			'picture'		=> Yii::t('UsrModule.usr','Profile picture'),
			'removePicture'	=> Yii::t('UsrModule.usr','Remove picture'),
			'password'		=> Yii::t('UsrModule.usr','Current password'),
		));
	}

	/**
	 * @inheritdoc
	 */
	public function getIdentity()
	{
		if($this->_identity===null) {
			$userIdentityClass = $this->userIdentityClass;
			if ($this->scenario == 'register') {
				$this->_identity = new $userIdentityClass(null, null);
			} else {
				$this->_identity = $userIdentityClass::find(array('id'=>Yii::app()->user->getId()));
			}
			if ($this->_identity !== null && !($this->_identity instanceof IEditableIdentity)) {
				throw new CException(Yii::t('UsrModule.usr','The {class} class must implement the {interface} interface.',array('{class}'=>get_class($this->_identity),'{interface}'=>'IEditableIdentity')));
			}
		}
		return $this->_identity;
	}

	public function uniqueIdentity($attribute,$params)
	{
		if($this->hasErrors()) {
			return;
		}
		$userIdentityClass = $this->userIdentityClass;
		$existingIdentity = $userIdentityClass::find(array($attribute => $this->$attribute));
		if ($existingIdentity !== null && ($this->scenario == 'register' || (($identity=$this->getIdentity()) !== null && $existingIdentity->getId() != $identity->getId()))) {
			$this->addError($attribute,Yii::t('UsrModule.usr','{attribute} has already been used by another user.', array('{attribute}'=>$this->$attribute)));
			return false;
		}
		return true;
	}

	/**
	 * A valid current password is required only when changing email.
	 */
	public function validCurrentPassword($attribute,$params)
	{
		if($this->hasErrors()) {
			return;
		}
		if (($identity=$this->getIdentity()) === null) {
			throw new CException('Current user has not been found in the database.');
		}
		if ($identity->getEmail() === $this->email) {
			return true;
		}
		$identity->password = $this->$attribute;
		if(!$identity->authenticate()) {
			$this->addError($attribute, Yii::t('UsrModule.usr', 'Changing email address requires providing the current password.'));
			return false;
		}
		return true;
	}

	/**
	 * Logs in the user using the given username.
	 * @return boolean whether login is successful
	 */
	public function login()
	{
		$identity = $this->getIdentity();

		return Yii::app()->user->login($identity,0);
	}

	/**
	 * Updates the identity with this models attributes and saves it.
	 * @return boolean whether saving is successful
	 */
	public function save()
	{
		$identity = $this->getIdentity();
		if ($identity === null)
			return false;

		$identity->setAttributes(array(
			'username'	=> $this->username,
			'email'		=> $this->email,
			'firstName'	=> $this->firstName,
			'lastName'	=> $this->lastName,
		));
		if ($identity->save(Yii::app()->controller->module->requireVerifiedEmail)) {
			if ((!($this->picture instanceof CUploadedFile) || $identity->savePicture($this->picture)) && (!$this->removePicture || $identity->removePicture())) {
				$this->_identity = $identity;
				return true;
			}
		}
		return false;
	}
}
