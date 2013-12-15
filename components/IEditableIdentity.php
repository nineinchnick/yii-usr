<?php

interface IEditableIdentity
{
	/**
	 * Saves a new or existing identity. Does not set or change the password.
	 * @see IPasswordHistoryIdentity::resetPassword()
	 * Should detect if the email changed and mark it as not verified.
	 * @param boolean $requireVerifiedEmail
	 * @return boolean
	 */
	public function save($requireVerifiedEmail=false);
	/**
	 * Returns attributes like username, email, first and last name.
	 * @return array
	 */
	public function getAttributes();
	/**
	 * Sets attributes like username, email, first and last name.
	 * Password should be changed using only the resetPassword() method from the IPasswordHistoryIdentity interface.
	 * @param array $attributes
	 * @return boolean
	 */
	public function setAttributes(array $attributes);
}

