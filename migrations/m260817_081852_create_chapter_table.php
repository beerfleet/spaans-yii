<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%chapter}}`.
 */
class m260817_081852_create_chapter_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%chapter}}', [
            'id' => $this->primaryKey(),
            'number' => $this->integer()->unique(),
            'name' => $this->string()->notNull(),
            'description' => $this->text(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%chapter}}');
    }
}
