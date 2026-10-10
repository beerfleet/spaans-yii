<?php

use yii\db\Migration;

/**
 * Tracks when a word was last practiced per direction, so statistics
 * can show recency ("Laatst") next to the counts.
 */
class m261013_120000_add_last_practiced_to_word_statistic extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%word_statistic}}', 'last_practiced_at', $this->integer()->null());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{%word_statistic}}', 'last_practiced_at');
    }
}
