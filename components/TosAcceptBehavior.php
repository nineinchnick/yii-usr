<?php

Yii::import('cmsStore.CmsStoreModule', true);
Yii::import('cms.components.*');
Yii::import('cms.models.*');
Yii::import('cms.CmsModule');
Yii::import('cmsStore.components.*');
Yii::import('cmsStore.models.*');
Yii::import('niix-utils.components.*');

/**
 * Description of TosAcceptBehavior
 *
 * @author lukas
 */
class TosAcceptBehavior extends FormModelBehavior implements TosAcceptBehaviorInterface
{
	public $acceptTos;

	public $module;

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
        return array(
			array('acceptTos', 'boolean', 'allowEmpty'=>false, 'on'=>'changeTOS'),
        );
	}

	/**
	 * Declares attribute labels.
	 * @return array
	 */
	public function attributeLabels()
	{
		return array_merge(parent::attributeLabels(), array(
			'acceptTos'	=> Yii::t('UsrModule.usr','Accept TOS'),
		));
	}

    public function save($form, $user)
    {
        // There will be implemented saveing accept of TOS
    }

    public function beforeLogin($event)
    {
        if (! Yii::app()->user->isGuest && Yii::app()->user->isTosAccepted() || $this->owner->acceptTos) {
            // We nedd to transfer response via param's property becouse events do not returns result in Yii
            $event->params['success'] = true;
        }
        $this->owner->scenario = 'changeTOS';
        $event->params['success'] = false;
    }

    public function afterLogin($event)
    {
        $event->params['success'] = false;
//        print_r($event); exit;
    }

    /**
     * Retrieve all articles of given internal name
     *
     * @param string $internal_name
     * @return array
     */
    public function getAllArticles($internal_name)
    {
        return StaticContent::getAllArticles($internal_name);
    }

    /**
     * Retrieve model for specified sitename
     *
     * @param string $siteName
     * @return StaticContent
     * @throws CHttpException
     */
    public function getArticle($siteName, $storeName=null)
    {
        $store = $storeName!==null ? Shops::model()->findByAttributes(array('prefix'=>$storeName)) : Yii::app()->user->getStore();
        $store = $store===null ? Postcodes::model()->notFoundDefault()->find()->shop : $store;

        $article = StaticContent::getSite($siteName, $store);
        if ($article === null) {
            throw new CHttpException(404, Yii::t('yii','Site dosn\'t exists'));
        }

        return $article;
    }

    /**
     * Retrieve array of userTokens
     *
     * @return array
     */
    public function getTokens($storeName=null)
    {
        // Specifying store
        $store = $storeName!==null ? Shops::model()->findByAttributes(array('prefix'=>$storeName)) : Yii::app()->user->getStore();
        $store = $store !== null ? $store : Postcodes::model()->notFoundDefault()->find()->shop;

        // Get tokens for store
        try {
            $userTokens = CmsModule::createTokenBuilder()->getTokens(
                array(CmsStoreModule::CATEGORY_STATIC_CONTENT, CmsStoreModule::CATEGORY_LANDING_PAGE),
                array('store'=>$store)
            );
        } catch(CException $ex) {
            $userTokens = array();
        }

        return $userTokens;
    }

}
