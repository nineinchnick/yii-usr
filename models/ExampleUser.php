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
 * @property datetime $updated_on
 * @property datetime $last_visit_on
 * @property datetime $password_set_on
 * @property boolean $email_verified
 * @property boolean $is_active
 * @property boolean $is_disabled
 */
abstract class ExampleUser extends CActiveRecord {
{
	public function tableName()
	{
		return '{{user}}';
	}

	public function rules()
	{
		// password is unsafe on purpose, assign it manually after hashing only if not empty
		return array(
			array('username, email, firstname, lastname, is_active, is_disabled', 'filter', 'filter' => 'trim'),
			array('activation_key, created_on, updated_on, last_visit_on, password_set_on, email_verified', 'filter', 'filter' => 'trim', 'on' => 'search'),
			array('username, email, firstname, lastname, is_active, is_disabled', 'default', 'setOnEmpty' => true, 'value' => null),
			array('activation_key, created_on, updated_on, last_visit_on, password_set_on, email_verified', 'default', 'setOnEmpty' => true, 'value' => null, 'on' => 'search'),
			array('username, email, is_active, is_disabled, email_verified', 'required', 'except' => 'search'),
			array('created_on, updated_on, last_visit_on, password_set_on', 'date', 'format' => array('yyyy-MM-dd', 'yyyy-MM-dd HH:mm', 'yyyy-MM-dd HH:mm:ss'), 'on' => 'search'),
			array('activation_key', 'length', 'max'=>128, 'on' => 'search'),
			array('is_active, is_disabled, email_verified', 'boolean'),
			array('username, email', 'unique', 'except' => 'search'),
		);
	}

	public function relations()
	{
		return array(
		);
	}

	public function attributeLabels()
	{
		return array(
			'id' => Yii::t('models', 'ID'),
			'username' => Yii::t('models', 'Username'),
			'password' => Yii::t('models', 'Password'),
			'email' => Yii::t('models', 'Email'),
			'firstname' => Yii::t('models', 'Firstname'),
			'lastname' => Yii::t('models', 'Lastname'),
			'activation_key' => Yii::t('models', 'Activation Key'),
			'created_on' => Yii::t('models', 'Created On'),
			'updated_on' => Yii::t('models', 'Updated On'),
			'last_visit_on' => Yii::t('models', 'Last Visit On'),
			'password_set_on' => Yii::t('models', 'Password Set On'),
			'email_verified' => Yii::t('models', 'Email Verified'),
			'is_active' => Yii::t('models', 'Is Active'),
			'is_disabled' => Yii::t('models', 'Is Disabled'),
		);
	}

	public function search()
	{
		$criteria=new CDbCriteria;

		$criteria->compare('id',$this->id);
		$criteria->compare('username',$this->username,true);
		//$criteria->compare('password',$this->password,true);
		$criteria->compare('email',$this->email,true);
		$criteria->compare('firstname',$this->firstname,true);
		$criteria->compare('lastname',$this->lastname,true);
		//$criteria->compare('activation_key',$this->activation_key,true);
		$criteria->compare('created_on',$this->created_on,true);
		$criteria->compare('updated_on',$this->updated_on,true);
		$criteria->compare('last_visit_on',$this->last_visit_on,true);
		$criteria->compare('password_set_on',$this->password_set_on,true);
		$criteria->compare('email_verified',$this->email_verified);
		$criteria->compare('is_active',$this->is_active);
		$criteria->compare('is_disabled',$this->is_disabled);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}

	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}

	protected function beforeSave()
	{
		if ($this->isNewRecord) {
			$this->created_on = date('Y-m-d H:i:s');
		} else {
			$this->updated_on = date('Y-m-d H:i:s');
		}
		return parent::beforeSave();
	}

	protected function afterSave() {
		Yii::import('nfy.models.NfyChannels');
		foreach(NfyChannels::model()->findAll() as $channel) {
			$channel->unsubscribe($this->id);
			$channel->subscribe($this->id, 'db');
		}
	}

	public static function hashPassword($password)
	{
		require(Yii::getPathOfAlias('usr.extensions').DIRECTORY_SEPARATOR.'password.php');
		return password_hash($password, PASSWORD_DEFAULT);
	}

	public function verifyPassword($password)
	{
		require(Yii::getPathOfAlias('usr.extensions').DIRECTORY_SEPARATOR.'password.php');
		return $this->password !== null && password_verify($password, $this->password);
	}
}
