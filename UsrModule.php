<?php

/**
 * # About
 *
 * Usr module allows:
 *
 * * logging in and out
 * * checking if user email has been confirmed (or account activated)
 * * registration
 * * updating profile data
 * * account activation via email
 * * password recovery via email
 * * updating password
 * * force old password reset
 * * forbidding reusing old passwords
 * * two step authentication using one time passwords
 * * log in using social sites credentials
 *
 * Usr module does not provide user managment. It's not aware of data structures holding user information
 * and only requires the user identity to implement some interfaces.
 * This allows it to be easily integrated into projects with different user model attributes.
 *
 * To use UsrModule, you must include it as a module in the application configuration like the following:
 * ~~~
 * return array(
 *     ......
 *     'modules'=>array(
 *         'usr'=>array(
 *				'userIdentityClass' => 'UserIdentity',
 *         ),
 *     ),
 * )
 * ~~~
 *
 * # Usage scenarios
 *
 * Various scenarios can be created by enabling or disabling following features:
 *
 * * registration
 * * email verification
 * * account activation
 *
 * Implementing those scenarios require some logic outside the scope of this module.
 *
 * ## Public site
 *
 * Users can register by themselves. Their accounts are activated instantly or after verifying email.
 *
 * ## Moderated site
 *
 * Users can register, but to allow them to log in an administrator must activate their accounts manually, optionally assigning an authorization profile.
 * Email verification is optional and activation could trigger an email notification.
 *
 * # Configuration for Twitter Bootstrap
 *
 * If using the bootstrap extension (http://www.yiiframework.com/extension/bootstrap), the following configuration may be used:
 *
 * ~~~
 * 'usr' => array(
 * 		'layout' => '//layouts/centered',
 * 		'formClass'=>'bootstrap.widgets.TbActiveForm',
 * 		'detailViewClass'=>'bootstrap.widgets.TbDetailView',
 * 		'formCssClass'=>'form well',
 * 		'alertCssClassPrefix'=>'alert alert-',
 * 		'submitButtonCssClass'=>'btn btn-primary',
 * 		// mail
 * 		...mail config...
 * 	),
 * ~~~
 *
 * # Demo
 *
 * See it in action at (demo).
 *
 * # Credits
 *
 * This module is heavily inspired by the yii-user module. It is simpler and has less features, hence the shorter name.
 * It aims at keeping closer to the KISS principle and keep the code clean and more readable and maintainable.
 *
 * @author Jan Was <jwas@nets.com.pl>
 */
