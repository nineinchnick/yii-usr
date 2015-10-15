<?php /*
@var $this DefaultController
@var $model ProfileForm */
$popupScript = <<<JavaScript
function PopupCenter(url, title, w, h) {
    // credits: http://www.xtf.dk/2011/08/center-new-popup-window-even-on.html
    // Fixes dual-screen position                         Most browsers      Firefox
    var dualScreenLeft = window.screenLeft != undefined ? window.screenLeft : screen.left;
    var dualScreenTop = window.screenTop != undefined ? window.screenTop : screen.top;

    width = window.innerWidth ? window.innerWidth : document.documentElement.clientWidth ? document.documentElement.clientWidth : screen.width;
    height = window.innerHeight ? window.innerHeight : document.documentElement.clientHeight ? document.documentElement.clientHeight : screen.height;

    var left = ((width / 2) - (w / 2)) + dualScreenLeft;
    var top = ((height / 2) - (h / 2)) + dualScreenTop;
    var options = 'toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=no, resizable=no, copyhistory=no';
    var newWindow = window.open(url, title, options+', width=' + w + ', height=' + h + ', top=' + top + ', left=' + left);

    if (window.focus) {
        newWindow.focus();
    }
    return false;
}
JavaScript;
Yii::app()->clientScript->registerCssFile(Yii::app()->getAssetManager()->publish(Yii::getPathOfAlias($this->module->id.'.components.assets.zocial')).'/zocial.css');
Yii::app()->clientScript->registerScript(__CLASS__.'#popup', $popupScript, CClientScript::POS_END);
?>
        <ul>
<?php foreach ($this->module->hybridauthProviders as $provider => $settings): if (!$settings['enabled']) {
     continue;
 } ?>
            <li>
                <?php if (Yii::app()->user->isGuest): ?>
                <a class="zocial <?php echo strtolower($provider); ?>" href="<?php echo $this->createUrl('hybridauth/popup', array('provider' => $provider)); ?>"
                    onclick="return PopupCenter($(this).attr('href'), 'Hybridauth', 400, 550);">
                    <?php echo Yii::t('UsrModule.usr', 'Log in using {provider}', array('{provider}' => $provider)); ?>
                </a>
                <?php elseif (isset($model) && $model->getIdentity()->hasRemoteIdentity(strtolower($provider))): ?>
                <a class="zocial <?php echo strtolower($provider); ?>" href="<?php echo $this->createUrl('hybridauth/logout', array('provider' => $provider)); ?>">
                    <?php echo Yii::t('UsrModule.usr', 'Disconnect with {provider}', array('{provider}' => $provider)); ?>
                </a>
                <?php else: ?>
                <a class="zocial <?php echo strtolower($provider); ?>" href="<?php echo $this->createUrl('hybridauth/popup', array('provider' => $provider)); ?>"
                    onclick="return PopupCenter($(this).attr('href'), 'Hybridauth', 400, 550);">
                    <?php echo Yii::t('UsrModule.usr', 'Associate this profile with {provider}', array('{provider}' => $provider)); ?>
                </a>
                <?php endif; ?>
            </li>
<?php endforeach; ?>
        </ul>
