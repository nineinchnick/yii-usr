<?php

class m130705_104658_create_table_user_profile_pictures extends CDbMigration
{
	public function safeUp()
	{
		$this->createTable('{{user_profile_pictures}}', array(
			'id' => 'pk',
			'user_id'=>'integer NOT NULL REFERENCES {{users}} (id) ON UPDATE CASCADE ON DELETE CASCADE',
			'original_picture_id'=>'integer REFERENCES {{user_profile_pictures}} (id) ON UPDATE CASCADE ON DELETE CASCADE',
			'filename' => 'string NOT NULL',
			'width' => 'integer NOT NULL',
			'height' => 'integer NOT NULL',
			'mimetype' => 'string NOT NULL',
			'created_on' => 'timestamp NOT NULL',
			'contents' => 'text NOT NULL',
		));
		$this->createIndex('{{user_profile_pictures}}_user_id_idx', '{{user_profile_pictures}}', 'user_id');
		$this->createIndex('{{user_profile_pictures}}_original_picture_id_idx', '{{user_profile_pictures}}', 'original_picture_id');
		$this->createIndex('{{user_profile_pictures}}_width_height_idx', '{{user_profile_pictures}}', 'width, height');
	}

	public function safeDown()
	{
		$this->dropTable('{{user_profile_pictures}}');
	}
}

