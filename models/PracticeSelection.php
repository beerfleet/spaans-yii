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
    public bool $only_unpracticed = false;

    public function rules(): array
    {
        return [
            [['nl_to_sp'], 'required'],
            ['nl_to_sp', 'boolean'],
            ['all_chapters', 'boolean'],
            ['difficult_first', 'boolean'],
            ['only_unpracticed', 'boolean'],
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
            'only_unpracticed' => 'Alleen nooit-geoefende woorden',
        ];
    }

    /**
     * {@inheritdoc}
     * Normalizes raw POST values before mass assignment to the typed
     * properties. Yii renders a hidden "" input for the chapters
     * checkbox list, so submitting with no box checked would otherwise
     * assign a string to array $chapters (TypeError). Same for a cleared
     * max_words field ("" is mapped to 0 so the min-rule reports it).
     */
    public function load($data, $formName = null)
    {
        $scope = $formName ?? $this->formName();
        if (isset($data[$scope]) && is_array($data[$scope])) {
            if (array_key_exists('chapters', $data[$scope]) && !is_array($data[$scope]['chapters'])) {
                $data[$scope]['chapters'] = $data[$scope]['chapters'] === '' || $data[$scope]['chapters'] === null
                    ? []
                    : [$data[$scope]['chapters']];
            }
            if (array_key_exists('max_words', $data[$scope]) && $data[$scope]['max_words'] === '') {
                $data[$scope]['max_words'] = 0;
            }
        }

        return parent::load($data, $formName);
    }
}