class UsrModule extends CWebModule
{
    /**
     * @var boolean Is new user registration enabled.
     */
    public $registrationEnabled = true;
    /**
     * @var boolean Does every new user needs to verify supplied email.
     */
    public $requireVerifiedEmail = true;
    /**
     * @var boolean Is password recovery permitted.
     */
    public $recoveryEnabled = true;
    /**
     * @var integer For how long the user will be logged in without any activity, in seconds. Defaults to 3600*24*30 seconds (30 days).
     */
    public $rememberMeDuration = 2592000;
    /**
     * @var array Set of rules to measure the password strength when choosing new password in the registration or recovery forms.
     * Rules should NOT include attribute name, it will be added when they are used.
     * If null, defaults to minimum 8 characters and at least one of each: lower and upper case character and a digit.
     * @see BasePasswordForm
     */
    public $passwordStrengthRules;
    /**
     * @var array Set of rules that restricts what images can be uploaded as user picture. If null, picture upload is disabled.
     * Rules should NOT include attribute name, it will be added when they are used.
     * This should probably include a 'file' validator, like in the following example:
     * array(
     *     array('file', 'allowEmpty' => true, 'types'=>'jpg, gif, png', 'maxSize'=>2*1024*1024, 'safe' => false, 'maxFiles' => 1),
     * ),
     * @see CFileValidator
     */
    public $pictureUploadRules;
    /**
     * @var string Class name of user identity object used to authenticate user.
     */
    public $userIdentityClass = 'CUserIdentity';
    /**
     * @var string Class name for input form widgets.
     */
    public $formClass = 'CActiveForm';
    /**
     * @var string Class name for detail view widget.
     */
    public $detailViewClass = 'zii.widgets.CDetailView';
    /**
     * @var string CSS class for html forms.
     */
    public $formCssClass = 'form';
    /**
     * @var array static properties of CHtml class, such as errorSummaryCss and errorMessageCss.
     */
    public $htmlCss;
    /**
     * @var string CSS class prefix for flash messages. Set to 'alert alert-' if using Twitter Bootstrap.
     */
    public $alertCssClassPrefix = 'flash-';
    /**
     * @var string CSS class for the form submit buttons.
     */
    public $submitButtonCssClass = '';
    /**
     * @var array configuration for PHPMailer, values which are arrays will trigger methods
     * for each value instead of setting properties.
     * For a full reference, please resolve to PHPMailer documentation.
     */
    public $mailerConfig = array(
        'SetLanguage' => array('en'),
        'SetFrom' => array('from@example.com', 'Administrator'),
        'AddReplyTo' => array('replyto@example.com','Administrator'),
        'IsMail' => array(),
        // SMTP options
        //'IsSMTP' => array(),
        //'Host' => 'localhost',
        //'Port' => 25,
        //'Username' => 'login',
        //'Password' => 'password',
        // extension properties
        'setPathViews' => array('usr.views.emails'),
        'setPathLayouts' => array('usr.views.layouts'),
    );
    /**
     * @var boolean If true a link for generating passwords will be rendered under new password field.
     */
    public $dicewareEnabled = true;
    /**
     * @var integer Number of words in password generated using the diceware component.
     */
    public $dicewareLength = 4;
    /**
     * @var boolean Should an extra digit be added in password generated using the diceware component.
     */
    public $dicewareExtraDigit = true;
    /**
     * @var integer Should an extra random character be added in password generated using the diceware component.
     */
    public $dicewareExtraChar = false;
    /**
     * @var array Available Hybridauth providers, indexed by name, defined as
     * array(
     *   'enabled'=>true|false,
     *   'keys'=>array('id'=>string, 'key'=>string, 'secret'=>string),
     *   'scope'=>string,
     * )
     * @see http://hybridauth.sourceforge.net/userguide.html
     */
    public $hybridauthProviders = array();
    /**
     * @var array list of identity attribute names that should be passed to UserIdentity::find()
     * to find a local identity matching a remote one.
     * If one is found, user must authorize to associate it. If none has been found,
     * a new local identity is automatically registered.
     * If the attribute list is empty a full pre-filled registration and login forms are displayed.
     */
    public $associateByAttributes = array('email');

    /**
     * @var array If not null, CAPTCHA will be enabled on the registration and recovery form
     * and this will be passed as arguments to the CCaptcha widget.
     * Remember to include the 'captchaAction'=>'/usr/default/captcha' property.
     * Adjust the module id.
     */
    public $captcha;
    /**
     * @var array Extra behaviors to attach to the profile form. If the view/update views are overriden in a theme
     * this can be used to display/update extra profile fields. @see FormModelBehavior
     */
    public $profileFormBehaviors;

    /**
     * @var array Extra behaviors to attach to the login form. If the views are overriden in a theme
     * this can be used to placed extra logic. @see FormModelBehavior
     */
    public $loginFormBehaviors;

    /**
     * @var array View params used in different LoginForm model scenarios.
     * View name can be changed by setting the 'view' key.
     */
    public $scenarioViews = array(
        'reset' => array('view' => 'reset'),
        'verifyOTP' => array('view' => 'verifyOTP'),
    );
    /**
     * @var Hybrid_Auth set if $hybridauthProviders are not empty
     */
    protected $_hybridauth;

    /**
     * @inheritdoc
     */
    public $controllerMap = array(
        'login' => 'usr.controllers.DefaultController',
        'logout' => 'usr.controllers.DefaultController',
        'reset' => 'usr.controllers.DefaultController',
        'recovery' => 'usr.controllers.DefaultController',
        'register' => 'usr.controllers.DefaultController',
        'profile' => 'usr.controllers.DefaultController',
        'profilePicture' => 'usr.controllers.DefaultController',
        'password' => 'usr.controllers.DefaultController',
    );

