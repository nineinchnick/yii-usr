<?php

Yii::import('usr.controllers.UsrController');

/**
 * The default controller providing all basic actions.
 * @author Jan Was <jwas@nets.com.pl>
 */
class DefaultController extends UsrController
{
	public function actions()
	{
		$actions = array();
		if ($this->module->captcha !== null) {
			// captcha action renders the CAPTCHA image displayed on the register and recovery page
			$actions['captcha'] = array(
				'class'=>'CCaptchaAction',
				'backColor'=>0xFFFFFF,
				'testLimit'=>0,
			);
		}
		if ($this->module->dicewareEnabled) {
			// DicewareAction generates a random passphrase
			$actions['password'] = array(
				'class'=>'DicewareAction',
				'length'=>$this->module->dicewareLength,
				'extraDigit'=>$this->module->dicewareExtraDigit,
				'extraChar'=>$this->module->dicewareExtraChar,
			);
		}
		if ($this->module->oneTimePasswordMode != UsrModule::OTP_NONE) {
			// OneTimePasswordAction allows toggling two step auth in user profile
			$actions['toggleOneTimePassword'] = array(
				'class'=>'OneTimePasswordAction',
			);
		}
		return $actions;
	}

	public function actionIndex()
	{
		if (Yii::app()->user->isGuest)
			$this->redirect(array('login'));
		else
			$this->redirect(array('profile'));
	}

	/**
	 * Redirects user either to returnUrl or main page.
	 */ 
	protected function afterLogin()
	{
		$returnUrlParts = explode('/',Yii::app()->user->returnUrl);
		if(end($returnUrlParts)=='index.php'){
			$url = '/';
		}else{
			$url = Yii::app()->user->returnUrl;
		}
		$this->redirect($url);
	}

	/**
	 * Performs user login, expired password reset or one time password verification.
	 * @param string $scenario
	 * @return string
	 */
	public function actionLogin($scenario = null)
	{
		if (!Yii::app()->user->isGuest)
			$this->redirect(Yii::app()->user->returnUrl);

		$model = $this->module->createFormModel('LoginForm');
		if ($scenario !== null && in_array($scenario, array('reset', 'verifyOTP'))) {
			$model->scenario = $scenario;
		}

		if(isset($_POST['ajax']) && $_POST['ajax']==='login-form') {
			echo CActiveForm::validate($model);
			Yii::app()->end();
		}

		if(isset($_POST['LoginForm'])) {
			$model->setAttributes($_POST['LoginForm']);
			if($model->validate()) {
				if (($model->scenario !== 'reset' || $model->resetPassword()) && $model->login($this->module->rememberMeDuration)) {
					$this->afterLogin();
				} else {
					Yii::app()->user->setFlash('error', Yii::t('UsrModule.usr', 'Failed to change password or log in using new password.'));
				}
			}
		}
		switch($model->scenario) {
		default: $view = 'login'; break;
		case 'reset': $view = 'reset'; break;
		case 'verifyOTP': $view = 'verifyOTP'; break;
		}
		$this->render($view, array('model'=>$model));
	}

	/**
	 * Logs out the current user and redirect to homepage.
	 */
	public function actionLogout()
	{
		Yii::app()->user->logout();
		$this->redirect(Yii::app()->homeUrl);
	}

