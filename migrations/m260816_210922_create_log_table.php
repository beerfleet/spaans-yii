<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%log}}`.
 */
class m260816_210922_create_log_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function up()
    {
        $this->createTable('log', [
            'id' => $this->primaryKey(),
            'level' => $this->string(),
            'category' => $this->string(),
            'log_time' => $this->integer(),
            'prefix' => $this->string(),
            'message' => $this->text(),
            'user_id' => $this->integer(),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function down()
    {
        $this->dropTable('log');
    }
}