    /**
     * @inheritdoc
     */
    public function getVersion()
    {
        return '1.1.0';
    }

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        $this->setImport(array(
            'usr.models.*',
            'usr.components.*',
        ));
        $this->setComponents(array(
            'mailer' => array(
                'class' => 'usr.extensions.mailer.EMailer',
                'pathViews' => 'usr.views.emails',
                'pathLayouts' => 'usr.views.layouts',
            ),
        ), false);
        if (is_array($this->htmlCss)) {
            foreach ($this->htmlCss as $name => $value) {
                CHtml::$$name = $value;
            }
        }
        $this->setupMailer();
        if ($this->hybridauthEnabled()) {
            $hybridauthConfig = array(
                'base_url' => Yii::app()->createAbsoluteUrl('/'.$this->id.'/hybridauth/callback'),
                'providers' => $this->hybridauthProviders,
                //'debug_mode' => YII_DEBUG,
                //'debug_file' => Yii::app()->runtimePath . '/hybridauth.log',
            );
            require dirname(__FILE__).'/extensions/Hybrid/Auth.php';
            $this->_hybridauth = new Hybrid_Auth($hybridauthConfig);
        }
    }

    public function setupMailer()
    {
        $mailerConfig = array_merge(array(
            'IsHTML' => array(true),
            'CharSet' => 'UTF-8',
            'IsMail' => array(),
            'setPathViews' => array('usr.views.emails'),
            'setPathLayouts' => array('usr.views.layouts'),
        ), $this->mailerConfig);
        foreach ($mailerConfig as $key => $value) {
            if (is_array($value)) {
                call_user_func_array(array($this->mailer, $key), $value);
            } else {
                $this->mailer->$key = $value;
            }
        }
    }

    /**
     * Checks if any Hybridauth provider has been configured.
     * @return boolean
     */
    public function hybridauthEnabled()
    {
        $providers = array_filter($this->hybridauthProviders, function ($p) {return !isset($p['enabled']) || $p['enabled'];});

        return !empty($providers);
    }

    /**
     * Gets the Hybridauth object
     * @return Hybrid_Auth
     */
    public function getHybridAuth()
    {
        return $this->_hybridauth;
    }

    /**
     * A factory to create pre-configured form models. Only model class names from the nineinchnick\usr\models namespace are allowed.
     * Sets scenario, password strength rules for models extending BasePasswordForm and attaches behaviors.
     *
     * @param  string $class    without the namespace
     * @param  string $scenario
     * @return Model
     */
    public function createFormModel($class, $scenario = '')
    {
        /** @var CFormModel */
        $form = new $class($scenario);
        if ($form instanceof BaseUsrForm) {
            $form->webUser = Yii::app()->user;
        }
        $form->userIdentityClass = $this->userIdentityClass;
        if ($form instanceof BasePasswordForm) {
            $form->passwordStrengthRules = $this->passwordStrengthRules;
        }
        switch ($class) {
            default:
                break;
            case 'ProfileForm':
                $form->pictureUploadRules = $this->pictureUploadRules;
                if (!empty($this->profileFormBehaviors)) {
                    foreach ($this->profileFormBehaviors as $name => $config) {
                        $form->attachBehavior($name, $config);
                    }
                }
            case 'RecoveryForm':
                if ($this->captcha !== null && CCaptcha::checkRequirements()) {
                    $form->attachBehavior('captcha', array(
                        'class' => 'CaptchaFormBehavior',
                        'ruleOptions' => $class == 'ProfileForm' ? array('on' => 'register') : array('except' => 'reset,verify'),
                    ));
                }
                break;
            case 'LoginForm':
                if ($this->loginFormBehaviors !== null && is_array($this->loginFormBehaviors)) {
                    foreach ($this->loginFormBehaviors as $name => $config) {
                        $form->attachBehavior($name, $config);
                    }
                }
                break;
            case 'HybridauthForm':
                $form->setValidProviders($this->hybridauthProviders);
                $form->setHybridAuth($this->getHybridAuth());
                break;
        }

        return $form;
    }
}
