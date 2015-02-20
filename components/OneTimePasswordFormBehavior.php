<?php
/**
 * OneTimePasswordFormBehavior class file.
 *
 * @author Jan Was <jwas@nets.com.pl>
 */

/**
 * OneTimePasswordFormBehavior adds one time password validation to a login form model component.
 *
 * @property GoogleAuthenticator authenticator If null, set to a new instance of GoogleAuthenticator class.
 * @property string mode If set to OneTimePasswordFormBehavior::OTP_TIME or OneTimePasswordFormBehavior::OTP_COUNTER, two step authentication is enabled using one time passwords.
 *                       Time mode uses codes generated using current time and requires the user to use an external application, like Google Authenticator on Android.
 *                       Counter mode uses codes generated using a sequence and sends them to user's email.
 * @property boolean required Should the user be allowed to log in even if a secret hasn't been generated yet (is null).
 *                            This only makes sense when mode is 'counter', secrets are generated when registering users and a code is sent via email.
 * @property integer timeout Number of seconds for how long is the last verified code valid.
 * @property CFormModel $owner The owner model that this behavior is attached to.
 * @property array $oneTimePasswordConfig Configuration options, @see OneTimePasswordFormBehavior.
 *
 * @author Jan Was <jwas@nets.com.pl>
 */
class OneTimePasswordFormBehavior extends FormModelBehavior
{
    const OTP_SECRET_PREFIX = 'UsrModule.oneTimePassword.';
    const OTP_COOKIE = 'otp';
    const OTP_NONE = 'none';
    const OTP_TIME = 'time';
    const OTP_COUNTER = 'counter';

    /**
     * @var string One time password as a token entered by the user.
     */
    public $oneTimePassword;
    /**
     * @var GoogleAuthenticator If null, set to a new instance of GoogleAuthenticator class.
     */
    public $authenticator;
    /**
     * @var string If set to OneTimePasswordFormBehavior::OTP_TIME or OneTimePasswordFormBehavior::OTP_COUNTER, two step authentication is enabled using one time passwords.
     *             Time mode uses codes generated using current time and requires the user to use an external application, like Google Authenticator on Android.
     *             Counter mode uses codes generated using a sequence and sends them to user's email.
     */
    public $mode;
    /**
     * @var boolean Should the user be allowed to log in even if a secret hasn't been generated yet (is null).
     *              This only makes sense when mode is 'counter', secrets are generated when registering users and a code is sent via email.
     */
    public $required;
    /**
     * @var integer Number of seconds for how long is the last verified code valid.
     */
    public $timeout;

    private $_oneTimePasswordConfig = array(
        'secret' => null,
        'previousCode' => null,
        'previousCounter' => null,
    );

    private $_controller;

    /**
     * @inheritdoc
     */
    public function events()
    {
        return array_merge(parent::events(), array(
            'onAfterValidate' => 'afterValidate',
        ));
    }

    /**
     * @inheritdoc
     */
    public function filterRules($rules = array())
    {
        $behaviorRules = array(
            array('oneTimePassword', 'filter', 'filter' => 'trim', 'on' => 'verifyOTP'),
            array('oneTimePassword', 'default', 'setOnEmpty' => true, 'value' => null, 'on' => 'verifyOTP'),
            array('oneTimePassword', 'required', 'on' => 'verifyOTP'),
            array('oneTimePassword', 'validOneTimePassword', 'except' => 'hybridauth'),
        );

        return array_merge($rules, $this->applyRuleOptions($behaviorRules));
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return array(
            'oneTimePassword' => Yii::t('UsrModule.usr', 'One Time Password'),
        );
    }

    public function getController()
    {
        return $this->_controller;
    }

    public function setController($value)
    {
        $this->_controller = $value;
    }

    public function getOneTimePasswordConfig()
    {
        return $this->_oneTimePasswordConfig;
    }

    public static function getDefaultAuthenticator()
    {
        require dirname(__FILE__).'/extensions/GoogleAuthenticator.php/lib/GoogleAuthenticator.php';

        return new GoogleAuthenticator();
    }

    public function setOneTimePasswordConfig(array $config)
    {
        foreach ($config as $key => $value) {
            if ($this->_oneTimePasswordConfig[$key] === null) {
                $this->_oneTimePasswordConfig[$key] = $value;
            }
        }

        return $this;
    }

    protected function loadOneTimePasswordConfig()
    {
        $identity = $this->owner->getIdentity();
        if (!($identity instanceof IOneTimePasswordIdentity)) {
            throw new CException(Yii::t('UsrModule.usr', 'The {class} class must implement the {interface} interface.', array('{class}' => get_class($identity), '{interface}' => 'IOneTimePasswordIdentity')));
        }
        list($previousCode, $previousCounter) = $identity->getOneTimePassword();
        $this->setOneTimePasswordConfig(array(
            'secret' => $identity->getOneTimePasswordSecret(),
            'previousCode' => $previousCode,
            'previousCounter' => $previousCounter,
        ));
        if ($this->authenticator === null) {
            $this->authenticator = self::getDefaultAuthenticator();
        }

        return $this;
    }

    public function getOTP($key)
    {
        if ($this->_oneTimePasswordConfig[$key] === null) {
            $this->loadOneTimePasswordConfig();
        }

        return $this->_oneTimePasswordConfig[$key];
    }

