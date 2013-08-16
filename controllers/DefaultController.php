<?php

class DefaultController extends CController {
	public function actionIndex() {
		$this->render('index');
	}

	/**
	 * Redirects user either to returnUrl or main page.
	 */ 
	protected function afterLogin() {
		$returnUrlParts = explode('/',Yii::app()->user->returnUrl);
		if(end($returnUrlParts)=='index.php'){
			$url = '/';
		}else{
			$url = Yii::app()->user->returnUrl;
		}
		$this->redirect($url);
	}

	public function actionLogin() {
		if (!Yii::app()->user->isGuest)
			$this->redirect(Yii::app()->user->returnUrl);

		$model=new LoginForm;

		if(isset($_POST['ajax']) && $_POST['ajax']==='login-form') {
			echo CActiveForm::validate($model);
			Yii::app()->end();
		}

		if(isset($_POST['LoginForm'])) {
			$model->setAttributes($_POST['LoginForm']);
			if($model->validate() && $model->passwordIsFresh() && $model->login()) {
				$this->afterLogin();
			}
		}
		$this->render($model->scenario === 'reset' ? 'reset' : 'login', array('model'=>$model));
	}

	public function actionReset() {
		if (!Yii::app()->user->isGuest)
			$this->redirect(Yii::app()->user->returnUrl);

		$model=new LoginForm;
		$model->scenario = 'reset';

		if(isset($_POST['ajax']) && $_POST['ajax']==='login-form') {
			echo CActiveForm::validate($model);
			Yii::app()->end();
		}

		if(isset($_POST['LoginForm'])) {
			$model->setAttributes($_POST['LoginForm']);
			if($model->validate() && $model->resetPassword() && $model->login($model->newPassword)) {
				$this->afterLogin();
			}
		}
		$title = Yii::t('UsrModule.usr', 'Password reset');
		$this->pageTitle = Yii::app()->name.' - '.$title;
		$this->render('reset',array('model'=>$model, 'title'=>$title));
	}

	/**
	 * Logs out the current user and redirect to homepage.
	 */
	public function actionLogout() {
		Yii::app()->user->logout();
		$this->redirect(Yii::app()->homeUrl);
	}

