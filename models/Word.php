<?php

namespace app\models;

use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use app\models\WordStatistic;

/**
 * This is the model class for table "word".
 *
 * @property int $id
 * @property int|null $chapter_id
 * @property string|null $dutch
 * @property string $spanish
 * @property int $created_at
 * @property int $updated_at
 *
 * @property Chapter $chapter
 */
class Word extends ActiveRecord
{

    /**
     * Raw bulk input (one expression per line). Form-only attribute for the
     * bulkForm scenario, so a whole batch is not limited to 255 chars.
     * @var string|null
     */
    public $bulkText;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'word';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        if ($this->scenario === 'bulkForm') {
            return [
                [['bulkText'], 'required', 'message' => 'Het veld {attribute} is verplicht'],
                [['bulkText'], 'string', 'max' => 20000],
                [['chapter_id'], 'default', 'value' => null],
                [['chapter_id'], 'integer'],
                [['chapter_id'], 'exist', 'skipOnError' => true, 'targetClass' => Chapter::class, 'targetAttribute' => ['chapter_id' => 'id']],
            ];
        }

        if ($this->scenario === 'bulkCreate') {
            return [
                [['spanish'], 'required', 'message' => 'Het veld {attribute} is verplicht'],
                [['chapter_id'], 'default', 'value' => null],
                [['chapter_id', 'created_at', 'updated_at'], 'integer'],
                [['dutch', 'spanish'], 'string', 'max' => 255],
                [['chapter_id'], 'exist', 'skipOnError' => true, 'targetClass' => Chapter::class, 'targetAttribute' => ['chapter_id' => 'id']],
            ];
        }

        if ($this->scenario === 'bulkTranslate') {
            // Untranslated list: dutch may stay empty, chapter may stay empty.
            return [
                [['chapter_id'], 'default', 'value' => null],
                [['chapter_id'], 'integer'],
                [['dutch', 'spanish'], 'string', 'max' => 255],
                [['chapter_id'], 'exist', 'skipOnError' => true, 'targetClass' => Chapter::class, 'targetAttribute' => ['chapter_id' => 'id']],
            ];
        }

        return [
            [['spanish', 'dutch'], 'required', 'message' => 'Het veld {attribute} is verplicht'],
            [['chapter_id'], 'default', 'value' => null],
            [['chapter_id', 'created_at', 'updated_at'], 'integer'],
            [['dutch', 'spanish'], 'string', 'max' => 255],
            [['chapter_id'], 'exist', 'skipOnError' => true, 'targetClass' => Chapter::class, 'targetAttribute' => ['chapter_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_INSERT => ['created_at', 'updated_at'],
                    ActiveRecord::EVENT_BEFORE_UPDATE => ['updated_at'],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'chapter_id' => 'Lijst',
            'spanish' => 'Spaans',
            'bulkText' => 'Spaans',
            'dutch' => 'Nederlands',
            'created_at' => 'Gemaakt op',
            'updated_at' => 'Gewijzigd op',
        ];
    }

    /**
     * Gets query for [[Chapter]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getChapter()
    {
        return $this->hasOne(Chapter::class, ['id' => 'chapter_id']);
    }

    /**
     * {@inheritdoc}
     */
    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios['bulkCreate'] = ['chapter_id', 'spanish', 'created_at', 'updated_at'];
        $scenarios['bulkForm'] = ['chapter_id', 'bulkText'];
        $scenarios['bulkTranslate'] = ['dutch', 'chapter_id'];
        return $scenarios;
    }

    /**
     * {@inheritdoc}
     */
    public function beforeValidate()
    {
        // Empty dropdown selection becomes NULL (list-less word).
        // NB: keep '0'/0 intact: WordSearch uses 0 as the "no list" filter value.
        if ($this->chapter_id === '') {
            $this->chapter_id = null;
        }

        return parent::beforeValidate();
    }

    /**
     * Query for words that may take part in practice:
     * only translated words (Dutch known). Untranslated words never practice.
     * @return \yii\db\ActiveQuery
     */
    public static function findTranslatable()
    {
        return self::find()
            ->andWhere(['not', ['dutch' => null]])
            ->andWhere(['not', ['dutch' => '']]);
    }

