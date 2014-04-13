<?php

Yii::import('usr.controllers.UsrController');

class ManagerController extends UsrController
{
	/**
	 * @var string the default layout for the views. Defaults to '//layouts/column2', meaning
	 * using two-column layout. See 'protected/views/layouts/column2.php'.
	 */
	public $layout='//layouts/column2';
	/**
	 * @var array context menu items. This property will be assigned to {@link CMenu::items}.
	 */
	public $menu=array();

	/**
	 * @return array action filters
	 */
	public function filters()
	{
		return array(
			'accessControl',
			'postOnly + delete,verify,activate,disable',
		);
	}

	/**
	 * Specifies the access control rules.
	 * This method is used by the 'accessControl' filter.
	 * @return array access control rules
	 */
	public function accessRules()
	{
		return array(
			array('allow', 'actions'=>array('index'), 'roles'=>array('usr.read')),
			array('allow', 'actions'=>array('update'), 'users'=>array('usr.update')),
			array('allow', 'actions'=>array('delete'), 'roles'=>array('usr.delete')),
			array('allow', 'actions'=>array('verify', 'activate', 'disable'), 'roles'=>array('usr.update.status')),
			array('deny', 'users'=>array('*')),
		);
	}

	/**
	 * Updates a particular model.
	 * If update is successful, the browser will be redirected to the 'index' page.
	 * @param integer $id the ID of the model to be updated
	 */
	public function actionUpdate($id=null)
	{
		if (!Yii::app()->user->checkAccess($id === null ? 'usr.create' : 'usr.update')) {
			throw new CHttpException(403, Yii::t('yii','You are not authorized to perform this action.'));
		}

		/** @var ProfileForm */
		$profileForm = $this->module->createFormModel('ProfileForm', 'register');
		$profileForm->detachBehavior('captcha');
		if ($id !== null) {
			$profileForm->setIdentity($identity=$this->loadModel($id));
			$profileForm->setAttributes($identity->getAttributes());
		}
		/** @var PasswordForm */
		$passwordForm = $this->module->createFormModel('PasswordForm', 'register');

		if(isset($_POST['ajax']) && $_POST['ajax']==='profile-form') {
			echo CActiveForm::validate($profileForm);
			Yii::app()->end();
		}
		/**
		 * @todo Check for detailed auth items
		 */
		$canUpdateAttributes = Yii::app()->user->checkAccess('usr.update.attributes');
		$canUpdatePassword = Yii::app()->user->checkAccess('usr.update.password');
		$canUpdateAuth = Yii::app()->user->checkAccess('usr.update.auth');

		if(isset($_POST['ProfileForm'])) {
			$profileForm->setAttributes($_POST['ProfileForm']);
			if ($profileForm->getIdentity() instanceof IPictureIdentity && !empty($profileForm->pictureUploadRules)) {
				$profileForm->picture = CUploadedFile::getInstance($profileForm, 'picture');
			}
			if ($canUpdatePassword && isset($_POST['PasswordForm']) && isset($_POST['PasswordForm']['newPassword']) && ($p=trim($_POST['PasswordForm']['newPassword']))!=='') {
				$passwordForm->setAttributes($_POST['PasswordForm']);
				$updatePassword = true;
			} else {
				$updatePassword = false;
			}
			if ($profileForm->validate() && (!$updatePassword || $passwordForm->validate())) {
				$trx = Yii::app()->db->beginTransaction();
				$oldEmail = $profileForm->getIdentity()->getEmail();
				if (($canUpdateAttributes && !$profileForm->save()) || ($updatePassword && !$passwordForm->resetPassword($profileForm->getIdentity()))) {
					$trx->rollback();
					Yii::app()->user->setFlash('error', Yii::t('UsrModule.usr', 'Failed to register a new user.').' '.Yii::t('UsrModule.usr', 'Try again or contact the site administrator.'));
				} else {
					if ($canUpdateAuth) {
						$identity = $profileForm->getIdentity();
						$authManager = Yii::app()->authManager;
						$assignedRoles = $id === null ? array() : $authManager->getAuthItems(CAuthItem::TYPE_ROLE, $id);

						if (isset($_POST['roles']) && is_array($_POST['roles'])) {
							foreach($_POST['roles'] as $roleName) {
								if (!isset($assignedRoles[$roleName])) {
									$authManager->assign($roleName, $identity->getId());
								} else {
									unset($assignedRoles[$roleName]);
								}
							}
						}
						foreach($assignedRoles as $roleName=>$role) {
							$authManager->revoke($roleName, $identity->getId());
						}
					}
					$trx->commit();
					if ($this->module->requireVerifiedEmail && $oldEmail != $profileForm->getIdentity()->email) {
						if ($this->sendEmail($profileForm, 'verify')) {
							Yii::app()->user->setFlash('success', Yii::t('UsrModule.usr', 'An email containing further instructions has been sent to the provided email address.'));
						} else {
							Yii::app()->user->setFlash('error', Yii::t('UsrModule.usr', 'Failed to send an email.').' '.Yii::t('UsrModule.usr', 'Try again or contact the site administrator.'));
						}
					}
					if (!Yii::app()->user->hasFlash('success')) {
						Yii::app()->user->setFlash('success', Yii::t('UsrModule.usr', 'User account has been successfully created or updated.'));
					}
					$this->redirect(array('index'));
				}
			}
		}

		$this->render('update', array('id'=>$id, 'profileForm'=>$profileForm, 'passwordForm'=>$passwordForm));
	}

