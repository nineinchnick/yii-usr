<?php

class m130701_104658_create_table_user extends CDbMigration
{
	public function safeUp()
	{
		$this->createTable('{{user}}', array(
			'id'=>'pk',
			'username'=>'string NOT NULL',
			'password'=>'string NOT NULL',
			'email'=>'string NOT NULL',
			'firstname'=>'string',
			'lastname'=>'string',
			'activation_key'=>'string',
			'created_on'=>'timestamp',
			'updated_on'=>'timestamp',
			'last_visit_on'=>'timestamp',
			'password_set_on'=>'timestamp',
			'email_verified'=>'boolean NOT NULL DEFAULT FALSE',
			'is_active'=>'boolean NOT NULL DEFAULT FALSE',
			'is_disabled'=>'boolean NOT NULL DEFAULT FALSE',
		));
		$this->createIndex('{{user}}_username_idx', '{{user}}', 'username', true);
		$this->createIndex('{{user}}_email_idx', '{{user}}', 'email', true);
		$this->createIndex('{{user}}_email_verified_idx', '{{user}}', 'email_verified');
		$this->createIndex('{{user}}_is_active_idx', '{{user}}', 'is_active');
		$this->createIndex('{{user}}_is_disabled_idx', '{{user}}', 'is_disabled');
	}

	public function safeDown()
	{
		$this->dropTable('{{user}}');
	}
}
