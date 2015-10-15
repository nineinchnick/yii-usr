<?php
/**
 * ExpiredPasswordBehavior class file.
 *
 * @author Jan Was <jwas@nets.com.pl>
 */

/**
 * ExpiredPasswordBehavior adds captcha validation to a form model component.
 * The model should extend from {@link CFormModel} or its child classes.
 *
 * The user identity class must implement IPasswordHistoryIdentity interface.
 *
 * @property CFormModel $owner The owner model that this behavior is attached to.
 * @property integer $passwordTimeout Number of days after which user is requred to reset his password after logging in.
 *
 * @author Jan Was <jwas@nets.com.pl>
 */
class ExpiredPasswordBehavior extends FormModelBehavior
{
    private $_passwordTimeout;

    /**
     * @return integer Number of days after which user is requred to reset his password after logging in.
     */
    public function getPasswordTimeout()
    {
        return $this->_passwordTimeout;
    }

    /**
     * @param $value integer Number of days after which user is requred to reset his password after logging in.
     */
    public function setPasswordTimeout($value)
    {
        $this->_passwordTimeout = $value;
    }

    /**
     * @inheritdoc
     */
    public function filterRules($rules = array())
    {
        $behaviorRules = array(
            array('password', 'passwordHasNotExpired', 'except' => 'reset, hybridauth, verifyOTP'),
        );

        return array_merge($rules, $this->applyRuleOptions($behaviorRules));
    }

    public function passwordHasNotExpired()
    {
        if ($this->owner->hasErrors()) {
            return;
        }

        $identity = $this->owner->getIdentity();
        if (!($identity instanceof IPasswordHistoryIdentity)) {
            throw new CException(Yii::t('UsrModule.usr', 'The {class} class must implement the {interface} interface.', array('{class}' => get_class($identity), '{interface}' => 'IPasswordHistoryIdentity')));
        }
        $lastUsed = $identity->getPasswordDate();
        $lastUsedDate = new DateTime($lastUsed);
        $today = new DateTime();
        if ($lastUsed === null || $today->diff($lastUsedDate)->days >= $this->passwordTimeout) {
            if ($lastUsed === null) {
                $this->owner->addError('password', Yii::t('UsrModule.usr', 'This is the first time you login. Current password needs to be changed.'));
            } else {
                $this->owner->addError('password', Yii::t('UsrModule.usr', 'Current password has been used too long and needs to be changed.'));
            }
            $this->owner->scenario = 'reset';

            return false;
        }

        return true;
    }
}
