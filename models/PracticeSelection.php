<?php

namespace app\models;

use yii\base\Model;

class PracticeSelection extends Model
{
    public array $chapters = [];
    public bool $nl_to_sp = true;
    public int $max_words = 20;
    public bool $all_chapters = false;
    public bool $difficult_first = false;

    public function rules(): array
    {
        return [
            [['nl_to_sp'], 'required'],
            ['nl_to_sp', 'boolean'],
            ['all_chapters', 'boolean'],
            ['difficult_first', 'boolean'],
            ['chapters', 'each', 'rule' => ['integer']],
            ['max_words', 'integer', 'min' => 1],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'chapters' => 'Kies lijsten',
            'nl_to_sp' => 'Richting',
            'max_words' => 'Maximum aantal woorden',
            'all_chapters' => 'Alle woorden oefenen (alle lijsten)',
            'difficult_first' => 'Moeilijkste eerst',
        ];
    }
    
}