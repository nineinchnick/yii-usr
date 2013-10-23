<?php

/**
 * HybridauthForm class.
 * HybridauthForm is the data structure for keeping
 * Hybridauth form data. It is used by the 'login' action of 'HybridauthController'.
 */
class HybridauthForm extends CFormModel
{
	public $provider;
	public $openid_identifier;

	protected $_validProviders = array();
	protected $_hybridAuth;
	protected $_hybridAuthAdapter;
	protected $_identity;

	/**
	 * Declares the validation rules.
	 */
	public function rules()
	{
		return array(
			array('provider, openid_identifier', 'filter', 'filter'=>'trim'),
			array('provider', 'filter', 'filter'=>'strtolower'),
			array('provider', 'required'),
			array('provider', 'validProvider'),
			array('openid_identifier', 'required', 'on'=>'openid'),
		);
	}

	public function validProvider($attribute, $params)
	{
		$provider = $this->$attribute;
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
		if ($this->provider == 'openid' && empty($this->openid_identifier))
			return true;

		return false;
	}

	public function login()
	{
		$userIdentityClass = Yii::app()->controller->module->userIdentityClass;
		//! @todo validate if $userIdentityClass implements IActivatedIdentity

		$params = $this->getAttributes();
		unset($params['provider']);
		$this->_hybridAuthAdapter = $this->_hybridAuth->authenticate($this->provider, $params);

		if ($this->_hybridAuthAdapter->isUserConnected()) {
			$profile = $this->_hybridAuthAdapter->getUserProfile();
			$username = $profile->identifier;
			$email = $profile->emailVerified !== null ? $profile->emailVerified : $profile->email;
			if (($this->_identity=$userIdentityClass::find(array('username'=>$username))) !== null || ($this->_identity=$userIdentityClass::find(array('email'=>$email))) !== null) {
				return Yii::app()->user->login($this->_identity,0);
			}
		}
		return false;
	}

	public function register()
	{
		$profile = $this->_hybridAuthAdapter->getUserProfile();
		$this->_identity->setAttributes(array(
			'username' => $profile->identifier,
			'email' => $profile->emailVerifier !== null ? $profile->emailVerifier : $profile->email,
			'firstName' => $profile->firstName,
			'lastName' => $profile->lastName,
		));
		if ($this->_identity->save())
			return Yii::app()->user->login($this->_identity,0);

		return false;
	}
}
