<?php

use yii\db\Migration;

/**
 * Handles adding columns to table `{{%log}}`.
 */
class m260816_211630_add_prefix_column_to_log_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('log', 'prefix', $this->string());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('log', 'prefix');
    }
}
