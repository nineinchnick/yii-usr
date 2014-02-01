<?php

interface IPictureIdentity
{
	/**
	 * Saves an uploaded picture. This method can be left unimplemented (throw an exception) to disable image upload,
	 * just remember to remove any rules from the UsrModule::$pictureUploadRules module property.
	 * @param CUploadedFile $picture
	 * @return boolean
	 */
	public function savePicture($picture);

	/**
	 * Returns an URL to the profile picture.
	 * @param integer $width
	 * @param integer $height
	 * @return string
	 */
	public function getPictureUrl($width=80, $height=80);

	/**
	 * Returns a picture with some metadata like dimensions and mimetype.
	 * @param string $id
	 * @param boolean $currentIdentity if true, only pictures for the current identity will be returned
	 * @return array with keys: mimetype, width, height, picture
	 */
	public function getPicture($id, $currentIdentity=true);
}
