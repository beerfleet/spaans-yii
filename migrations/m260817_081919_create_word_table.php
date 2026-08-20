<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%word}}`.
 */
class m260817_081919_create_word_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%word}}', [
            'id' => $this->primaryKey(),
            'chapter_id' => $this->integer()->notNull(),
            'dutch' => $this->string(),
            'spanish' => $this->string()->notNull(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {        
        $this->dropTable('{{%word}}');
    }
}
