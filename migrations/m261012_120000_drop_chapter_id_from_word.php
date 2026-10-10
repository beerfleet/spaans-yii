<?php

use yii\db\Migration;

/**
 * Step 3 of the many-to-many move: drops word.chapter_id (and its FK).
 * All reads already run on the chapter_word junction and every write
 * mirrors into it, so the column is redundant. safeDown restores the
 * column with each word's lowest list id as its single list.
 */
class m261012_120000_drop_chapter_id_from_word extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->dropForeignKey('fk-word-chapter_id', '{{%word}}');
        $this->dropColumn('{{%word}}', 'chapter_id');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->addColumn('{{%word}}', 'chapter_id', $this->integer()->null());
        $this->execute(
            'UPDATE {{%word}} w SET [[chapter_id]] = ' .
            '(SELECT MIN([[chapter_id]]) FROM {{%chapter_word}} cw WHERE cw.[[word_id]] = w.[[id]])'
        );
        $this->addForeignKey(
            'fk-word-chapter_id',
            '{{%word}}',
            'chapter_id',
            '{{%chapter}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
    }
}