    /**
     * Display name of the list this word belongs to, or null when list-less.
     * @return string|null
     */
    public function getListLabel()
    {
        return $this->chapter ? $this->chapter->name : null;
    }

    /**
     * Gets query for [[WordStatistic]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getWordStatistic()
    {
        return $this->hasOne(WordStatistic::class, ['word_id' => 'id']);
    }

    /**
     * Returns the prompt side of this word for a practice direction.
     * @param bool $nl_to_sp true: prompt is Dutch, false: prompt is Spanish
     * @return string|null
     */
    public function getWordBasedOnDirection(bool $nl_to_sp = false)
    {
        return $nl_to_sp ? $this->dutch : $this->spanish;
    }

    /**
     * Normalizes an answer for lenient comparison: lowercased, accents
     * stripped, but ñ/Ñ kept intact. In Spanish ñ is a distinct letter,
     * not an accented n (año/year vs ano/anus), so it stays strict.
     * @param string|null $value
     * @return string
     */
    public static function normalizeAnswer($value)
    {
        $value = mb_strtolower(trim((string) $value));
        return strtr($value, [
            'á' => 'a', 'à' => 'a', 'ä' => 'a', 'â' => 'a',
            'é' => 'e', 'è' => 'e', 'ë' => 'e', 'ê' => 'e',
            'í' => 'i', 'ì' => 'i', 'ï' => 'i', 'î' => 'i',
            'ó' => 'o', 'ò' => 'o', 'ö' => 'o', 'ô' => 'o',
            'ú' => 'u', 'ù' => 'u', 'ü' => 'u', 'û' => 'u',
            'ý' => 'y', 'ÿ' => 'y',
        ]);
    }

    /**
     * Groups Spanish forms occurring in more than one row ("doubles"):
     * homonyms with different meanings as well as exact duplicates.
     * Grouping uses the accent-lenient form, so "pasion" and "pasión"
     * land in one group, while "ano" and "año" stay apart.
     * Sorted by group size (desc), then form (asc).
     * @return array normalized form => Word[] (each 2+ rows)
     */
    public static function findDuplicateGroups()
    {
        $groups = [];
        foreach (self::find()->with('chapter')->all() as $word) {
            $key = self::normalizeAnswer($word->spanish);
            if ($key === '') {
                continue;
            }
            $groups[$key][] = $word;
        }

        $groups = array_filter($groups, function ($group) {
            return count($group) > 1;
        });
        uasort($groups, function ($a, $b) {
            $bySize = count($b) <=> count($a);
            if ($bySize !== 0) {
                return $bySize;
            }
            return mb_strtolower($a[0]->spanish) <=> mb_strtolower($b[0]->spanish);
        });

        return $groups;
    }

    /**
     * All accepted answers for a practice prompt, homonym-aware.
     * Same form, different meanings (e.g. "camino": de weg / ik loop) live
     * in separate rows, so counterparts of all rows sharing the prompt count.
     * @param bool $nl_to_sp practice direction (true: prompt is Dutch)
     * @return string[] display versions, deduplicated, non-empty
     */
    public function getPracticeAnswers(bool $nl_to_sp): array
    {
        if ($nl_to_sp) {
            $values = self::find()->select(['spanish'])->where(['dutch' => $this->dutch])->column();
            $fallback = $this->spanish;
        } else {
            $values = self::find()->select(['dutch'])->where(['spanish' => $this->spanish])->column();
            $fallback = $this->dutch;
        }

        $answers = [];
        foreach ($values as $value) {
            foreach (explode(',', (string) $value) as $part) {
                $part = trim($part);
                if ($part !== '' && !in_array($part, $answers)) {
                    $answers[] = $part;
                }
            }
        }

        if (empty($answers)) {
            foreach (explode(',', (string) $fallback) as $part) {
                $part = trim($part);
                if ($part !== '' && !in_array($part, $answers)) {
                    $answers[] = $part;
                }
            }
        }

        return $answers;
    }

    /**
     * Number of word rows sharing this practice prompt (meanings count).
     * @param bool $nl_to_sp practice direction (true: prompt is Dutch)
     */
    public function countPracticeMeanings(bool $nl_to_sp): int
    {
        if ($nl_to_sp) {
            return (int) self::find()->where(['dutch' => $this->dutch])->count();
        }
        return (int) self::find()->where(['spanish' => $this->spanish])->count();
    }

}
