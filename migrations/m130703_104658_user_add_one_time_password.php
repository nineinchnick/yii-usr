<?php

class m130703_104658_user_add_one_time_password extends CDbMigration
{
	public function safeUp()
	{
		$this->addColumn('{{user}}', 'one_time_password_secret', 'string');
		$this->addColumn('{{user}}', 'one_time_password_code', 'string');
		$this->addColumn('{{user}}', 'one_time_password_counter', 'integer NOT NULL DEFAULT 0');
	}

	public function safeDown()
	{
		$this->dropColumn('{{user}}', 'one_time_password_counter');
		$this->dropColumn('{{user}}', 'one_time_password_code');
		$this->dropColumn('{{user}}', 'one_time_password_secret');
	}
}

