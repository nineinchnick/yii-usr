<?php

class m130704_104658_create_table_user_used_passwords extends CDbMigration
{
	public function safeUp()
	{
		$this->createTable('{{user_used_passwords}}', array(
			'id'=>'pk',
			'user_id'=>'integer NOT NULL',
			'password'=>'string NOT NULL',
			'set_on'=>'timestamp NOT NULL',
		));
		$this->addForeignKey('{{user_used_passwords}}_user_id_fkey', '{{user_used_passwords}}', 'user_id', '{{users}}', 'id', 'CASCADE', 'CASCADE');
		$this->createIndex('{{user_used_passwords}}_user_id_idx', '{{user_used_passwords}}', 'user_id');
	}

	public function safeDown()
	{
		$this->dropTable('{{user_used_passwords}}');
	}
}