    public function getNewCode()
    {
        $this->loadOneTimePasswordConfig();
        // extracts: $secret, $previousCode, $previousCounter
        extract($this->_oneTimePasswordConfig);

        return $this->authenticator->getCode($secret, $this->mode == OneTimePasswordFormBehavior::OTP_TIME ? null : $previousCounter);
    }

    public function validOneTimePassword($attribute, $params)
    {
        if ($this->owner->hasErrors()) {
            return;
        }
        $this->loadOneTimePasswordConfig();
        // extracts: $secret, $previousCode, $previousCounter
        extract($this->_oneTimePasswordConfig);

        if (($this->mode !== OneTimePasswordFormBehavior::OTP_TIME && $this->mode !== OneTimePasswordFormBehavior::OTP_COUNTER) || (!$this->required && $secret === null)) {
            return true;
        }
        if ($this->required && $secret === null) {
            // generate and save a new secret only if required to do so, in other cases user must verify that the secret works
            $secret = $this->_oneTimePasswordConfig['secret'] = $this->authenticator->generateSecret();
            $this->owner->getIdentity()->setOneTimePasswordSecret($secret);
        }

        if ($this->isValidOTPCookie(Yii::app()->request->cookies->itemAt(OneTimePasswordFormBehavior::OTP_COOKIE), $this->owner->username, $secret, $this->timeout)) {
            return true;
        }
        if (empty($this->owner->$attribute)) {
            $this->owner->addError($attribute, Yii::t('UsrModule.usr', 'Enter a valid one time password.'));
            $this->owner->scenario = 'verifyOTP';
            if ($mode === OneTimePasswordFormBehavior::OTP_COUNTER) {
                $this->_controller->sendEmail($this, 'oneTimePassword');
            }
            if (YII_DEBUG) {
                $this->oneTimePassword = $this->authenticator->getCode($secret, $this->mode === OneTimePasswordFormBehavior::OTP_TIME ? null : $previousCounter);
            }

            return false;
        }
        if ($this->mode === OneTimePasswordFormBehavior::OTP_TIME) {
            $valid = $this->authenticator->checkCode($secret, $this->owner->$attribute);
        } elseif ($this->mode === OneTimePasswordFormBehavior::OTP_COUNTER) {
            $valid = $this->authenticator->getCode($secret, $previousCounter) == $this->owner->$attribute;
        } else {
            $valid = false;
        }
        if (!$valid) {
            $this->owner->addError($attribute, Yii::t('UsrModule.usr', 'Entered code is invalid.'));
            $this->owner->scenario = 'verifyOTP';

            return false;
        }
        if ($this->owner->$attribute == $previousCode) {
            if ($this->mode === OneTimePasswordFormBehavior::OTP_TIME) {
                $message = Yii::t('UsrModule.usr', 'Please wait until next code will be generated.');
            } elseif ($this->mode === OneTimePasswordFormBehavior::OTP_COUNTER) {
                $message = Yii::t('UsrModule.usr', 'Please log in again to request a new code.');
            }
            $this->owner->addError($attribute, Yii::t('UsrModule.usr', 'Entered code has already been used.').' '.$message);
            $this->owner->scenario = 'verifyOTP';

            return false;
        }
        $this->owner->getIdentity()->setOneTimePassword($this->owner->$attribute, $this->mode === OneTimePasswordFormBehavior::OTP_TIME ? floor(time() / 30) : $previousCounter + 1);

        return true;
    }

    protected function afterValidate($event)
    {
        if ($this->owner->scenario === 'hybridauth') {
            return;
        }

        // extracts: $secret, $previousCode, $previousCounter
        extract($this->_oneTimePasswordConfig);

        $cookie = $this->createOTPCookie($this->owner->username, $secret, $this->timeout);
        Yii::app()->request->cookies->add($cookie->name, $cookie);
    }

    public function createOTPCookie($username, $secret, $timeout, $time = null)
    {
        if ($time === null) {
            $time = time();
        }
        $cookie = new CHttpCookie(OneTimePasswordFormBehavior::OTP_COOKIE, '');
        $cookie->expire = time() + ($timeout <= 0 ? 10*365*24*3600 : $timeout);
        $cookie->httpOnly = true;
        $data = array('username' => $username, 'time' => $time, 'timeout' => $timeout);
        $cookie->value = $time.':'.Yii::app()->getSecurityManager()->computeHMAC(serialize($data), $secret);

        return $cookie;
    }

    public function isValidOTPCookie($cookie, $username, $secret, $timeout, $time = null)
    {
        if ($time === null) {
            $time = time();
        }

        if (!$cookie || empty($cookie->value) || !is_string($cookie->value)) {
            return false;
        }
        $parts = explode(":", $cookie->value, 2);
        if (count($parts) != 2) {
            return false;
        }
        list($creationTime, $hash) = $parts;
        $data = array('username' => $username, 'time' => (int) $creationTime, 'timeout' => $timeout);
        $validHash = Yii::app()->getSecurityManager()->computeHMAC(serialize($data), $secret);

        return ($timeout <= 0 || $creationTime + $timeout >= $time) && $hash === $validHash;
    }
}
