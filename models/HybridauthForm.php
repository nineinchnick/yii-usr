<?php

/**
 * HybridauthForm class.
 * HybridauthForm is the data structure for keeping
 * Hybridauth form data. It is used by the 'login' action of 'HybridauthController'.
 */
class HybridauthForm extends BaseUsrForm
{
	/**
	 * @var string provider name selected from the list of available providers
	 */
	public $provider;
	/**
	 * @var string user identifier
	 */
	public $openid_identifier;

	/**
	 * @var array @see UsrModule::$hybridauthProviders
	 */
	protected $_validProviders = array();
	protected $_hybridAuth;
	protected $_hybridAuthAdapter;
	/**
	 * @var IUserIdentity cached object returned by @see getIdentity()
	 */
	protected $_identity;

	/**
	 * Declares the validation rules.
	 */
	public function rules()
	{
		return array(
			array('provider, openid_identifier', 'filter', 'filter'=>'trim'),
			// can't filter this because it's displayed to the user
			//array('provider', 'filter', 'filter'=>'strtolower'),
			array('provider', 'required'),
			array('provider', 'validProvider'),
			array('openid_identifier', 'required', 'on'=>'openid'),
		);
	}

	public function validProvider($attribute, $params)
	{
		$provider = strtolower($this->$attribute);
		return isset($this->_validProviders[$provider]) && $this->_validProviders[$provider];
	}

	/**
	 * @param array $providers list of valid providers
	 */
	public function setValidProviders($providers)
	{
		$this->_validProviders = array();
		foreach($providers as $provider=>$options) {
			$this->_validProviders[strtolower($provider)] = !isset($options['enabled']) || $options['enabled'];
		}
		return $this;
	}

	public function setHybridAuth($hybridAuth)
	{
		$this->_hybridAuth = $hybridAuth;
		return $this;
	}

	public function getHybridAuthAdapter()
	{
		return $this->_hybridAuthAdapter;
	}

	public function getIdentity()
	{
		return $this->_identity;
	}

	/**
	 * Declares attribute labels.
	 */
	public function attributeLabels()
	{
		return array(
			'provider'		=> Yii::t('UsrModule.usr','Provider'),
			'openid_identifier'		=> Yii::t('UsrModule.usr','OpenID Identifier'),
		);
	}

	public function requiresFilling()
	{
		if (strtolower($this->provider) == 'openid' && empty($this->openid_identifier))
			return true;

		return false;
	}

	public function loggedInRemotely()
	{
		return ($adapter=$this->getHybridAuthAdapter()) !== null && $adapter->isUserConnected();
	}

	public function login()
	{
		$userIdentityClass = $this->userIdentityClass;
		$fakeIdentity = new $userIdentityClass(null, null);
		if (!($fakeIdentity instanceof IHybridauthIdentity))
			throw new CException(Yii::t('UsrModule.usr','The {class} class must implement the {interface} interface.',array('{class}'=>get_class($identity),'{interface}'=>'IHybridauthIdentity')));

		$params = $this->getAttributes();
		unset($params['provider']);
		$this->_hybridAuthAdapter = $this->_hybridAuth->authenticate(strtolower($this->provider), $params);

		if ($this->_hybridAuthAdapter->isUserConnected()) {
			$profile = $this->_hybridAuthAdapter->getUserProfile();
			if (($this->_identity=$userIdentityClass::findByProvider(strtolower($this->provider), $profile->identifier)) !== null) {
				return Yii::app()->user->login($this->_identity,0);
			}
		}
		return false;
	}

	public function associate($user_id)
	{
		$userIdentityClass = $this->userIdentityClass;
		$identity = new $userIdentityClass(null, null);
		if (!($identity instanceof IHybridauthIdentity))
			throw new CException(Yii::t('UsrModule.usr','The {class} class must implement the {interface} interface.',array('{class}'=>get_class($identity),'{interface}'=>'IHybridauthIdentity')));
		$identity->setId($user_id);
		$profile = $this->_hybridAuthAdapter->getUserProfile();
		if ($identity instanceof IPictureIdentity && !empty($profile->photoURL)) {
			$picture = $identity->getPictureUrl();
			if ($picture['url'] != $profile->photoURL) {
				$path = tempnam(sys_get_temp_dir(), 'external_profile_picture_');
				if (copy($profile->photoURL, $path)) {
					$uploadedFile = new CUploadedFile(basename($path), $path, CFileHelper::getMimeType($path), filesize($path), UPLOAD_ERR_OK);
					$identity->removePicture();
					$identity->savePicture($uploadedFile);
				}
			}
		}
		return $identity->addRemoteIdentity(strtolower($this->provider), $profile->identifier);
	}
}
