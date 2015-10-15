<?php

/**
 * LoginForm class.
 * LoginForm is the data structure for keeping
 * user login form data. It is used by the 'login' action of 'DefaultController'.
 */
class LoginForm extends BasePasswordForm
{
    public $username;
    public $password;
    public $rememberMe;

    /**
     * @var IdentityInterface cached object returned by @see getIdentity()
     */
    private $_identity;

    /**
     * Retrieve list of scenarios aviable in model
     * @return array
     */
    public function getAvailableScenarios()
    {
        return array_merge(parent::getAvailableScenarios(), array('reset', 'verifyOTP'));
    }

    /**
     * Declares the validation rules.
     * The rules state that username and password are required,
     * and password needs to be authenticated.
     */
    public function rules()
    {
        $rules = $this->filterRules(array_merge(array(
            array('username, password', 'filter', 'filter' => 'trim'),
            array('username, password', 'required'),
            array('rememberMe', 'boolean'),
            array('password', 'authenticate'),
        ), $this->rulesAddScenario(parent::rules(), 'reset')));

        return $rules;
    }

    /**
     * Declares attribute labels.
     */
    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(), array(
            'username'        => Yii::t('UsrModule.usr', 'Username'),
            'password'        => Yii::t('UsrModule.usr', 'Password'),
            'rememberMe'    => Yii::t('UsrModule.usr', 'Remember me when logging in next time'),
        ), $this->getBehaviorLabels());
    }

    /**
     * @inheritdoc
     */
    public function getIdentity()
    {
        if ($this->_identity === null) {
            $userIdentityClass = $this->userIdentityClass;
            $this->_identity = new $userIdentityClass($this->username, $this->password);
        }

        return $this->_identity;
    }

    /**
     * Authenticates the password.
     * This is the 'authenticate' validator as declared in rules().
     * @param  string  $attribute
     * @param  array   $params
     * @return boolean
     */
    public function authenticate($attribute, $params)
    {
        if ($this->hasErrors()) {
            return;
        }
        $identity = $this->getIdentity();
        $identity->authenticate();
        if (!$identity->getIsAuthenticated()) {
            $this->addError('password', !empty($identity->errorMessage) ? $identity->errorMessage : Yii::t('UsrModule.usr', 'Invalid username or password.'));

            return false;
        }

        return true;
    }

    /**
     * A wrapper for the passwordHasNotExpired method from ExpiredPasswordBehavior.
     * @param $attribute string
     * @param $params array
     */
    public function passwordHasNotExpired($attribute, $params)
    {
        if (($behavior = $this->asa('expiredPasswordBehavior')) !== null) {
            return $behavior->passwordHasNotExpired($attribute, $params);
        }

        return true;
    }

    /**
     * A wrapper for the validOneTimePassword method from OneTimePasswordBehavior.
     * @param $attribute string
     * @param $params array
     */
    public function validOneTimePassword($attribute, $params)
    {
        if (($behavior = $this->asa('oneTimePasswordBehavior')) !== null) {
            return $behavior->validOneTimePassword($attribute, $params);
        }

        return true;
    }

    /**
     * Resets user password using the new one given in the model.
     * @return boolean whether password reset was successful
     */
    public function resetPassword()
    {
        if ($this->hasErrors()) {
            return;
        }
        $identity = $this->getIdentity();
        if (($message = $identity->resetPassword($this->newPassword)) !== true) {
            $this->addError('newPassword', is_string($message) ? $message : Yii::t('UsrModule.usr', 'Failed to reset the password.'));

            return false;
        }

        return true;
    }

    /**
     * Logs in the user using the given username and password in the model.
     * @param  integer $duration For how long the user will be logged in without any activity, in seconds.
     * @return boolean whether login is successful
     */
    public function login($duration = 0)
    {
        if ($this->beforeLogin()) {
            $identity = $this->getIdentity();
            if ($this->scenario === 'reset') {
                $identity->password = $this->newPassword;
                $identity->authenticate();
            }
            if ($identity->getIsAuthenticated()) {
                $result = $this->webUser->login($identity, $this->rememberMe ? $duration : 0);
                if ($result) {
                    $this->afterLogin();
                }

                return $result;
            }
        }

        return false;
    }

    public function beforeLogin()
    {
        $event = new CModelEvent();
        $this->onBeforeLogin($event);

        return $event->isValid;
    }

    public function afterLogin()
    {
        return $this->onAfterLogin();
    }

    /**
     * Fire afterLogin events
     * @param CFormModel $model
     */
    public function onBeforeLogin($event)
    {
        $this->raiseEvent('onBeforeLogin', $event);
    }

    /**
     * Fire afterLogin events
     * @param CFormModel $model
     */
    public function onAfterLogin()
    {
        $this->raiseEvent('onAfterLogin', new CEvent($this, array('success' => true)));
    }
}
