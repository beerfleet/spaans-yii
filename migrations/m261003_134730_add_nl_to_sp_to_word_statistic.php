<?php

use yii\db\Migration;

class m261003_134730_add_nl_to_sp_to_word_statistic extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%word_statistic}}', 'nl_to_sp', $this->boolean()->notNull()->defaultValue(false));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{%word_statistic}}', 'nl_to_sp');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m261003_134730_add_nl_to_sp_to_word_statistic cannot be reverted.\n";

        return false;
    }
    */
}
