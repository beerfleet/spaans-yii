<?php

use yii\db\Migration;

/**
 * Drops the legacy course number from chapter: lists sort by name and
 * the number is no longer shown or entered anywhere.
 */
class m261010_120000_drop_number_from_chapter extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->dropColumn('{{%chapter}}', 'number');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->addColumn('{{%chapter}}', 'number', $this->integer()->null());
    }
}