	public function actionRecovery() {
		if (!$this->module->recoveryEnabled) {
			throw new CHttpException(403,Yii::t('UsrModule.usr', 'Password recovery has not been enabled.'));
		}
		if (!Yii::app()->user->isGuest)
			$this->redirect(Yii::app()->user->returnUrl);

		$model=new RecoveryForm;

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
			if ($model->activationKey !== null)
				$model->scenario = 'reset';
			if($model->validate()) {
				if ($model->scenario !== 'reset') {
					if ($this->sendEmail($model, 'recovery')) {
						Yii::app()->user->setFlash('success', Yii::t('UsrModule.usr', 'An email containing further instructions has been sent to email associated with specified user account.'));
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

	public function actionVerify() {
		$model=new RecoveryForm;
		$model->scenario = 'verify';
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

	public function actionRegister() {
		if (!$this->module->registrationEnabled) {
			throw new CHttpException(403,Yii::t('UsrModule.usr', 'Registration has not been enabled.'));
		}
		if (!Yii::app()->user->isGuest)
			$this->redirect(array('profile'));

		$model=new ProfileForm;
		$model->scenario = 'register';

		if(isset($_POST['ajax']) && $_POST['ajax']==='profile-form') {
			echo CActiveForm::validate($model);
			Yii::app()->end();
		}
		if(isset($_POST['ProfileForm'])) {
			$model->setAttributes($_POST['ProfileForm']);
			if($model->validate()) {
				if ($model->save() && $model->resetPassword()) {
					$flashIsSet = false;
					if ($this->module->requireVerifiedEmail) {
						if ($this->sendEmail($model, 'verify')) {
							Yii::app()->user->setFlash('success', Yii::t('UsrModule.usr', 'An email containing further instructions has been sent to provided email address.'));
							$flashIsSet = true;
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
						if (!$flashIsSet)
							Yii::app()->user->setFlash('success', Yii::t('UsrModule.usr', 'Please wait for the account to be activated. A notification will be send to provided email address.'));
						$this->redirect(array('login'));
					}
				} else {
					Yii::app()->user->setFlash('error', Yii::t('UsrModule.usr', 'Failed to register a new user.').' '.Yii::t('UsrModule.usr', 'Try again or contact the site administrator.'));
				}
			}
		}
		$this->render('updateProfile',array('model'=>$model));
	}

	public function actionProfile($update=false) {
		if (Yii::app()->user->isGuest)
			$this->redirect(array('login'));

		$model=new ProfileForm;
		$model->setAttributes($model->getIdentity()->getAttributes());

		if(isset($_POST['ajax']) && $_POST['ajax']==='recovery-form') {
			echo CActiveForm::validate($model);
			Yii::app()->end();
		}
		if(isset($_POST['ProfileForm'])) {
			$model->setAttributes($_POST['ProfileForm']);
			if($model->validate()) {
				$oldEmail = $model->getIdentity()->getEmail();
				if ($model->save() && $model->resetPassword()) {
					$flashIsSet = false;
					if ($this->module->requireVerifiedEmail && $oldEmail != $model->email) {
						if ($this->sendEmail($model, 'verify')) {
							Yii::app()->user->setFlash('success', Yii::t('UsrModule.usr', 'An email containing further instructions has been sent to provided email address.'));
							$flashIsSet = true;
						} else {
							Yii::app()->user->setFlash('error', Yii::t('UsrModule.usr', 'Failed to send an email.').' '.Yii::t('UsrModule.usr', 'Try again or contact the site administrator.'));
						}
					}
					if (!$flashIsSet)
						Yii::app()->user->setFlash('success', Yii::t('UsrModule.usr', 'Changes have been saved successfully.'));
					$this->redirect(array('profile'));
				} else {
					Yii::app()->user->setFlash('error', Yii::t('UsrModule.usr', 'Failed to update profile.').' '.Yii::t('UsrModule.usr', 'Try again or contact the site administrator.'));
				}
			}
		}
		if ($update) {
			$this->render('updateProfile',array('model'=>$model));
		} else {
			$this->render('viewProfile',array('model'=>$model));
		}
	}

	public function actionPassword() {
		$diceware = new Diceware;
		$password = $diceware->get_phrase($this->module->dicewareLength, $this->module->dicewareExtraDigit, $this->module->dicewareExtraChar);
		echo json_encode($password);
	}

	/**
	 * Sends out an email containing instructions and link to the email verification
	 * or password recovery page, containing an activation key.
	 * @param CFormModel $model
	 * @param strign $mode 'recovery' or 'verify'
	 * @return boolean if sending the email succeeded
	 */
	protected function sendEmail(CFormModel $model, $mode) {
		$mail = $this->module->mailer;
		$mail->AddAddress($model->getIdentity()->getEmail(), $model->getIdentity()->getName());
		if ($mode == 'recovery') {
			$mail->Subject = Yii::t('UsrModule.usr', 'Password recovery');
		} else {
			$mail->Subject = Yii::t('UsrModule.usr', 'Email address verification');
		}
		$params = array(
			'siteUrl' => $this->createAbsoluteUrl('/'), 
			'actionUrl' => $this->createAbsoluteUrl('default/'.$mode, array(
				'activationKey'=>$model->getIdentity()->getActivationKey(),
				'username'=>$model->getIdentity()->getName(),
			)),
		);
		$body = $this->renderPartial($mail->getPathViews().'.'.$mode, $params, true);
		$full = $this->renderPartial($mail->getPathLayouts().'.email', array('content'=>$body), true);
		$mail->MsgHTML($full);
		if ($mail->Send()) {
			return true;
		} else {
			Yii::log($mail->ErrorInfo, 'error');
			return false;
		}
	}
}
