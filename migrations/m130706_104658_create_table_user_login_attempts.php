<?php

class m130706_104658_create_table_user_login_attempts extends CDbMigration
{
    public function safeUp()
    {
        $this->createTable('{{user_login_attempts}}', array(
            'id' => 'pk',
            'username' => 'string NOT NULL',
            'user_id' => 'integer REFERENCES {{users}} (id) ON UPDATE CASCADE ON DELETE CASCADE',
            'performed_on' => 'timestamp NOT NULL',
            'is_successful' => 'boolean NOT NULL DEFAULT false',
            'session_id' => 'string',
            'ipv4' => 'integer',
            'user_agent' => 'string',
        ));

        $this->createIndex('{{user_login_attempts}}_user_id_idx', '{{user_login_attempts}}', 'user_id');
    }

    public function safeDown()
    {
        $this->dropTable('{{user_login_attempts}}');
    }
}
