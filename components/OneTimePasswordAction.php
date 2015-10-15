<?php

/**
 * Called from the updateProfile action, enables or disables one time passwords for two step authentication.
 * When enabling OTP user must verify that he is able to use them successfully.
 */
class OneTimePasswordAction extends CAction
{
    /**
     * @var array Same configuration as set for @see OneTimePasswordFormBehavior.
     */
    public $configuration;

    public function run()
    {
        if (Yii::app()->user->isGuest) {
            $this->controller->redirect(array('login'));
        }
        $this->configuration = array_merge(array(
            'authenticator' => null,
            'mode'          => null,
            'required'      => null,
            'timeout'       => null,
        ), $this->configuration);
        if ($this->configuration['required']) {
            $this->controller->redirect(array('profile'));
        }

        $model = new OneTimePasswordForm();
        /** @var IUserIdentity */
        $identity = $model->getIdentity();
        /**
         * Disable OTP when a secret is set.
         */
        if ($identity->getOneTimePasswordSecret() !== null) {
            $identity->setOneTimePasswordSecret(null);
            Yii::app()->request->cookies->remove(OneTimePasswordFormBehavior::OTP_COOKIE);
            $this->controller->redirect('profile');

            return;
        }

        $model->setMode($this->configuration['mode'])->setAuthenticator($this->configuration['authenticator']);

        /**
         * When no secret has been set yet, generate a new secret and save it in session.
         * Do it if it hasn't been done yet.
         */
        if (($secret = Yii::app()->session[OneTimePasswordFormBehavior::OTP_SECRET_PREFIX.'newSecret']) === null) {
            $secret = Yii::app()->session[OneTimePasswordFormBehavior::OTP_SECRET_PREFIX.'newSecret'] = $this->configuration['authenticator']->generateSecret();

            $model->setSecret($secret);
            if ($this->configuration['mode'] === OneTimePasswordFormBehavior::OTP_COUNTER) {
                $this->controller->sendEmail($model, 'oneTimePassword');
            }
        }
        $model->setSecret($secret);

        if (isset($_POST['OneTimePasswordForm'])) {
            $model->setAttributes($_POST['OneTimePasswordForm']);
            if ($model->validate()) {
                // save secret
                $identity->setOneTimePasswordSecret($secret);
                Yii::app()->session[OneTimePasswordFormBehavior::OTP_SECRET_PREFIX.'newSecret'] = null;
                // save current code as used
                $identity->setOneTimePassword($model->oneTimePassword, $this->configuration['mode'] === OneTimePasswordFormBehavior::OTP_TIME ? floor(time() / 30) : $model->getPreviousCounter() + 1);
                $this->controller->redirect('profile');
            }
        }
        if (YII_DEBUG) {
            $model->oneTimePassword = $this->configuration['authenticator']->getCode($secret, $this->configuration['mode'] === OneTimePasswordFormBehavior::OTP_TIME ? null : $model->getPreviousCounter());
        }

        if ($this->configuration['mode'] === OneTimePasswordFormBehavior::OTP_TIME) {
            $hostInfo = Yii::app()->request->hostInfo;
            $url = $model->getUrl($identity->username, parse_url($hostInfo, PHP_URL_HOST), $secret);
        } else {
            $url = '';
        }

        $this->controller->render('generateOTPSecret', array(
            'model' => $model,
            'url'   => $url,
            'mode'  => $this->configuration['mode'],
        ));
    }
}
