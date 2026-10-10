<?php

namespace app\models;

use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use app\models\WordStatistic;
use Yii;

/**
 * This is the model class for table "word".
 *
 * @property int $id
 * @property string|null $dutch
 * @property string $spanish
 * @property int $created_at
 * @property int $updated_at
 *
 * @property Chapter[] $chapters M-N lists via chapter_word
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
     * Assigned list ids. Mass-assigned by forms (checkbox lists, multi
     * selects); null means untouched, [] means explicitly list-less.
     * @var int[]|null
     */
    private $_chapterIds;

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
        $listRules = [
            [['chapterIds'], 'each', 'rule' => ['integer']],
            [['chapterIds'], 'exist', 'allowArray' => true, 'skipOnError' => true, 'targetClass' => Chapter::class, 'targetAttribute' => 'id'],
        ];

        if ($this->scenario === 'bulkForm') {
            return array_merge([
                [['bulkText'], 'required', 'message' => 'Het veld {attribute} is verplicht'],
                [['bulkText'], 'string', 'max' => 20000],
            ], $listRules);
        }

        if ($this->scenario === 'bulkCreate') {
            return array_merge([
                [['spanish'], 'required', 'message' => 'Het veld {attribute} is verplicht'],
                [['created_at', 'updated_at'], 'integer'],
                [['dutch', 'spanish'], 'string', 'max' => 255],
            ], $listRules);
        }

        if ($this->scenario === 'bulkTranslate') {
            // Untranslated list: dutch may stay empty, lists may stay empty.
            return array_merge([
                [['dutch', 'spanish'], 'string', 'max' => 255],
            ], $listRules);
        }

        return array_merge([
            [['spanish', 'dutch'], 'required', 'message' => 'Het veld {attribute} is verplicht'],
            [['created_at', 'updated_at'], 'integer'],
            [['dutch', 'spanish'], 'string', 'max' => 255],
        ], $listRules);
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
            'chapterIds' => 'Lijsten',
            'spanish' => 'Spaans',
            'bulkText' => 'Spaans',
            'dutch' => 'Nederlands',
            'created_at' => 'Gemaakt op',
            'updated_at' => 'Gewijzigd op',
        ];
    }

    /**
     * Assigned list ids. Lazy-loads the current junction rows on first read
     * ([] for new records); the setter normalizes form input (drops the
     * hidden "" Yii renders for empty checkbox lists).
     * @return int[]
     */
    public function getChapterIds()
    {
        if ($this->_chapterIds === null) {
            if ($this->isNewRecord) {
                $this->_chapterIds = [];
            } elseif ($this->isRelationPopulated('chapters')) {
                $ids = [];
                foreach ($this->chapters as $chapter) {
                    $ids[] = (int) $chapter->id;
                }
                $this->_chapterIds = $ids;
            } else {
                $this->_chapterIds = array_map('intval', $this->getChapters()->select('{{%chapter}}.[[id]]')->column());
            }
        }
        return $this->_chapterIds;
    }

    /**
     * @param int|int[]|string|null $value
     */
    public function setChapterIds($value)
    {
        if ($value === null || $value === '') {
            $this->_chapterIds = [];
            return;
        }
        $ids = [];
        foreach ((array) $value as $v) {
            if ($v === '' || $v === null) {
                continue;
            }
            $ids[] = (int) $v;
        }
        $this->_chapterIds = array_values(array_unique($ids));
    }

    /**
     * Many-to-many lists via the chapter_word junction.
     * @return \yii\db\ActiveQuery
     */
    public function getChapters()
    {
        return $this->hasMany(Chapter::class, ['id' => 'chapter_id'])
            ->viaTable('{{%chapter_word}}', ['word_id' => 'id']);
    }

    /**
     * {@inheritdoc}
     */
    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);

        $ids = $this->getChapterIds();
        Yii::$app->db->createCommand()
            ->delete('{{%chapter_word}}', ['word_id' => $this->id])
            ->execute();
        foreach ($ids as $chapterId) {
            Yii::$app->db->createCommand()->insert('{{%chapter_word}}', [
                'word_id' => $this->id,
                'chapter_id' => $chapterId,
            ])->execute();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios['bulkCreate'] = ['chapterIds', 'spanish', 'created_at', 'updated_at'];
        $scenarios['bulkForm'] = ['chapterIds', 'bulkText'];
        $scenarios['bulkTranslate'] = ['dutch', 'chapterIds'];
        return $scenarios;
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
     * Display names of all lists this word belongs to, or null when list-less.
     * @return string|null
     */
    public function getListsText()
    {
        if (empty($this->chapters)) {
            return null;
        }
        $names = [];
        foreach ($this->chapters as $chapter) {
            $names[] = $chapter->name;
        }
        return implode(', ', $names);
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
     * Picks one random variant of the practice prompt. Rows may hold
     * comma-separated alternatives (e.g. "feliz, contento, contenta");
     * quizzing a single variant keeps the prompt readable instead of
     * showing (and giving away) the whole list. Answer acceptance is
     * unaffected: every counterpart still counts.
     * @param bool $nl_to_sp true: prompt is Dutch, false: prompt is Spanish
     * @return string
     */
    public function pickPromptVariant(bool $nl_to_sp)
    {
        $full = (string) $this->getWordBasedOnDirection($nl_to_sp);
        $parts = [];
        foreach (explode(',', $full) as $part) {
            $part = trim($part);
            if ($part !== '') {
                $parts[] = $part;
            }
        }
        if (empty($parts)) {
            return $full;
        }
        return $parts[array_rand($parts)];
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
        foreach (self::find()->all() as $word) {
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
     * Suggests lists for list-less words, based on lists where the same
     * Spanish form (accent-lenient) or the same Dutch translation already
     * lives. Helps decide where an unassigned word belongs.
     * Two queries total, regardless of page size.
     * @param Word[] $words typically the current grid page models
     * @return array word id => list names (max 3 each)
     */
    public static function suggestLists(array $words)
    {
        $ids = [];
        foreach ($words as $word) {
            $ids[] = (int) $word->id;
        }
        if (empty($ids)) {
            return [];
        }
        // One query for all linked words on the page (avoids N+1).
        $linked = array_map('intval', (new \yii\db\Query())
            ->select(['word_id'])
            ->distinct()
            ->from('{{%chapter_word}}')
            ->where(['word_id' => $ids])
            ->column());

        $needles = [];
        foreach ($words as $word) {
            if (in_array((int) $word->id, $linked, true)) {
                continue;
            }
            $needles[$word->id] = $word;
        }
        if (empty($needles)) {
            return [];
        }

        $spanishForms = [];
        $dutchValues = [];
        foreach ($needles as $word) {
            $spanishForms[] = $word->spanish;
            if (trim((string) $word->dutch) !== '') {
                $dutchValues[] = $word->dutch;
            }
        }
        $conditions = ['or'];
        if (!empty($spanishForms)) {
            $conditions[] = ['spanish' => array_values(array_unique($spanishForms))];
        }
        if (!empty($dutchValues)) {
            $conditions[] = ['dutch' => array_values(array_unique($dutchValues))];
        }

        $siblings = self::find()
            ->where($conditions)
            ->with('chapters')
            ->all();

        $bySpanish = [];
        $byDutch = [];
        foreach ($siblings as $sibling) {
            if (empty($sibling->chapters)) {
                continue;
            }
            foreach ($sibling->chapters as $chapter) {
                $bySpanish[self::normalizeAnswer($sibling->spanish)][] = $chapter->name;
                $byDutch[mb_strtolower(trim((string) $sibling->dutch))][] = $chapter->name;
            }
        }

        $suggestions = [];
        foreach ($needles as $id => $word) {
            $names = array_merge(
                $bySpanish[self::normalizeAnswer($word->spanish)] ?? [],
                $byDutch[mb_strtolower(trim((string) $word->dutch))] ?? []
            );
            $names = array_values(array_unique($names));
            if (!empty($names)) {
                $suggestions[$id] = array_slice($names, 0, 3);
            }
        }

        return $suggestions;
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
