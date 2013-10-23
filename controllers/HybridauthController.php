<?php

class HybridauthController extends CController
{
	public function actionIndex()
	{
		$this->redirect('login');
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

	public function actionLogin()
	{
		$model = new HybridauthForm;
		if (isset($_GET['HybridauthForm'])) {
			$model->setAttributes($_GET['HybridauthForm']);
			$model->scenario = strtolower($model->provider);
			$model->setValidProviders($this->module->hybridauthProviders);
			$model->setHybridAuth($this->module->getHybridAuth());

			if($model->validate()) {
				if ($model->login()) {
					$this->afterLogin();
				} elseif (($adapter=$model->getHybridAuthAdapter()) !== null) {
					if (!$this->module->registrationEnabled) {
						Yii::app()->user->setFlash('error', Yii::t('UsrModule.usr', 'Registration has not been enabled.'));
						$this->redirect('login');
					}
					if ($model->register()) {
						$this->afterLogin();
					} else {
						Yii::app()->user->setFlash('error', Yii::t('UsrModule.usr', 'Failed to save user data locally.'));
						$this->redirect('login');
					}
				} else {
					Yii::app()->user->setFlash('error', Yii::t('UsrModule.usr', 'Failed to log in using {provider}.', array('{provider}'=>$model->provider)));
					$this->redirect('login');
				}
			}
		}
		$this->render('login', array('model'=>$model));
	}

	public function actionCallback()
	{
		require dirname(__FILE__) . '/../extensions/Hybrid/Endpoint.php';
		Hybrid_Endpoint::process();
	}
}
