<?php

use yii\db\Migration;

/**
 * Step 1 of the many-to-many move: junction table between word and chapter,
 * backfilled from the current word.chapter_id. Step 1 changes no behavior:
 * word.chapter_id stays the leading column until reads/writes move over.
 */
class m261011_120000_create_chapter_word_junction extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%chapter_word}}', [
            'word_id' => $this->integer()->notNull(),
            'chapter_id' => $this->integer()->notNull(),
            'PRIMARY KEY(word_id, chapter_id)',
        ]);

        $this->createIndex(
            '{{%idx-chapter_word-chapter_id}}',
            '{{%chapter_word}}',
            'chapter_id'
        );

        $this->addForeignKey(
            '{{%fk-chapter_word-word_id}}',
            '{{%chapter_word}}',
            'word_id',
            '{{%word}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
        $this->addForeignKey(
            '{{%fk-chapter_word-chapter_id}}',
            '{{%chapter_word}}',
            'chapter_id',
            '{{%chapter}}',
            'id',
            'CASCADE',
            'CASCADE'
        );

        // Backfill current single-list assignments.
        $this->execute(
            'INSERT INTO {{%chapter_word}} ([[word_id]], [[chapter_id]]) ' .
            'SELECT [[id]], [[chapter_id]] FROM {{%word}} WHERE [[chapter_id]] IS NOT NULL'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('{{%fk-chapter_word-word_id}}', '{{%chapter_word}}');
        $this->dropForeignKey('{{%fk-chapter_word-chapter_id}}', '{{%chapter_word}}');
        $this->dropTable('{{%chapter_word}}');
    }
}