	/**
	 * Processes a request for password recovery email or resetting the password. 
	 * @return string
	 */
	public function actionRecovery()
	{
		if (!$this->module->recoveryEnabled) {
			throw new CHttpException(403,Yii::t('UsrModule.usr', 'Password recovery has not been enabled.'));
		}
		if (!Yii::app()->user->isGuest)
			$this->redirect(Yii::app()->user->returnUrl);

		/** @var RecoveryForm */
		$model = $this->module->createFormModel('RecoveryForm');

		if(isset($_POST['ajax']) && $_POST['ajax']==='recovery-form') {
			echo CActiveForm::validate($model);
			Yii::app()->end();
		}

		if (isset($_GET['activationKey'])) {
			$model->scenario = 'reset';
			$model->setAttributes($_GET);
		}
		if(isset($_POST['RecoveryForm'])) {
			$model->setAttributes($_POST['RecoveryForm']);
			/**
			 * If the activation key is missing that means the user is requesting a recovery email.
			 */
			if ($model->activationKey !== null)
				$model->scenario = 'reset';
			if($model->validate()) {
				if ($model->scenario !== 'reset') {
					/** 
					 * Send email appropriate to the activation status
					 * (ie if verification is required, that must happen
					 * before password recovery). Also allows re-sending of
					 * verification emails)
					 */
					if ($this->sendEmail($model, ($model->identity->isActive()) ? 'recovery' : 'verify')) {
						Yii::app()->user->setFlash('success', Yii::t('UsrModule.usr', 'An email containing further instructions has been sent to the email address associated with the specified user account.'));
					} else {
						Yii::app()->user->setFlash('error', Yii::t('UsrModule.usr', 'Failed to send an email.').' '.Yii::t('UsrModule.usr', 'Try again or contact the site administrator.'));
					}
				} else {
					$model->getIdentity()->verifyEmail();
					if ($model->resetPassword() && $model->login()) {
						$this->afterLogin();
					} else {
						Yii::app()->user->setFlash('error', Yii::t('UsrModule.usr', 'Failed to change password or log in using new password.'));
					}
				}
				$this->redirect(array('recovery'));
			}
		}
		$this->render('recovery',array('model'=>$model));
	}

	/**
	 * Processes email verification.
	 * @return string
	 */
	public function actionVerify()
	{
		/** @var RecoveryForm */
		$model = $this->module->createFormModel('RecoveryForm', 'verify');
		if (!isset($_GET['activationKey'])) {
			throw new CHttpException(400,Yii::t('UsrModule.usr', 'Activation key is missing.'));
		}
		$model->setAttributes($_GET);
		if($model->validate() && $model->getIdentity()->verifyEmail()) {
			Yii::app()->user->setFlash('success', Yii::t('UsrModule.usr', 'Your email address has been successfully verified.'));
		} else {
			Yii::app()->user->setFlash('error', Yii::t('UsrModule.usr', 'Failed to verify your email address.'));
		}
		$this->redirect(array(Yii::app()->user->isGuest ? 'login' : 'profile'));
	}

	/**
	 * Performs user sign-up.
	 * @return string
	 */
	public function actionRegister()
	{
		if (!$this->module->registrationEnabled) {
			throw new CHttpException(403,Yii::t('UsrModule.usr', 'Registration has not been enabled.'));
		}
		if (!Yii::app()->user->isGuest)
			$this->redirect(array('profile'));

		/** @var ProfileForm */
		$model = $this->module->createFormModel('ProfileForm', 'register');
		/** @var PasswordForm */
		$passwordForm = $this->module->createFormModel('PasswordForm', 'register');

		if(isset($_POST['ajax']) && $_POST['ajax']==='profile-form') {
			echo CActiveForm::validate(array($model, $passwordForm));
			Yii::app()->end();
		}
		if(isset($_POST['ProfileForm'])) {
			$model->setAttributes($_POST['ProfileForm']);
			if(isset($_POST['PasswordForm']))
				$passwordForm->setAttributes($_POST['PasswordForm']);
			if ($model->validate() && $passwordForm->validate()) {
				$trx = Yii::app()->db->beginTransaction();
				if (!$model->save() || !$passwordForm->resetPassword($model->getIdentity())) {
					$trx->rollback();
					Yii::app()->user->setFlash('error', Yii::t('UsrModule.usr', 'Failed to register a new user.').' '.Yii::t('UsrModule.usr', 'Try again or contact the site administrator.'));
				} else {
					$trx->commit();
					if ($this->module->requireVerifiedEmail) {
						if ($this->sendEmail($model, 'verify')) {
							Yii::app()->user->setFlash('success', Yii::t('UsrModule.usr', 'An email containing further instructions has been sent to the provided email address.'));
						} else {
							Yii::app()->user->setFlash('error', Yii::t('UsrModule.usr', 'Failed to send an email.').' '.Yii::t('UsrModule.usr', 'Try again or contact the site administrator.'));
						}
					}
					if ($model->getIdentity()->isActive()) {
						if ($model->login()) {
							$this->afterLogin();
						} else {
							Yii::app()->user->setFlash('error', Yii::t('UsrModule.usr', 'Failed to log in.').' '.Yii::t('UsrModule.usr', 'Try again or contact the site administrator.'));
						}
					} else {
						if (!Yii::app()->user->hasFlash('success'))
							Yii::app()->user->setFlash('success', Yii::t('UsrModule.usr', 'Please wait for the account to be activated. A notification will be send to provided email address.'));
						$this->redirect(array('login'));
					}
				}
			}
		}
		$this->render('updateProfile',array('model'=>$model, 'passwordForm'=>$passwordForm));
	}

