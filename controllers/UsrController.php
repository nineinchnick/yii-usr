<?php

abstract class UsrController extends CController
{
    /**
     * Sends out an email containing instructions and link to the email verification
     * or password recovery page, containing an activation key.
     * @param  CFormModel $model it must have a getIdentity() method
     * @param  strign     $mode  'recovery', 'verify' or 'oneTimePassword'
     * @return boolean    if sending the email succeeded
     */
    public function sendEmail(CFormModel $model, $mode)
    {
        $mail = $this->module->mailer;
        $mail->AddAddress($model->getIdentity()->getEmail(), $model->getIdentity()->getName());
        $params = array(
            'siteUrl' => $this->createAbsoluteUrl('/'),
        );
        switch ($mode) {
        default: return false;
        case 'recovery':
        case 'verify':
            $mail->Subject = $mode == 'recovery' ? Yii::t('UsrModule.usr', 'Password recovery') : Yii::t('UsrModule.usr', 'Email address verification');
            $params['actionUrl'] = $this->createAbsoluteUrl('default/'.$mode, array(
                'activationKey' => $model->getIdentity()->getActivationKey(),
                'username' => $model->getIdentity()->getName(),
            ));
            break;
        case 'oneTimePassword':
            $mail->Subject = Yii::t('UsrModule.usr', 'One Time Password');
            $params['code'] = $model->getNewCode();
            break;
        }
        $body = $this->renderPartial($mail->getPathViews().'.'.$mode, $params, true);
        $full = $this->renderPartial($mail->getPathLayouts().'.email', array('content' => $body), true);
        $mail->MsgHTML($full);
        if ($mail->Send()) {
            return true;
        } else {
            Yii::log($mail->ErrorInfo, 'error');

            return false;
        }
    }

    /**
     * Retreive view name and params based on scenario name and module configuration.
     *
     * @param  string $scenario
     * @param  string $default  default view name if scenario is null
     * @return array  two values, view name (string) and view params (array)
     */
    public function getScenarioView($scenario, $default)
    {
        if (empty($scenario)) {
            $scenario = $default;
        }
        if (!isset($this->module->scenarioViews[$scenario])) {
            return array($scenario, array());
        }
        // config, scenario, default
        $config = $this->module->scenarioViews[$scenario];
        if (isset($config['view'])) {
            $view = $config['view'];
            unset($config['view']);
        } else {
            $view = $scenario;
        }

        return array($view, $config);
    }

    /**
     * Redirects user either to returnUrl or main page.
     */
    public function afterLogin()
    {
        $returnUrl = Yii::app()->user->returnUrl;
        $returnUrlParts = explode('/', is_array($returnUrl) ? reset($returnUrl) : $returnUrl);
        $url = end($returnUrlParts) == 'index.php' ? '/' : Yii::app()->user->returnUrl;
        $this->redirect($url);
    }
}
