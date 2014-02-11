<?php

interface IManagedIdentity
{
	const STATUS_EMAIL_VERIFIED = 'email_verified';
	const STATUS_IS_ACTIVE = 'is_active';
	const STATUS_IS_DISABLED = 'is_disabled';
	/**
	 * Returns a data provider filled with UserIdentity instances.
	 * @param SearchForm $searchForm
	 * @return CDataProvider
	 */
	public function getDataProvider(SearchForm $searchForm);
	/**
	 * Toggles email verification, active or disabled status.
	 * @param string $status on of following consts: self::STATUS_EMAIL_VERIFIED, self::STATUS_IS_ACTIVE, self::STATUS_IS_DISABLED
	 * @return boolean
	 */
	public function toggleStatus($status);
}
