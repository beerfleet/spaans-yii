<?php

namespace app\models;

use yii\base\Model;

class PracticeSelection extends Model
{
    public array $chapters = [];
    public bool $nl_to_sp = true;

    public function rules(): array
    {
        return [
            [['chapters', 'nl_to_sp'], 'required'],
            ['nl_to_sp', 'boolean'],
            ['chapters', 'each', 'rule' => ['integer']],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'chapters' => 'Kies hoofdstukken',
            'nl_to_sp' => 'Richting',
        ];
    }
    
}