<?php

/**
 * # About
 *
 * Usr module allows:
 *
 * * logging in and out
 * * checking if user email has been confirmed (or account activated)
 * * registration
 * * account activation via email
 * * password recovery via email
 * * updating password
 * * force old password reset
 * * (todo) measuring password strength
 * * (todo) forbidding reusing old passwords
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
 * If your application is using path-format URLs with some customized URL rules, you may need to add
 * the following URLs in your application configuration in order to access UsrModule:
 * ~~~
 * 'components'=>array(
 *     'urlManager'=>array(
 *         'urlFormat'=>'path',
 *         'rules'=>array(
 *             'usr/<action:(login|logout|reset|recovery|register|profile)>'=>'usr/default/<action>',
 *             ...other rules...
 *         ),
 *     )
 * )
 * ~~~
 *
 * # Usage scenarios
 *
 * Varios scenarios can be created by enabling or disabling following features:
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
class UsrModule extends CWebModule {
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
	 * @var integer Timeout in days after which user is requred to reset his password after logging in.
	 * If not null, the user identity class must implement IPasswordHistoryIdentity interface.
	 */
	public $passwordTimeout;
	/**
	 * @var string Class name of user identity object used to authenticate user.
	 * Must implement the IPasswordHistoryIdentity interface if passwordTimeout is set.
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
	 * @var string CSS class prefix for flash messages. Set to 'alert alert-' if using Twitter Bootstrap.
	 */
	public $alertCssClassPrefix = 'flash-';
	/**
	 * @var string CSS class for the form submit buttons.
	 */
	public $submitButtonCssClass = '';
	/**
	 * @var array configuration for PHPMailer, values which are arrays will trigger methods for each value instead of setting properties.
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

	public function init() {
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
		$mailerConfig = array_merge(array(
			'IsHTML' => array(true),
			'CharSet' => 'UTF-8',
			'IsMail' => array(),
			'setPathViews' => array('usr.views.emails'),
			'setPathLayouts' => array('usr.views.layouts'),
		), $this->mailerConfig);
		foreach($mailerConfig as $key=>$value) {
			if (is_array($value)) {
				call_user_func_array(array($this->mailer, $key), $value);
			} else {
				$this->mailer->$key = $value;
			}
		}
	}
}
