<?php

namespace app\models;

use yii\base\Model;

class PracticeAnswer extends Model
{
    public string $answer = '';

    public function rules(): array
    {
        return [
            ['answer', 'required', 'message' => 'Het veld {attribute} is verplicht.'],
            ['answer', 'string', 'max' => 255],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'answer' => 'Antwoord',
        ];
    }
}