<?php

require(Yii::getPathOfAlias('usr.extensions').DIRECTORY_SEPARATOR.'password.php');

/**
 * This is the model class for table "{{user_used_passwords}}".
 *
 * The followings are the available columns in table '{{user_used_passwords}}':
 * @property integer $id
 * @property integer $user_id
 * @property string $password
 * @property string $set_on
 *
 * The followings are the available model relations:
 * @property User $user
 */
abstract class ExampleUserUsedPassword extends CActiveRecord
{
	/**
	 * @inheritdoc
	 */
	public function tableName()
	{
		return '{{user_used_passwords}}';
	}

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return array(
		);
	}

	/**
	 * @inheritdoc
	 */
	public function relations()
	{
		return array(
			'user' => array(self::BELONGS_TO, 'User', 'user_id'),
		);
	}

	/**
	 * @inheritdoc
	 */
	public function attributeLabels()
	{
		return array(
			'id' => Yii::t('models', 'ID'),
			'user_id' => Yii::t('models', 'User'),
			'password' => Yii::t('models', 'Password'),
			'set_on' => Yii::t('models', 'Password Set On'),
		);
	}

	/**
	 * @param string $className active record class name.
	 * @return UserUsedPassword the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}

	/**
	 * @param string $password password to validate
	 * @return bool if password provided is valid for saved one
	 */
	public function verifyPassword($password)
	{
		return $this->password !== null && password_verify($password, $this->password);
	}
}
