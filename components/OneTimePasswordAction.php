<?php

/**
 * Called from the updateProfile action, enables or disables one time passwords for two step authentication.
 * When enabling OTP user must verify that he is able to use them successfully.
 */
class OneTimePasswordAction extends CAction
{
	public function run() {
		if (Yii::app()->user->isGuest)
			$this->controller->redirect(array('login'));
		/** @var UsrModule */
		$module = $this->controller->module;
		if ($module->oneTimePasswordRequired)
			$this->controller->redirect(array('profile'));

		$model = new OneTimePasswordForm;
		/** @var IUserIdentity */
		$identity = $model->getIdentity();
		/**
		 * Disable OTP when a secret is set.
		 */
		if ($identity->getOneTimePasswordSecret() !== null) {
			$identity->setOneTimePasswordSecret(null);
			Yii::app()->request->cookies->remove(UsrModule::OTP_COOKIE);
			$this->controller->redirect('profile');
			return;
		}

		$model->setMode($module->oneTimePasswordMode)->setAuthenticator($module->googleAuthenticator);

		/**
		 * When no secret has been set yet, generate a new secret and save it in session.
		 * Do it if it hasn't been done yet.
		 */
		if (($secret=Yii::app()->session[UsrModule::OTP_SECRET_PREFIX.'newSecret']) === null) {
			$secret = Yii::app()->session[UsrModule::OTP_SECRET_PREFIX.'newSecret'] = $module->googleAuthenticator->generateSecret();

			$model->setSecret($secret);
			if ($module->oneTimePasswordMode === UsrModule::OTP_COUNTER) {
				$this->controller->sendEmail($model, 'oneTimePassword');
			}
		}
		$model->setSecret($secret);

		if (isset($_POST['OneTimePasswordForm'])) {
			$model->setAttributes($_POST['OneTimePasswordForm']);
			if ($model->validate()) {
				// save secret
				$identity->setOneTimePasswordSecret($secret);
				Yii::app()->session[UsrModule::OTP_SECRET_PREFIX.'newSecret'] = null;
				// save current code as used
				$identity->setOneTimePassword($model->oneTimePassword, $module->oneTimePasswordMode === UsrModule::OTP_TIME ? floor(time() / 30) : $model->getPreviousCounter() + 1);
				$this->controller->redirect('profile');
			}
		}
		if (YII_DEBUG) {
			$model->oneTimePassword = $module->googleAuthenticator->getCode($secret, $module->oneTimePasswordMode === UsrModule::OTP_TIME ? null : $model->getPreviousCounter());
		}

		if ($module->oneTimePasswordMode === UsrModule::OTP_TIME) {
			$hostInfo = Yii::app()->request->hostInfo;
			$url = $model->getUrl($identity->username, parse_url($hostInfo, PHP_URL_HOST), $secret);
		} else {
			$url = '';
		}

		$this->controller->render('generateOTPSecret', array('model'=>$model, 'url'=>$url));
	}
}
