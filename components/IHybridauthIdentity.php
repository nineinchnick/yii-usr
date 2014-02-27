<?php

interface IHybridauthIdentity
{
	/**
	 * Loads a specific user identity connected to specified provider by an identifier.
	 * @param string $provider
	 * @param string $identifier
	 * @return object a user identity object or null if not found.
	 */
	public static function findByProvider($provider, $identifier);
	/**
	 * Associates this identity with a remote one identified by a provider name and identifier.
     * If another user already has such association, it is removed, since provider and identifier must be a unique pair.
	 * @param string $provider
	 * @param string $identifier
	 * @return boolean
	 */
	public function addRemoteIdentity($provider, $identifier);
    /**
     * Removes association between any identity and a remote one for the selected provider and identifier.
     * This method is static since there can be only one such association.
     * @param string $provider
	 * @param string $identifier
     * @return boolean
     */
    public static function removeRemoteIdentity($provider, $identifier);
    /**
     * Similar to @see getAttributes() but reads the remote profile instead of current identity.
     * @param mixed $remoteProfie
     * @return array
     */
    public static function getRemoteAttributes($remoteProfile);
}

