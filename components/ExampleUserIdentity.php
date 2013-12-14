<?php

Yii::import('usr.components.*');

/**
 * UserIdentity represents the data needed to identity a user.
 * It contains the authentication method that checks if the provided
 * data can identity the user.
 */
abstract class ExampleUserIdentity extends CUserIdentity implements IPasswordHistoryIdentity,IActivatedIdentity,IEditableIdentity,IHybridauthIdentity,IOneTimePasswordIdentity
{
	CONST ERROR_USER_DISABLED=1000;
	CONST ERROR_USER_INACTIVE=1001;

	public $email = null;
	public $firstName = null;
	public $lastName = null;
	private $_id = null;

	protected static function createFromUser(User $user)
	{
		$identity = new UserIdentity($user->username, null);
		$identity->initFromUser($user);
		return $identity;
	}

	protected function initFromUser(User $user)
	{
		$this->id = $user->id;
		$this->username = $user->username;
		$this->email = $user->email;
		$this->firstName = $user->firstname;
		$this->lastName = $user->lastname;
	}

	// {{{ IUserIdentity

	/**
	 * @inheritdoc
	 */
	public function authenticate()
	{
		$record=User::model()->findByAttributes(array('username'=>$this->username));
		if ($record!==null && $record->verifyPassword($this->password)) {
			if ($record->is_disabled) {
				$this->errorCode=self::ERROR_USER_DISABLED;
			}
			else if (!$record->is_active) {
				$this->errorCode=self::ERROR_USER_INACTIVE;
			}
			else {
				$this->initFromUser($record);
				$this->errorCode=self::ERROR_NONE;
				$record->saveAttributes(array('last_visit_on'=>date('Y-m-d H:i:s')));
			}
		} else {
			$this->errorCode=self::ERROR_USERNAME_INVALID;
		}
		return $this->getIsAuthenticated();
	}
	
	public function setId($id)
	{
		$this->_id = $id;
	}
	
	/**
	 * @return int|string current user ID
	 */
	public function getId()
	{
		return $this->_id;
	}

	// }}}

	// {{{ PasswordHistoryIdentityInterface

	/**
	 * Returns the date when specified password was last set or null if it was never used before.
	 * If null is passed, returns date of setting current password.
	 * @param string $password new password or null if checking when the current password has been set
	 * @return string date in YYYY-MM-DD format or null if password was never used.
	 */
	public function getPasswordDate($password = null)
	{
		if ($this->_id === null || ($record=User::model()->findByPk($this->_id)) === null)
			return null;

		if ($password === null) {
			return $record->password_set_on;
		} else {
			foreach($record->userUsedPasswords as $usedPassword) {
				if ($usedPassword->verifyPassword($password))
					return $usedPassword->set_on;
			}
		}
		return null;
	}

	/**
	 * Changes the password and updates last password change date.
	 * Saves old password so it couldn't be used again.
	 * @param string $password new password
	 * @return boolean
	 */
	public function resetPassword($password)
	{
		if ($this->_id===null)
			return false;
		if (($record=User::model()->findByPk($this->_id))!==null) {
			$hashedPassword = User::hashPassword($password);
			$usedPassword = new UserUsedPassword;
			$usedPassword->setAttributes(array(
				'user_id'=>$this->_id,
				'password'=>$hashedPassword,
				'set_on'=>date('Y-m-d H:i:s'),
			), false);
			return $usedPassword->save() && $record->saveAttributes(array(
				'password'=>$hashedPassword,
				'password_set_on'=>date('Y-m-d H:i:s'),
			));
		}
		return false;
	}

	// }}}

	// {{{ EditableIdentityInterface

	/**
	 * Saves a new or existing identity. Does not set or change the password.
	 * @see IPasswordHistoryIdentity::resetPassword()
	 * Should detect if the email changed and mark it as not verified.
	 * @return boolean
	 */
	public function save()
	{
		if ($this->_id === null) {
			$record = new User;
			$record->password = 'x';
			$record->is_active = Yii::app()->controller->module->requireVerifiedEmail ? 0 : 1;
		} else {
			$record = User::model()->findByPk($this->_id);
		}
		if ($record!==null) {
			$record->setAttributes(array(
				'username' => $this->username,
				'email' => $this->email,
				'firstname' => $this->firstName,
				'lastname' => $this->lastName,
			));
			if ($record->save()) {
				$this->_id = $record->getPrimaryKey();
				return true;
			}
			Yii::log('Failed to save user: '.print_r($record->getErrors(),true), 'warning');
		} else {
			Yii::log('Trying to save UserIdentity but no matching User has been found', 'warning');
		}
		return false;
	}

	/**
	 * Sets attributes like username, email, first and last name.
	 * Password should be changed using only the resetPassword() method from the IPasswordHistoryIdentity.
	 * @param array $attributes
	 * @return boolean
	 */
	public function setAttributes(array $attributes)
	{
		$allowedAttributes = array('username','email','firstName','lastName');
		foreach($attributes as $name=>$value) {
			if (in_array($name, $allowedAttributes))
				$this->$name = $value;
		}
		return true;
	}

	/**
	 * Returns attributes like username, email, first and last name.
	 * @return array
	 */
	public function getAttributes()
	{
		return array(
			'username' => $this->username,
			'email' => $this->email,
			'firstName' => $this->firstName,
			'lastName' => $this->lastName,
		);
	}

	// }}}

	// {{{ ActivatedIdentityInterface

	public static function find(array $attributes)
	{
		$record = User::model()->findByAttributes($attributes);
		return $record === null ? null : self::createFromUser($record);
	}

