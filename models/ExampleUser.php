<?php

/**
 * This is the model class for the table "{{user}}".
 *
 * Columns in table "{{user}}" available as properties of the model.
 *
 * @property integer $id
 * @property string $username
 * @property string $password
 * @property string $email
 * @property string $firstname
 * @property string $lastname
 * @property string $activation_key
 * @property datetime $created_on
 * @property datetime $last_edit_on
 * @property datetime $last_visit_on
 * @property datetime $password_set_on
 * @property boolean $is_active
 * @property boolean $is_disabled
 */
class ExampleUser extends CActiveRecord {
	public static function model($className=__CLASS__) {
		return parent::model($className);
	}

	public function tableName() {
		return '{{user}}';
	}

	public function rules() {
		// password is unsafe on purpose, assign it manually after hashing only if not empty
		return array(
			array('username, email, firstname, lastname, is_active, is_disabled', 'filter', 'filter' => 'trim'),
			array('activation_key, created_on, last_edit_on, last_visit_on, password_set_on', 'filter', 'filter' => 'trim', 'on' => 'search'),
			array('username, email, firstname, lastname, is_active, is_disabled', 'default', 'setOnEmpty' => true, 'value' => null),
			array('activation_key, created_on, last_edit_on, last_visit_on, password_set_on', 'default', 'setOnEmpty' => true, 'value' => null, 'on' => 'search'),
			array('username, email, is_active, is_disabled', 'required', 'except' => 'search'),
			array('created_on, last_edit_on, last_visit_on, password_set_on', 'date', 'format' => array('yyyy-MM-dd', 'yyyy-MM-dd HH:mm', 'yyyy-MM-dd HH:mm:ss'), 'on' => 'search'),
			array('activation_key', 'length', 'max'=>128, 'on' => 'search'),
			array('is_active, is_disabled', 'boolean'),
			array('username, email', 'unique', 'except' => 'search'),
		);
	}

	public function relations() {
		return array(
		);
	}

	public function attributeLabels() {
		return array(
			'id' => Yii::t('models', 'ID'),
			'username' => Yii::t('models', 'Username'),
			'password' => Yii::t('models', 'Password'),
			'email' => Yii::t('models', 'Email'),
			'firstname' => Yii::t('models', 'Firstname'),
			'lastname' => Yii::t('models', 'Lastname'),
			'activation_key' => Yii::t('models', 'Activation Key'),
			'created_on' => Yii::t('models', 'Created On'),
			'last_edit_on' => Yii::t('models', 'Last Edit On'),
			'last_visit_on' => Yii::t('models', 'Last Visit On'),
			'password_set_on' => Yii::t('models', 'Password Set On'),
			'is_active' => Yii::t('models', 'Is Active'),
			'is_disabled' => Yii::t('models', 'Is Disabled'),
		);
	}

	protected function beforeSave() {
		if ($this->isNewRecord) {
			$this->created_on = date('Y-m-d H:i:s');
		} else {
			$this->last_edited_on = date('Y-m-d H:i:s');
		}
		return parent::beforeSave();
	}

	public static function hashPassword($password) {
		require(Yii::getPathOfAlias('usr.extensions').'password.php');
		return password_hash($password);
	}

	public function verifyPassword($password) {
		require(Yii::getPathOfAlias('usr.extensions').'password.php');
		return $this->password !== null && password_verify($password, $this->password);
	}
}