	/**
	 * Deletes a particular model.
	 * If deletion is successful, the browser will be redirected to the 'index' page.
	 * @param integer $id the ID of the model to be deleted
	 */
	public function actionDelete($id)
	{
		if (!$this->loadModel($id)->delete()) {
			throw new CHttpException(409,'User account could not be deleted.');
		}

		// if AJAX request (triggered by deletion via index grid view), we should not redirect the browser
		if(!isset($_GET['ajax']))
			$this->redirect(isset($_POST['returnUrl']) ? $_POST['returnUrl'] : array('index'));
	}

	/**
	 * Toggles email verification status for a particular user.
	 * @param integer $id the ID of the user which email verification status is to be toggled
	 */
	public function actionVerify($id)
	{
		$this->loadModel($id)->toggleStatus(IManagedIdentity::STATUS_EMAIL_VERIFIED);

		if(!isset($_GET['ajax']))
			$this->redirect(isset($_POST['returnUrl']) ? $_POST['returnUrl'] : array('index'));
	}

	/**
	 * Toggles active status for a particular user.
	 * @param integer $id the ID of the user which active status is to be toggled
	 */
	public function actionActivate($id)
	{
		$this->loadModel($id)->toggleStatus(IManagedIdentity::STATUS_IS_ACTIVE);

		if(!isset($_GET['ajax']))
			$this->redirect(isset($_POST['returnUrl']) ? $_POST['returnUrl'] : array('index'));
	}

	/**
	 * Toggles disabled status for a particular user.
	 * @param integer $id the ID of the user which disabled status is to be toggled
	 */
	public function actionDisable($id)
	{
		$this->loadModel($id)->toggleStatus(IManagedIdentity::STATUS_IS_DISABLED);

		if(!isset($_GET['ajax']))
			$this->redirect(isset($_POST['returnUrl']) ? $_POST['returnUrl'] : array('index'));
	}

	/**
	 * Manages all models.
	 */
	public function actionIndex()
	{
		$model = $this->module->createFormModel('SearchForm');
		if (isset($_GET['SearchForm'])) {
			$model->attributes = $_GET['SearchForm'];
			$model->validate();
			$errors = $model->getErrors();
			$model->unsetAttributes(array_keys($errors));
		}

		$this->render('index', array('model'=>$model));
	}

	/**
	 * Returns the data model based on the primary key given in the GET variable.
	 * If the data model is not found, an HTTP exception will be raised.
	 * @param integer $id the ID of the model to be loaded
	 * @return User the loaded model
	 * @throws CHttpException
	 */
	public function loadModel($id)
	{
		$searchForm = $this->module->createFormModel('SearchForm');
		if(($model = $searchForm->getIdentity($id))===null)
			throw new CHttpException(404,'The requested page does not exist.');
		return $model;
	}
}
