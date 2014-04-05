<?php

Yii::import('usr.components.*');

/**
 * UserIdentity represents the data needed to identity a user.
 * It contains the authentication method that checks if the provided
 * data can identity the user.
 */
abstract class ExampleUserIdentity extends CUserIdentity
	implements IPasswordHistoryIdentity,
	IActivatedIdentity,
	IEditableIdentity,
	IHybridauthIdentity,
	IOneTimePasswordIdentity,
	IPictureIdentity,
	IManagedIdentity
{
	const ERROR_USER_DISABLED=1000;
	const ERROR_USER_INACTIVE=1001;
	const ERROR_USER_LOCKED=1002;

    const MAX_FAILED_LOGIN_ATTEMPTS = 5;
    const LOGIN_ATTEMPTS_COUNT_SECONDS = 1800;

	public $email = null;
	public $firstName = null;
	public $lastName = null;
	private $_id = null;
	private $_activeRecord = null;

	protected static function createFromUser(User $user)
	{
		$identity = new UserIdentity($user->username, null);
		$identity->initFromUser($user);
		return $identity;
	}

	protected function initFromUser(User $user)
	{
		$this->_activeRecord = $user;
		$this->id = $user->getPrimaryKey();
		$this->username = $user->username;
		$this->email = $user->email;
		$this->firstName = $user->firstname;
		$this->lastName = $user->lastname;
	}

	protected function getActiveRecord()
	{
		if ($this->_activeRecord === null && $this->_id !== null) {
			$this->_activeRecord = User::model()->findByPk($this->_id);
		}
		return $this->_activeRecord;
	}

	// {{{ IUserIdentity

    /**
     * @inheritdoc
     */
    public function authenticate()
    {
        $record = User::model()->findByAttributes(array('username'=>$this->username));
        $authenticated = $record !== null && $record->verifyPassword($this->password);

        $attempt = new UserLoginAttempt;
        $attempt->username = $this->username;
        $attempt->user_id = !is_null($record) ? $record->id : NULL;
        $attempt->is_successful = $authenticated;
        $attempt->save();

        if (UserLoginAttempt::hasTooManyFailedAttempts($this->username, self::MAX_FAILED_LOGIN_ATTEMPTS, self::LOGIN_ATTEMPTS_COUNT_SECONDS)) {
            // this is the first check not to reveal if the specified user account exists or not
            $this->errorCode=self::ERROR_USER_LOCKED;
            $this->errorMessage=Yii::t('UsrModule.usr','User account has been locked due to too many failed login attempts. Try again later.');
        } elseif (!$authenticated) {
            $this->errorCode=self::ERROR_USERNAME_INVALID;
            $this->errorMessage=Yii::t('UsrModule.usr','Invalid username or password.');
        } elseif ($record->is_disabled) {
            $this->errorCode=self::ERROR_USER_DISABLED;
            $this->errorMessage=Yii::t('UsrModule.usr','User account has been disabled.');
        } else if (!$record->is_active) {
            $this->errorCode=self::ERROR_USER_INACTIVE;
            $this->errorMessage=Yii::t('UsrModule.usr','User account has not been activated yet.');
        } else {
            $this->errorCode=self::ERROR_NONE;
            $this->errorMessage='';
            $this->initFromUser($record);
            $record->saveAttributes(array('last_visit_on'=>date('Y-m-d H:i:s')));
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
		if (($record=$this->getActiveRecord()) === null)
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
		if (($record=$this->getActiveRecord())===null) {
			return false;
		}
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

	// }}}

	// {{{ EditableIdentityInterface

	/**
	 * Saves a new or existing identity. Does not set or change the password.
	 * @see IPasswordHistoryIdentity::resetPassword()
	 * Should detect if the email changed and mark it as not verified.
	 * @param boolean $requireVerifiedEmail
	 * @return boolean
	 */
	public function save($requireVerifiedEmail=false)
	{
		if ($this->_id === null) {
			$record = new User;
			$record->password = 'x';
			$record->is_active = $requireVerifiedEmail ? 0 : 1;
		} else {
			$record = $this->getActiveRecord();
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
				$this->initFromUser($record);
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

    /**
     * @inheritdoc
     */
	public static function find(array $attributes)
	{
		$records = User::model()->findAllByAttributes($attributes);
        if (count($records)!==1) {
            return null;
        }
        $record = reset($records);
		return self::createFromUser($record);
	}

	/**
	 * Checkes if user account is active. This should not include disabled (banned) status.
	 * This could include if the email address has been verified.
	 * Same checks should be done in the authenticate() method, because this method is not called before logging in.
	 * @return boolean
	 */
	public function isActive()
	{
		if (($record=$this->getActiveRecord())===null) {
			return false;
		}
		return (bool)$record->is_active;
	}

	/**
	 * Checkes if user account is disabled (banned). This should not include active status.
	 * @return boolean
	 */
	public function isDisabled()
	{
		if (($record=$this->getActiveRecord())===null) {
			return false;
		}
		return (bool)$record->is_disabled;
	}

	/**
	 * Checkes if user email address is verified.
	 * @return boolean
	 */
	public function isVerified()
	{
		if (($record=$this->getActiveRecord())===null) {
			return false;
		}
		return (bool)$record->email_verified;
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
		if (($record=$this->getActiveRecord())===null) {
			return false;
		}
		$activationKey = md5(time().mt_rand().$record->username);
		if (!$record->saveAttributes(array('activation_key' => $activationKey))) {
			return false;
		}
		return $activationKey;
	}

	/**
	 * Verifies if specified activation key matches the saved one and if it's not too old.
	 * This method should not alter any saved data.
	 * @return integer the verification error code. If there is an error, the error code will be non-zero.
	 */
	public function verifyActivationKey($activationKey)
	{
		if (($record=$this->getActiveRecord())===null) {
			return self::ERROR_AKEY_INVALID;
		}
		return $record->activation_key === $activationKey ? self::ERROR_AKEY_NONE : self::ERROR_AKEY_INVALID;
	}

	/**
	 * Verify users email address, which could also activate his account and allow him to log in.
	 * Call only after verifying the activation key.
	 * @param boolean $requireVerifiedEmail
	 * @return boolean
	 */
	public function verifyEmail($requireVerifiedEmail=false)
	{
		if (($record=$this->getActiveRecord())===null) {
			return false;
		}
		/**
		 * Only update $record if it's not already been updated, otherwise
		 * saveAttributes will return false, incorrectly suggesting
		 * failure.
		 */
		if (!$record->email_verified) {
			$attributes = array('email_verified' => 1);

			if ($requireVerifiedEmail && !$record->is_active) {
				$attributes['is_active'] = 1;
			}
			if (!$record->saveAttributes($attributes)) {
				return false;
			}
		}
		return true;
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
		if (($record=$this->getActiveRecord())===null) {
			return false;
		}
		return $record->one_time_password_secret;
	}

	/**
	 * Sets current secret used to generate one time passwords. If it's null, two step auth is disabled.
	 * @param string $secret
	 * @return boolean
	 */
	public function setOneTimePasswordSecret($secret)
	{
		if (($record=$this->getActiveRecord())===null) {
			return false;
		}
		return $record->saveAttributes(array('one_time_password_secret' => $secret));
	}

	/**
	 * Returns previously used one time password and value of counter used to generate current one time password, used in counter mode.
	 * @return array array(string, integer)
	 */
	public function getOneTimePassword()
	{
		if (($record=$this->getActiveRecord())===null) {
			return array(null, null);
		}
		return array(
			$record->one_time_password_code,
			$record->one_time_password_counter === null ? 1 : $record->one_time_password_counter,
		);
	}

	/**
	 * Sets previously used one time password and value of counter used to generate current one time password, used in counter mode.
	 * @return boolean
	 */
	public function setOneTimePassword($password, $counter = 1)
	{
		if (($record=$this->getActiveRecord())===null) {
			return false;
		}
		return $record->saveAttributes(array(
			'one_time_password_code' => $password,
			'one_time_password_counter' => $counter,
		));
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
	 * @inheritdoc
	 */
	public function addRemoteIdentity($provider, $identifier)
	{
		if ($this->_id===null)
			return false;
		UserRemoteIdentity::model()->deleteAllByAttributes(array('provider'=>$provider, 'identifier'=>$identifier));
		$model = new UserRemoteIdentity;
		$model->setAttributes(array(
			'user_id' => $this->_id,
			'provider' => $provider,
			'identifier' => $identifier,
		), false);
		return $model->save();
	}

    /**
     * @inheritdoc
     */
    public function removeRemoteIdentity($provider)
    {
		if ($this->_id===null)
			return false;
		UserRemoteIdentity::model()->deleteAllByAttributes(array('provider'=>$provider, 'user_id'=>$this->_id));
        return true;
    }

    /**
     * @inheritdoc
     */
    public function hasRemoteIdentity($provider)
    {
		if ($this->_id===null)
			return false;
		return 0 != UserRemoteIdentity::model()->countByAttributes(array('provider'=>$provider, 'user_id'=>$this->_id));
    }

    /**
     * Similar to @see getAttributes() but reads the remote profile instead of current identity.
     * @param mixed $remoteProfie
     * @return array
     */
    public static function getRemoteAttributes($remoteProfile)
    {
		$email = (isset($remoteProfile->emailVerifier) && $remoteProfile->emailVerifier !== null) ? $remoteProfile->emailVerifier : $remoteProfile->email;
        return array(
            'username' => $email,
            'email' => $email,
            'firstName' => $remoteProfile->firstName,
            'lastName' => $remoteProfile->lastName,
        );
    }

	// }}}

	// {{{ IPictureIdentity

	/**
	 * @inheritdoc
	 */
	public function savePicture($picture)
	{
		if (($record=$this->getActiveRecord())===null) {
			return null;
		}
		$pictureRecord = $record->userProfilePictures(array('condition'=>'original_picture_id IS NULL'));
		if (!empty($pictureRecord)) {
			$pictureRecord = $pictureRecord[0];
		} else {
			$pictureRecord = new UserProfilePicture;
			$pictureRecord->user_id = $this->_id;
		}
		$picturePath = $picture->getTempName();
		$pictureRecord->filename = $picture;
		$pictureRecord->mimetype = CFileHelper::getMimeType($picturePath);
		$pictureRecord->contents = base64_encode(file_get_contents($picturePath));

		if (($size = @getimagesize($picturePath)) !== false) {
			list($width, $height, $type, $attr) = $size;
			$pictureRecord->width = $width;
			$pictureRecord->height = $height;
		} else {
			$pictureRecord->width = 0;
			$pictureRecord->height = 0;
		}
		return $pictureRecord->save() && $this->saveThumbnail($picture, $pictureRecord);
	}

	protected function saveThumbnail($picture, $pictureRecord)
	{
		// skip thumbnail if couldn't read size of original picture
		if ($pictureRecord->width == 0 || $pictureRecord->height == 0) {
			return true;
		}
		// calculate thumbnail dimensions with max width and height at 80
		$max_width = 80;
		$max_height = 80;

		$width = $pictureRecord->width;
		$height = $pictureRecord->height;
		if ($width > $max_width || $height > $max_height ) {
			if ($width > $height) {
				$height = floor($height / ($width / $max_width));
				$width = $max_width;
			} else {
				$width = floor($width / ($height / $max_height));
				$height = $max_height;
			}
		}

		// create the thumbnail image (always a jpeg)
		$thumbImage = imagecreatetruecolor($width, $height);
		$sourceImage = imagecreatefromstring(base64_decode($pictureRecord->contents));
		imagecopyresized($thumbImage, $sourceImage, 0, 0, 0, 0, $width, $height, $pictureRecord->width, $pictureRecord->height);
		ob_start();
		imagejpeg($thumbImage);
		$contents = ob_get_clean();

		// update existing thumbnail or create a new one
		$thumbnail = $pictureRecord->thumbnails;
		if (!empty($thumbnail)) {
			$thumbnail = $thumbnail[0];
		} else {
			$thumbnail = new UserProfilePicture;
			$thumbnail->original_picture_id = $pictureRecord->id;
			$thumbnail->user_id = $pictureRecord->user_id;
			$thumbnail->filename = $pictureRecord->filename;
			$thumbnail->mimetype = 'image/jpeg';
		}
		$thumbnail->width = $width;
		$thumbnail->height = $height;
		$thumbnail->contents = base64_encode($contents);
		return $thumbnail->save();
	}

	/**
	 * @inheritdoc
	 */
	public function getPictureUrl($width=null, $height=null)
	{
		if (($record=$this->getActiveRecord())===null) {
			return null;
		}
		// try to locate biggest picture smaller than specified dimensions
		$criteria = array('select' => 'id', 'order' => 'width DESC', 'limit' => 1,);
		if ($width !== null && $height !== null) {
			$criteria['condition'] = 'width <= :width AND height <= :height';
			$criteria['params'] = array(':width'=>$width, ':height'=>$height);
		}
		$pictures = $record->userProfilePictures($criteria);
		if (!empty($pictures)) {
			return array(
				'url'	=> Yii::app()->createAbsoluteUrl('/usr/profilePicture', array('id'=>$pictures[0]->id)),
				'width'	=> $pictures[0]->width,
				'height'=> $pictures[0]->height,
			);
		}

		// if no picture has been found, use a Gravatar
		$hash = md5(strtolower(trim($record->email)));
		// more at http://gravatar.com/site/implement/images/
		$options = array(
			//'forcedefault' => 'y',
			'rating'=> 'g',
			'd'		=> 'retro',
			's'		=> $width,
		);
		$host = Yii::app()->request->isSecureConnection ? 'https://secure.gravatar.com' : 'http://gravatar.com';
		return array(
			'url'	=> $host.'/avatar/'.$hash.'?'.http_build_query($options),
			'width'	=> $width,
			'height'=> $height,
		);
	}

	/**
	 * @inheritdoc
	 */
	public function getPicture($id, $currentIdentity=true)
	{
		$criteria = new CDbCriteria;
		$criteria->addColumnCondition(array('id'=>$id));
		if ($currentIdentity) {
			$criteria->addColumnCondition(array('user_id'=>$this->_id));
		}
		if (($picture=UserProfilePicture::model()->find($criteria)) === null) {
			return null;
		}
		return array(
			'mimetype'=>$picture->mimetype,
			'width'=>$picture->width,
			'height'=>$picture->height,
			'picture'=>base64_decode($picture->contents),
		);
	}

	/**
	 * @inheritdoc
	 */
	public function removePicture($id=null)
	{
		if ($this->_id===null) {
			return 0;
		}
		$attributes = array('user_id'=>$this->_id);
		if ($id !== null) {
			$attributes['id'] = $id;
		}
		return UserProfilePicture::model()->deleteAllByAttributes($attributes);
	}

	// }}}

	// {{{ IManagedIdentity

	/**
	 * @inheritdoc
	 */
	public function getDataProvider(SearchForm $searchForm)
	{
		$criteria=new CDbCriteria;

		$criteria->compare('id', $searchForm->id);
		$criteria->compare('username', $searchForm->username,true);
		$criteria->compare('email', $searchForm->email,true);
		$criteria->compare('firstname', $searchForm->firstName,true);
		$criteria->compare('lastname', $searchForm->lastName,true);
		$criteria->compare('created_on', $searchForm->createdOn);
		$criteria->compare('updated_on', $searchForm->updatedOn);
		$criteria->compare('last_visit_on', $searchForm->lastVisitOn);
		$criteria->compare('email_verified', $searchForm->emailVerified);
		$criteria->compare('is_active', $searchForm->isActive);
		$criteria->compare('is_disabled', $searchForm->isDisabled);
		$dataProvider = new CActiveDataProvider('User', array('criteria'=>$criteria, 'keyAttribute'=>'id'));
		$identities = array();
		foreach($dataProvider->getData() as $row) {
			$identities[] = self::createFromUser($row);
		}
		$dataProvider->setData($identities);
		return $dataProvider;
	}

	/**
	 * @inheritdoc
	 */
	public function toggleStatus($status)
	{
		if (($record=$this->getActiveRecord())===null) {
			return false;
		}
		switch($status) {
		case self::STATUS_EMAIL_VERIFIED: $attributes['email_verified'] = !$record->email_verified; break;
		case self::STATUS_IS_ACTIVE: $attributes['is_active'] = !$record->is_active; break;
		case self::STATUS_IS_DISABLED: $attributes['is_disable'] = !$record->is_disabled; break;
		}
		return $record->saveAttributes($attributes);
	}

	/**
	 * @inheritdoc
	 */
	public function delete()
	{
		if (($record=$this->getActiveRecord())===null) {
			return false;
		}
		return $record->delete();
	}

	/**
	 * @inheritdoc
	 */
	public function getTimestamps($key=null)
	{
		if (($record=$this->getActiveRecord())===null) {
			return false;
		}
		$timestamps = array(
			'createdOn' => $record->created_on,
			'updatedOn' => $record->updated_on,
			'lastVisitOn' => $record->last_visit_on,
			'passwordSetOn' => $record->password_set_on,
		);
		// can't use isset, since it returns false for null values
		return $key === null || !array_key_exists($key, $timestamps) ? $timestamps : $timestamps[$key];
	}

	// }}}
}
