<?php

/**
 * This is the model class for table "{{user_login_attempts}}".
 *
 * The followings are the available columns in table '{{user_login_attempts}}':
 * @property integer $id
 * @property integer $user_id
 * @property string $performed_on
 * @property boolean $is_successful
 * @property string $session_id
 * @property integer $ipv4
 * @property string $user_agent
 *
 * The followings are the available model relations:
 * @property User $user
 */
abstract class ExampleUserLoginAttempt extends CActiveRecord
{
	/**
	 * @inheritdoc
	 */
	public function tableName()
	{
		return '{{user_login_attempts}}';
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
			'performed_on' => Yii::t('models', 'Performed On'),
			'is_successful' => Yii::t('models', 'Is Successful'),
			'session_id' => Yii::t('models', 'Session ID'),
			'ipv4' => Yii::t('models', 'IPv4'),
			'user_agent' => Yii::t('models', 'User Agent'),
		);
	}

	/**
	 * @param string $className active record class name.
	 * @return UserLoginAttempt the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}

    protected function beforeSave()
    {
        if ($this->isNewRecord) {
            /** @var CHttpRequest */
            $request = Yii::app()->request;
            $this->performed_on = date('Y-m-d H:i:s');
            $this->session_id = Yii::app()->session->sessionID;
            $this->ipv4 = $request->userHostAddress;
            $this->user_agent = $request->userAgent;
        }
        return parent::beforeSave();
    }
}
