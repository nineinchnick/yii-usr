<?php

class m130702_104658_create_table_user_remote_identity extends CDbMigration
{
	public function safeUp()
	{
		$this->createTable('{{user_remote_identity}}', array(
			'id'=>'pk',
			'user_id'=>'integer NOT NULL REFERENCES {{user}} (id) ON UPDATE CASCADE ON DELETE CASCADE',
			'provider'=>'string NOT NULL',
			'identifier'=>'string NOT NULL',
			'created_on'=>'timestamp NOT NULL',
			'last_used_on'=>'timestamp',
		));
		$this->createIndex('{{user_remote_identity}}_user_id_provider_identifier_idx', '{{user_remote_identity}}', 'user_id, provider, identifier', true);
		$this->createIndex('{{user_remote_identity}}_user_id_idx', '{{user_remote_identity}}', 'user_id');
	}

	public function safeDown()
	{
		$this->dropTable('{{user_remote_identity}}');
	}
}
