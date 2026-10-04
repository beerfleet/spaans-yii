<?php

use yii\db\Migration;

/**
 * Allows words to be created without a chapter (e.g. bulk import
 * where words do not all belong to the same chapter).
 */
class m261004_100000_allow_null_chapter_in_word extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->dropForeignKey('fk-word-chapter_id', '{{%word}}');
        $this->alterColumn('{{%word}}', 'chapter_id', $this->integer()->null());
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

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // Assign orphaned words to the first chapter so NOT NULL can be restored.
        $firstChapterId = (new \yii\db\Query())
            ->select('id')
            ->from('{{%chapter}}')
            ->orderBy(['id' => SORT_ASC])
            ->scalar($this->db);

        if ($firstChapterId === false) {
            echo "Cannot revert: no chapter available to assign orphaned words.\n";
            return false;
        }

        $this->update('{{%word}}', ['chapter_id' => $firstChapterId], ['chapter_id' => null]);

        $this->dropForeignKey('fk-word-chapter_id', '{{%word}}');
        $this->alterColumn('{{%word}}', 'chapter_id', $this->integer()->notNull());
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
