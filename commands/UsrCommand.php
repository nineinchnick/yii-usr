<?php

class UsrCommand extends CConsoleCommand
{
	public function actionPassword($count = 1, $length = null, $extra_digit = null, $extra_char = null) {
		$usrModule = Yii::app()->getModule('usr');
		if ($length === null)
			$length = $usrModule->dicewareLength;
		if ($extra_digit === null)
			$extra_digit = $usrModule->dicewareExtraDigit;
		if ($extra_char === null)
			$extra_char = $usrModule->dicewareExtraChar;

		$diceware = new Diceware;
		for ($i = 0; $i < $count; $i++)
			echo $diceware->get_phrase($length, $extra_digit, $extra_char) . "\n";
	}

    /**
	 * usr.manage
     *   |-usr.create
     *   |-usr.read
     *   |-usr.update
     *   |   |-usr.update.status
     *   |   |-usr.update.auth
     *   |   |-usr.update.attributes
     *   |   \-usr.update.password
     *   \-usr.delete
     */
    public function getTemplateAuthItems() {
        return array(
            array('name'=> 'usr.manage',           'child' => null, 'type'=>CAuthItem::TYPE_TASK),
            array('name'=> 'usr.create',           'child' => 'usr.manage'),
            array('name'=> 'usr.read',             'child' => 'usr.manage'),
            array('name'=> 'usr.update',           'child' => 'usr.manage'),
            array('name'=> 'usr.update.status',    'child' => 'usr.update'),
            array('name'=> 'usr.update.auth',      'child' => 'usr.update'),
            array('name'=> 'usr.update.attributes','child' => 'usr.update'),
            array('name'=> 'usr.update.password',  'child' => 'usr.update'),
            array('name'=> 'usr.delete',           'child' => 'usr.manage'),
        );
    }

    public function getTemplateAuthItemDescriptions()
    {
        return array(
            'usr'                   => Yii::t('UsrModule.auth', 'Manage users'),
            'usr.create'            => Yii::t('UsrModule.auth', 'Create users'),
            'usr.read'              => Yii::t('UsrModule.auth', 'Read any user'),
            'usr.update'            => Yii::t('UsrModule.auth', 'Update any user'),
            'usr.update.status'     => Yii::t('UsrModule.auth', 'Update any user\'s status'),
            'usr.update.auth'       => Yii::t('UsrModule.auth', 'Update any user\'s auth item assignments'),
            'usr.update.attributes' => Yii::t('UsrModule.auth', 'Update any user\'s attributes'),
            'usr.update.password'   => Yii::t('UsrModule.auth', 'Update any user\'s password'),
            'usr.delete'            => Yii::t('UsrModule.auth', 'Delete any user'),
        );
    }

    public function actionCreateAuthItems()
    {
		$auth = Yii::app()->authManager;

        $newAuthItems = array();
        $descriptions = $this->getTemplateAuthItemDescriptions();
        foreach($this->getTemplateAuthItems() as $template) {
            $newAuthItems[$template['name']] = $template;
        }
		$existingAuthItems = $auth->getAuthItems();
        foreach($existingAuthItems as $name=>$existingAuthItem) {
            if (isset($newAuthItems[$name]))
                unset($newAuthItems[$name]);
        }
        foreach($newAuthItems as $template) {
			$type = isset($template['type']) ? $template['type'] : CAuthItem::TYPE_OPERATION;
			$bizRule = isset($template['bizRule']) ? $template['bizRule'] : null;
            $auth->createAuthItem($template['name'], $type, $descriptions[$template['name']], $bizRule);
            if (isset($template['child']) && $template['child'] !== null) {
                $auth->addItemChild($template['child'], $template['name']);
            }
        }
	}

    public function actionRemoveAuthItems()
    {
		$auth = Yii::app()->authManager;

        foreach($this->getTemplateAuthItems() as $template) {
            $auth->removeAuthItem($template['name']);
        }
    }
}
