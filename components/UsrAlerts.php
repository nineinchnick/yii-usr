<?php

/**
 * Alerts displays flash messages.
 *
 * ~~~
 * // $this is the view object currently being used
 * $this->widget('usr.components.UsrAlerts');
 * ~~~
 *
 * @author Jan Waś <jwas@nets.com.pl>
 */
class UsrAlerts extends CWidget
{
	public $cssClassPrefix;
	/**
	 * Renders the widget.
	 */
	public function run()
	{
		if (($flashMessages = Yii::app()->user->getFlashes())) {
			echo '<ul class="flashes">';
			foreach($flashMessages as $key => $message) {
				echo '<li><div class="'.$this->cssClassPrefix.$key.'">'.$message.'</div></li>';
			}
			echo '</ul>';
		}
	}
}
