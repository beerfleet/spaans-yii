<?php

use yii\db\Migration;

class m260817_084302_add_foreign_key_to_word_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addForeignKey(
            'fk-word-chapter_id', // Name of the foreign key
            'word',              // Table that the foreign key is in
            'chapter_id',        // Column in the table that the foreign key is in
            'chapter',           // Table that the foreign key references
            'id',                // Column in the referenced table
            'CASCADE',           // On delete
            'CASCADE'            // On update
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk-word-chapter_id', 'word');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m260817_084302_add_foreign_key_to_word_table cannot be reverted.\n";

        return false;
    }
    */
}
