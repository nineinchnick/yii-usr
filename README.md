Usr module
==========

Usr module is inspired by the popular Yii-user module but written from scratch. It provides basic user actions like:

* logging in and out,
* password recovery and reset if expired
* registration with optional email verification,
* viewing and updating a minimal user profile along with changing password, profile pictures are supported
* user managment

It's goal is to be easier to integrate into current projects by not requiring to modify existing user database table and model.
Only the UserIdentity class is used to provide all business logic by implementing few provided interfaces.

Key differences from yii-user:

* clean codebase, easier to read/review
* use good password hashing
* no need to modify current tables and models
* bundled mailer class
* built-in Hybridauth for logging using social site identities
* built-in Google Authenticator for two step authentication using one time passwords

# Installation

Using composer:

~~~bash
curl -sS https://getcomposer.org/installer | php
./composer.phar require nineinchnick/yii-usr:dev-master
~~~

Download and unpack in _protected/modules_.

Enable the module in the config/main.php file:

~~~php
return array(
    ......
    'modules'=>array(
        'usr'=>array(
               'userIdentityClass' => 'UserIdentity',
        ),
    ),
)
~~~

See UsrModule.php file for full options reference.

To be able to use user managment, create auth items using the `createAuthItems` command and assign them to a role or users.

## Fast setup and/or new projects

This assumes there are no user tables in the database and all features can be enabled.

* Copy migrations files from the module to your project and adjust their filenames and class names. Apply them.
* Copy the ExampleUserIdentity to your _components_ directory changing it's name to UserIdentity, remove the _abstract_ keyword from the class definition.
* Copy each file starting with _Example_ from the models directory to your projects and remove that prefix. Remove the _abstract_ keyword from the class definition.

## Custom setup and/or existing projects

If the module will be used with existing database tables and/or not all features will be used the identity class should be copied and adjusted or reimplemented from scratch.

Requirements for the UserIdentity class are described in next chapter.

# Identity interfaces

To be able to use all features of the Usr module, the UserIdentity class must implement some or all of the following interfaces.

For details, please read comments in each identity file or see the provided _ExampleUserIdentity_ file.

## Editable

This interface allows to create new identities (register) and update existing ones.

## Active/disabled and email verification

This interface allows:

* finding existing identities using one of its attributes.
* generating and verifying an activation key used to verify email and send a recovery link

Remember to invalidate the email if it changes in the save() method from the Editable interface.

## Password history

This interface allows password reset with optional tracking of used passwords. This allows to detect expired passwords and avoid reusing old passwords by users.

See the ExpiredPasswordBehavior description below.

## Hybridauth

This interface allows finding local identity associated with a remote one (from an external social site) and creating such associations.

## One Time Password

This interface allow saving and retrieving a secret used to generate one time passwords. Also, last used password and counter used to generate last password are saved and retrieve to protect against reply attacks.

See the OneTimePasswordFormBehavior description below.

## Profile Pictures