	/**
	 * Checkes if user account is active. This should not include disabled (banned) status.
	 * This could include if the email address has been verified.
	 * Same checks should be done in the authenticate() method, because this method is not called before logging in.
	 * @return boolean
	 */
	public function isActive()
	{
		if ($this->_id===null)
			return false;
		if (($record=User::model()->findByPk($this->_id))!==null) {
			return (bool)$record->is_active;
		}
		return false;
	}

	/**
	 * Checkes if user account is disabled (banned). This should not include active status.
	 * @return boolean
	 */
	public function isDisabled()
	{
		if ($this->_id===null)
			return false;
		if (($record=User::model()->findByPk($this->_id))!==null) {
			return (bool)$record->is_disabled;
		}
		return false;
	}

	/**
	 * Generates and saves a new activation key used for verifying email and restoring lost password.
	 * The activation key is then sent by email to the user.
	 *
	 * Note: only the last generated activation key should be valid and an activation key
	 * should have it's generation date saved to verify it's age later.
	 *
	 * @return string
	 */
	public function getActivationKey()
	{
		if ($this->_id===null)
			return false;
		if (($record=User::model()->findByPk($this->_id))!==null) {
			$activationKey = md5(time().mt_rand().$record->username);
			if (!$record->saveAttributes(array('activation_key' => $activationKey))) {
				return false;
			}
			return $activationKey;
		}
		return false;
	}

	/**
	 * Verifies if specified activation key matches the saved one and if it's not too old.
	 * This method should not alter any saved data.
	 * @return integer the verification error code. If there is an error, the error code will be non-zero.
	 */
	public function verifyActivationKey($activationKey)
	{
		if ($this->_id===null)
			return self::ERROR_AKEY_INVALID;
		if (($record=User::model()->findByPk($this->_id))!==null) {
			return $record->activation_key === $activationKey ? self::ERROR_AKEY_NONE : self::ERROR_AKEY_INVALID;
		}
		return self::ERROR_AKEY_INVALID;
	}

	/**
	 * Verify users email address, which could also activate his account and allow him to log in.
	 * Call only after verifying the activation key.
	 * @return boolean
	 */
	public function verifyEmail()
	{
		if ($this->_id===null)
			return false;
		if (($record=User::model()->findByPk($this->_id))!==null) {
			/** 
			 * Only update $record if it's not already been updated, otherwise 
			 * saveAttributes will return false, incorrectly suggesting 
			 * failure.  
			*/
			if (!$record->email_verified) {
				$updates = array('email_verified' => 1);

				if (Yii::app()->controller->module->requireVerifiedEmail && !$record->is_active) {
					$updates['is_active'] = 1;
				}
				if (!$record->saveAttributes($updates)) {
					return false;
				}
			}
			return true;
		}
		return false;
	}
	
	/**
	 * Returns user email address.
	 * @return string
	 */
	public function getEmail()
	{
		return $this->email;
	}

	// }}}

	// {{{ OneTimePasswordIdentityInterface

	/**
	 * Returns current secret used to generate one time passwords. If it's null, two step auth is disabled.
	 * @return string
	 */
	public function getOneTimePasswordSecret()
	{
		if ($this->_id===null)
			return false;
		if (($record=User::model()->findByPk($this->_id))!==null) {
			return $record->one_time_password_secret;
		}
		return false;
	}

	/**
	 * Sets current secret used to generate one time passwords. If it's null, two step auth is disabled.
	 * @param string $secret
	 * @return boolean
	 */
	public function setOneTimePasswordSecret($secret)
	{
		if ($this->_id===null)
			return false;
		if (($record=User::model()->findByPk($this->_id))!==null) {
			return $record->saveAttributes(array('one_time_password_secret' => $secret));
		}
		return false;
	}

	/**
	 * Returns previously used one time password and value of counter used to generate current one time password, used in counter mode.
	 * @return array array(string, integer) 
	 */
	public function getOneTimePassword()
	{
		if ($this->_id===null)
			return array(null, null);
		if (($record=User::model()->findByPk($this->_id))!==null) {
			return array(
				$record->one_time_password_code,
				$record->one_time_password_counter === null ? 1 : $record->one_time_password_counter,
			);
		}
		return array(null, null);
	}

	/**
	 * Sets previously used one time password and value of counter used to generate current one time password, used in counter mode.
	 * @return boolean
	 */
	public function setOneTimePassword($password, $counter = 1)
	{
		if ($this->_id===null)
			return false;
		if (($record=User::model()->findByPk($this->_id))!==null) {
			return $record->saveAttributes(array(
				'one_time_password_code' => $password,
				'one_time_password_counter' => $counter,
			));
		}
		return false;
	}

	// }}}

	// {{{ HybridauthIdentityInterface

	/**
	 * Loads a specific user identity connected to specified provider by an identifier.
	 * @param string $provider
	 * @param string $identifier
	 * @return object a user identity object or null if not found.
	 */
	public static function findByProvider($provider, $identifier)
	{
		$criteria = new CDbCriteria;
		$criteria->with['userRemoteIdentities'] = array('alias'=>'ur');
		$criteria->compare('ur.provider',$provider);
		$criteria->compare('ur.identifier',$identifier);
		$record = User::model()->find($criteria);
		return $record === null ? null : self::createFromUser($record);
	}

	/**
	 * Associates this identity with a remote one identified by a provider name and identifier.
	 * @param string $provider
	 * @param string $identifier
	 * @return boolean
	 */
	public function addRemoteIdentity($provider, $identifier)
	{
		if ($this->_id===null)
			return false;
		$model = new UserRemoteIdentity;
		$model->setAttributes(array(
			'user_id' => $this->_id,
			'provider' => $provider,
			'identifier' => $identifier,
		), false);
		return $model->save();
	}

	// }}}
}
