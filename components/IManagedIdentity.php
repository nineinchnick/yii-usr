<?php

interface IManagedIdentity
{
	/**
	 * Returns a data provider filled with UserIdentity instances.
	 * @param SearchForm $searchForm
	 * @return CDataProvider
	 */
	public function getDataProvider(SearchForm $searchForm);
}