Allows users to upload a profile picture. The example identity uses [Gravatar](http://gravatar.com/) to provide a default picture.

## Managable

Allows to manage users:

* update their profiles (and pictures)
* change passwords
* assign authorization roles
* activate/disable and mark email as verified
* see details as timestamps of account creation, last profile update and last visit

# Custom login behaviors

The login action can be extended by attaching custom behaviors to the LoginForm. This is done by configuring the UsrModule.loginFormBehaviors property.

There are two such behaviors provided by yii-usr module:

* ExpiredPasswordBehavior
* OneTimePasswordFormBehavior

### ExpiredPasswordBehavior

Validates if current password has expired and forces the users to change it before logging in.

Options:

* passwordTimeout - number of days after which user is requred to reset his password after logging in

### OneTimePasswordFormBehavior

Two step authentication using one time passwords.

Options:

* authenticator - if null, set to a new instance of GoogleAuthenticator class.
* mode - if set to OneTimePasswordFormBehavior::OTP_TIME or OneTimePasswordFormBehavior::OTP_COUNTER, two step authentication is enabled using one time passwords. Time mode uses codes generated using current time and requires the user to use an external application, like Google Authenticator on Android. Counter mode uses codes generated using a sequence and sends them to user's email.
* required - should the user be allowed to log in even if a secret hasn't been generated yet (is null). This only makes sense when mode is 'counter', secrets are generated when registering users and a code is sent via email.
* timeout - Number of seconds for how long is the last verified code valid.

## Example usage

~~~php
'loginFormBehaviors' => array(
    'expiredPasswordBehavior' => array(
        'class' => 'ExpiredPasswordBehavior',
        'passwordTimeout' => 10,
    ),
    'oneTimePasswordBehavior' => array(
        'class' => 'OneTimePasswordFormBehavior',
        'mode' => OneTimePasswordFormBehavior::OTP_TIME,
        'required' => true,
        'timeout' => 123,
    ),
    // ... other behaviors
),
~~~

# User model example

A sample ExampleUserIdentity and corresponding ExampleUser and ExampleUserUsedPassword models along with database migrations are provided respectively in the 'components', 'models' and 'migrations' folders.

They could be used as-is by extending from or copying to be modified to better suit a project.

To use the provided migrations it's best to copy them to your migrations directory and adjust the filenames and classnames to current date and time. Also, they could be modified to remove not needed features.

# Diceware aka password generator

A simple implementation of a Diceware Passphrase generator is provided to aid users when they need to create a good, long but also easy to remember passphrase.

Read more at [the Diceware Passphrase homepage](http://world.std.com/~reinhold/diceware.html).

# Customize

## Custom profile fields

It is possible to add more profile fields:

* Override view files in a theme.
* Create a behavior class extending _FormModelBehavior_.
* Add that behvaior in the _UsrModule::profileFormBehaviors_ property.
* Remember to update _setAttributes_ and _getAttributes_ methods of your UserIdentity class to include new profile fields.

The behavior will include properties, rules and labels. Rules can contain inline validators defined in that behavior, just call them using the _behaviorValidator_ helper method:
~~~php
   // BEHAVIOR_NAME is the key used in UsrModule::profileFormBehaviors
   // INLINE_VALIDATOR is the name of the inline validator method defined in the behavior
   array('attribute', 'behaviorValidator', 'behavior'=>'BEHAVIOR_NAME', 'validator'=>'INLINE_VALIDATOR', /* other params */),
~~~

## Email templates

Set the _setPathViews_ and _setPathLayouts_ keys under the _mailerConfig_ module option.

## Translations

Feel free to send new and updated translations to the author.

# Usage scenarios

Various scenarios can be created by enabling or disabling following features:

* registration
* email verification
* account activation

Implementing those scenarios require some logic outside the scope of this module.

## Public site

Users can register by themselves. Their accounts are activated instantly or after verifying email.

## Moderated site

Users can register, but to allow them to log in an administrator must activate their accounts manually, optionally assigning an authorization profile.
Email verification is optional and activation could trigger an email notification.

# Configuration for Twitter Bootstrap

If using the [bootstrap extension](http://www.yiiframework.com/extension/bootstrap), the following configuration may be used:

~~~
'usr' => array(
		'layout' => '//layouts/centered',
		'formClass'=>'bootstrap.widgets.TbActiveForm',
		'detailViewClass'=>'bootstrap.widgets.TbDetailView',
		'formCssClass'=>'form well',
		'alertCssClassPrefix'=>'alert alert-',
		'submitButtonCssClass'=>'btn btn-primary',
		'htmlCss' => array(
			'errorSummaryCss' => 'alert alert-error',
			'errorMessageCss' => 'text-error',
		),
		// mail
		...mail config...
	),
~~~

Besides that, all views could be overriden in a theme. A following skin can be used for user managment grid:

~~~
<?php
return array(
	'default' => array(
		'itemsCssClass' => 'table table-striped table-bordered table-condensed',
		'pagerCssClass' => 'paging_bootstrap pagination',
	),
);
~~~

# License

MIT or BSD


