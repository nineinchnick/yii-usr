<?php

/**
 * This is the model class for table "{{user_profile_pictures}}".
 *
 * The followings are the available columns in table '{{user_profile_pictures}}':
 * @property integer $id
 * @property integer $user_id
 * @property integer $original_picture_id
 * @property string $filename
 * @property integer $width
 * @property integer $height
 * @property string $mimetype
 * @property string $created_on
 * @property string $contents
 *
 * The followings are the available model relations:
 * @property UserProfilePicture $originalPicture
 * @property UserProfilePicture[] $thumbnails
 * @property Users $user
 */
abstract class ExampleUserProfilePicture extends CActiveRecord
{
	/**
	 * @inheritdoc
	 */
	public function tableName()
	{
		return '{{user_profile_pictures}}';
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
			'originalPicture' => array(self::BELONGS_TO, 'UserProfilePicture', 'original_picture_id'),
			'thumbnails' => array(self::HAS_MANY, 'UserProfilePicture', 'original_picture_id'),
			'user' => array(self::BELONGS_TO, 'Users', 'user_id'),
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
			'original_picture_id' => Yii::t('models', 'Original Picture'),
			'filename' => Yii::t('models', 'Filename'),
			'width' => Yii::t('models', 'Width'),
			'height' => Yii::t('models', 'Height'),
			'mimetype' => Yii::t('models', 'Mimetype'),
			'created_on' => Yii::t('models', 'Created On'),
			'contents' => Yii::t('models', 'Contents'),
		);
	}

	/**
	 * @param string $className active record class name.
	 * @return UserProfilePicture the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}

	protected function beforeSave()
	{
		if ($this->isNewRecord) {
			$this->created_on = date('Y-m-d H:i:s');
		}
		return parent::beforeSave();
	}
}
