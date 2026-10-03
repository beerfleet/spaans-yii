<?php
// models/WordStatistic.php

namespace app\models;

use yii\db\ActiveRecord;

class WordStatistic extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%word_statistic}}';
    }

    public function rules()
    {
        return [
            [['word_id', 'correct_count', 'incorrect_count', 'nl_to_sp'], 'integer'],
            [['word_id'], 'exist', 'skipOnError' => true, 'targetClass' => Word::class, 'targetAttribute' => ['word_id' => 'id']],
        ];
    }

    public function getWord()
    {
        return $this->hasOne(Word::class, ['id' => 'word_id']);
    }
}
