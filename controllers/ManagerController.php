<?php

class ManagerController extends Controller
{
	/**
	 * @var string the default layout for the views. Defaults to '//layouts/column2', meaning
	 * using two-column layout. See 'protected/views/layouts/column2.php'.
	 */
	public $layout='//layouts/column2';

	/**
	 * @return array action filters
	 */
	public function filters()
	{
		return array(
			'accessControl', // perform access control for CRUD operations
			'postOnly + delete', // we only allow deletion via POST request
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
			array('allow', 'actions'=>array('create'), 'roles'=>array('usr.create')),
			array('allow', 'actions'=>array('update'), 'roles'=>array('usr.update')),
			array('allow', 'actions'=>array('delete'), 'roles'=>array('usr.delete')),
			array('allow', 'actions'=>array('verify', 'activate', 'disable'), 'roles'=>array('usr.update.status')),
			array('deny', 'users'=>array('*')),
		);
	}

	/**
	 * Creates a new model.
	 * If creation is successful, the browser will be redirected to the 'index' page.
	 */
	public function actionCreate()
	{
		$model=new User;

		// Uncomment the following line if AJAX validation is needed
		// $this->performAjaxValidation($model);

		if(isset($_POST['User']))
		{
			$model->attributes=$_POST['User'];
			if($model->save())
				$this->redirect(array('index'));
		}

		$this->render('create',array(
			'model'=>$model,
		));
	}

	/**
	 * Updates a particular model.
	 * If update is successful, the browser will be redirected to the 'index' page.
	 * @param integer $id the ID of the model to be updated
	 */
	public function actionUpdate($id)
	{
		$model=$this->loadModel($id);

		// Uncomment the following line if AJAX validation is needed
		// $this->performAjaxValidation($model);

		if(isset($_POST['User']))
		{
			$model->attributes=$_POST['User'];
			if($model->save())
				$this->redirect(array('index'));
		}

		$this->render('update',array(
			'model'=>$model,
		));
	}

	/**
	 * Deletes a particular model.
	 * If deletion is successful, the browser will be redirected to the 'index' page.
	 * @param integer $id the ID of the model to be deleted
	 */
	public function actionDelete($id)
	{
		$this->loadModel($id)->delete();

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
		$identity = $this->loadModel($id);
		if ($identity->isVerified()) {
			throw new CHttpException(500, 'Cannot unverify email.');
		} else {
			$identity->verifyEmail($this->module->requireVerifiedEmail);
		}

		if(!isset($_GET['ajax']))
			$this->redirect(isset($_POST['returnUrl']) ? $_POST['returnUrl'] : array('index'));
	}

	/**
	 * Toggles active status for a particular user.
	 * @param integer $id the ID of the user which active status is to be toggled
	 */
	public function actionActivate($id)
	{
		$identity = $this->loadModel($id);
		if ($identity->isActive()) {
			throw new CHttpException(500, 'Cannot deactivate.');
		} else {
			throw new CHttpException(500, 'Cannot activate.');
		}

		if(!isset($_GET['ajax']))
			$this->redirect(isset($_POST['returnUrl']) ? $_POST['returnUrl'] : array('index'));
	}

	/**
	 * Toggles disabled status for a particular user.
	 * @param integer $id the ID of the user which disabled status is to be toggled
	 */
	public function actionDisable($id)
	{
		$identity = $this->loadModel($id);
		if ($identity->isDisabled()) {
			throw new CHttpException(500, 'Cannot enable.');
		} else {
			throw new CHttpException(500, 'Cannot disable.');
		}

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
		$searchForm->id = $id;
		if(($model = $searchForm->getIdentity())===null)
			throw new CHttpException(404,'The requested page does not exist.');
		return $model;
	}

	/**
	 * Performs the AJAX validation.
	 * @param User $model the model to be validated
	 */
	protected function performAjaxValidation($model)
	{
		if(isset($_POST['ajax']) && $_POST['ajax']==='user-form')
		{
			echo CActiveForm::validate($model);
			Yii::app()->end();
		}
	}
}