	/**
	 * Allows users to view or update their profile.
	 * @param boolean $update
	 * @return string
	 */
	public function actionProfile($update=false)
	{
		if (Yii::app()->user->isGuest)
			$this->redirect(array('login'));

		/** @var ProfileForm */
		$model = $this->module->createFormModel('ProfileForm');
		$model->setAttributes($model->getIdentity()->getAttributes());
		/** @var PasswordForm */
		$passwordForm = $this->module->createFormModel('PasswordForm');

		if(isset($_POST['ajax']) && $_POST['ajax']==='profile-form') {
			$models = array($model);
			if(isset($_POST['PasswordForm']) && trim($_POST['PasswordForm']['newPassword']) !== '') {
				$models[] = $passwordForm;
			}
			echo CActiveForm::validate($models);
			Yii::app()->end();
		}
		$flashes = array('success'=>array(), 'error'=>array());
		/**
		 * Only try to set new password if it has been specified in the form.
		 * The current password could have been used to authorize other changes.
		 */
		if(isset($_POST['PasswordForm']) && trim($_POST['PasswordForm']['newPassword']) !== '') {
			$passwordForm->setAttributes($_POST['PasswordForm']);
			if ($passwordForm->validate()) {
				if ($passwordForm->resetPassword($model->getIdentity())) {
					$flashes['success'][] = Yii::t('UsrModule.usr', 'Changes have been saved successfully.');
				} else {
					$flashes['error'][] = Yii::t('UsrModule.usr', 'Failed to change password.');
				}
			}
		}
		if(isset($_POST['ProfileForm']) && empty($flashes['error'])) {
			$model->setAttributes($_POST['ProfileForm']);
			if($model->validate()) {
				$oldEmail = $model->getIdentity()->getEmail();
				if ($model->save()) {
					if ($this->module->requireVerifiedEmail && $oldEmail != $model->email) {
						if ($this->sendEmail($model, 'verify')) {
							$flashes['success'][] = Yii::t('UsrModule.usr', 'An email containing further instructions has been sent to the provided email address.');
						} else {
							$flashes['error'][] = Yii::t('UsrModule.usr', 'Failed to send an email.').' '.Yii::t('UsrModule.usr', 'Try again or contact the site administrator.');
						}
					}
					$flashes['success'][] = Yii::t('UsrModule.usr', 'Changes have been saved successfully.');
					if (!empty($flashes['success']))
						Yii::app()->user->setFlash('success', implode('<br/>',$flashes['success']));
					if (!empty($flashes['error']))
						Yii::app()->user->setFlash('error', implode('<br/>',$flashes['error']));
					$this->redirect(array('profile'));
				} else {
					$flashes['error'][] = Yii::t('UsrModule.usr', 'Failed to update profile.').' '.Yii::t('UsrModule.usr', 'Try again or contact the site administrator.');
				}
			}
		}
		if (!empty($flashes['success']))
			Yii::app()->user->setFlash('success', implode('<br/>',$flashes['success']));
		if (!empty($flashes['error']))
			Yii::app()->user->setFlash('error', implode('<br/>',$flashes['error']));
		if ($update) {
			$this->render('updateProfile',array('model'=>$model, 'passwordForm'=>$passwordForm));
		} else {
			$this->render('viewProfile',array('model'=>$model));
		}
	}
}
