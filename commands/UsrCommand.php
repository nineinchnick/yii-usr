<?php

class UsrCommand extends CConsoleCommand
{
	public function actionPassword($count = 1, $length = null, $extra_digit = null, $extra_char = null) {
		$usrModule = Yii::app()->getModule('usr');
		if ($length === null)
			$length = $usrModule->dicewareLength;
		if ($extra_digit === null)
			$extra_digit = $usrModule->dicewareExtraDigit;
		if ($extra_char === null)
			$extra_char = $usrModule->dicewareExtraChar;

		$diceware = new Diceware;
		for ($i = 0; $i < $count; $i++)
			echo $diceware->get_phrase($length, $extra_digit, $extra_char) . "\n";
	}

}
