<?php

Yii::import('usr.components.*');

/**
 * UserIdentity represents the data needed to identity a user.
 * It contains the authentication method that checks if the provided
 * data can identity the user.
 */
abstract class ExampleUserIdentity extends CUserIdentity implements IPasswordHistoryIdentity,IActivatedIdentity,IEditableIdentity
{
	public $email = null;
	public $firstName = null;
	public $lastName = null;
	private $_id = null;

	/**
	 * Authenticates a user.
	 * @return boolean whether authentication succeeds.
	 */
	public function authenticate() {
		$record=User::model()->findByAttributes(array('username'=>$this->username));
		if ($record!==null && $record->verifyPassword($this->password)) {
			$this->_id = $record->id;
			$this->email = $record->email;
			$this->errorCode=self::ERROR_NONE;
			$record->saveAttributes(array('last_visit_on'=>date('Y-m-d H:i:s')));
		} else {
			$this->errorCode=self::ERROR_USERNAME_INVALID;
		}
		return !$this->errorCode;
	}
	
	public function setId($id) {
		$this->_id = $id;
	}
	
	public function getId() {
		return $this->_id;
	}

	public function getPasswordDate($password = null) {
		if ($this->_id===null)
			return null;
		if (($record=User::model()->findByPk($this->_id))!==null) {
			return $record->password_set_on;
		}
		return null;
	}

	public function resetPassword($password) {
		if ($this->_id===null)
			return false;
		if (($record=User::model()->findByPk($this->_id))!==null) {
			//! @todo update password change column and password history table
			return $record->saveAttributes(array(
				'password'=>User::hashPassword($password),
				'password_set_on'=>date('Y-m-d H:i:s'),
			));
		}
		return false;
	}

	public static function find(array $attributes) {
		$record = User::model()->findByAttributes($attributes);
		if ($record === null)
			return null;
		$identity = new UserIdentity($record->username, null);
		$identity->id = $record->id;
		$identity->username = $record->username;
		$identity->email = $record->email;
		$identity->firstName = $record->firstname;
		$identity->lastName = $record->lastname;
		return $identity;
	}

	public function getActivationKey() {
		if ($this->_id===null)
			return false;
		if (($record=User::model()->findByPk($this->_id))!==null) {
			return $record->activkey;
		}
		return false;
	}

	public function verifyActivationKey($activationKey) {
		if ($this->_id===null)
			return self::ERROR_AKEY_INVALID;
		if (($record=User::model()->findByPk($this->_id))!==null) {
			return $record->activkey === $activationKey ? self::ERROR_AKEY_NONE : self::ERROR_AKEY_INVALID;
		}
		return self::ERROR_AKEY_INVALID;
	}

	public function isActive() {
		if ($this->_id===null)
			return false;
		if (($record=User::model()->findByPk($this->_id))!==null) {
			return $record->status === User::STATUS_ACTIVE;
		}
		return false;
	}

	public function isDisabled() {
		if ($this->_id===null)
			return false;
		if (($record=User::model()->findByPk($this->_id))!==null) {
			return $record->status === User::STATUS_BANNED;
		}
		return false;
	}

	public function verifyEmail() {
		return true;
	}
	
	public function getEmail() {
		return $this->email;
	}

	public function save() {
		$record = $this->_id===null ? new User : User::model()->findByPk($this->_id);
		if ($record!==null) {
			$record->setAttributes(array(
				'username' => $this->username,
				'email' => $this->email,
				'firstname' => $this->firstName,
				'lastname' => $this->lastName,
			));
			return $record->save();
		}
		return false;
	}

	public function setAttributes(array $attributes) {
		if (isset($attributes['username']))
			$this->username = $attributes['username'];
		if (isset($attributes['email']))
			$this->email = $attributes['email'];
		if (isset($attributes['firstName']))
			$this->firstName = $attributes['firstName'];
		if (isset($attributes['lastName']))
			$this->lastName = $attributes['lastName'];
		return true;
	}

	public function getAttributes() {
		return array(
			'username' => $this->username,
			'email' => $this->email,
			'firstName' => $this->firstName,
			'lastName' => $this->lastName,
		);
	}
}
