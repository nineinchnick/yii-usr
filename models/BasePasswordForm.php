<?php

/**
 * BasePasswordForm class.
 * BasePasswordForm is the base class for forms used to set new password.
 */
abstract class BasePasswordForm extends BaseUsrForm
{
	public $newPassword;
	public $newVerify;

	/**
	 * Declares the validation rules.
	 * The rules state that username and password are required,
	 * and password needs to be authenticated.
	 */
	public function rules()
	{
		$defaultStrengthRules = Yii::app()->controller->module->passwordStrengthRules;
		if ($defaultStrengthRules === null) {
			$defaultStrengthRules = array(
				array('newPassword', 'length', 'min' => 8, 'message' => Yii::t('UsrModule.usr', 'New password must contain at least 8 characters.')),
				array('newPassword', 'match', 'pattern' => '/^.*(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).*$/', 'message'	=> Yii::t('UsrModule.usr', 'New password must contain at least one lower and upper case character and a digit.')),
			);
		}
		$rules = array_merge(
			array(
				array('newPassword, newVerify', 'filter', 'filter'=>'trim'),
				array('newPassword, newVerify', 'required'),
				array('newPassword', 'unusedNewPassword'),
			),
			$defaultStrengthRules,
			array(
				array('newVerify', 'compare', 'compareAttribute'=>'newPassword', 'message' => Yii::t('UsrModule.usr', 'Please type the same new password twice to verify it.')),
			)
		);
		return $rules;
	}

	public function rulesAddScenario($rules, $scenario)
	{
		foreach($rules as $key=>$rule) {
			$rules[$key]['on'] = $scenario;
		}
		return $rules;
	}

	/**
	 * Declares attribute labels.
	 */
	public function attributeLabels()
	{
		return array_merge(parent::attributeLabels(), array(
			'newPassword'	=> Yii::t('UsrModule.usr','New password'),
			'newVerify'		=> Yii::t('UsrModule.usr','Verify'),
		));
	}

	abstract public function getIdentity();
	abstract public function resetPassword();

	/**
	 * Checkes if current password hasn't been used before.
	 * This is the 'unusedNewPassword' validator as declared in rules().
	 */
	public function unusedNewPassword()
	{
		if($this->hasErrors()) {
			return;
		}

		$identity = $this->getIdentity();
		if (!($identity instanceof IPasswordHistoryIdentity))
			return true;
		// check if new password is not the same as current one
		if (Yii::app()->user->getId() !== null) {
			$userIdentityClass = $this->userIdentityClass;
			$newIdentity = $userIdentityClass::find(array('id'=>Yii::app()->user->getId()));
			$newIdentity->password = $this->newPassword;
			if ($newIdentity->authenticate()) {
				$this->addError('newPassword',Yii::t('UsrModule.usr','New password must be different than the old one.'));
				return false;
			}
		}
		// check if new password hasn't been used before
		if (($lastUsed = $identity->getPasswordDate($this->newPassword)) !== null) {
			$this->addError('newPassword',Yii::t('UsrModule.usr','New password has been used before, last set on {date}.', array('{date}'=>$lastUsed)));
			return false;
		}
		return true;
	}
}
