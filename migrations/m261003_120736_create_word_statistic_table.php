<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%word_statistic}}`.
 */
class m261003_120736_create_word_statistic_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%word_statistic}}', [
            'id' => $this->primaryKey(),
            'word_id' => $this->integer()->notNull(),
            'correct_count' => $this->integer()->defaultValue(0),
            'incorrect_count' => $this->integer()->defaultValue(0),
            'nl_to_sp' => $this->boolean()->notNull()->defaultValue(false),
        ]);

        $this->createIndex(
            '{{%idx-word_statistic-word_id}}',
            '{{%word_statistic}}',
            'word_id'
        );

        $this->addForeignKey(
            '{{%fk-word_statistic-word_id}}',
            '{{%word_statistic}}',
            'word_id',
            '{{%word}}',
            'id',
            'CASCADE'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey(
            '{{%fk-word_statistic-word_id}}',
            '{{%word_statistic}}'
        );

        $this->dropIndex(
            '{{%idx-word_statistic-word_id}}',
            '{{%word_statistic}}'
        );

        $this->dropTable('{{%word_statistic}}');
    }
}
