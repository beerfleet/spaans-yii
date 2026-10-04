<?php

namespace app\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use app\models\WordStatistic;

/**
 * This is the model class for table "word".
 *
 * @property int $id
 * @property int|null $chapter_id
 * @property string $dutch
 * @property string $spanish
 * @property int $created_at
 * @property int $updated_at
 *
 * @property Chapter $chapter
 */
class Word extends ActiveRecord
{


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
        if ($this->scenario === 'bulkCreate') {
            return [
                [['spanish'], 'required', 'message' => 'Het veld {attribute} is verplicht'],
                [['chapter_id'], 'default', 'value' => null],
                [['chapter_id', 'created_at', 'updated_at'], 'integer'],
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

    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_INSERT => ['created_at', 'updated_at'],
                    ActiveRecord::EVENT_BEFORE_UPDATE => ['updated_at'],
                ],
                // Gebruik een database-expressie zoals NOW() als je DATETIME/TIMESTAMP velden gebruikt i.p.v. Unix timestamps
                // 'value' => new \yii\db\Expression('NOW()'),
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
            'chapter_id' => 'Hoofdstuk',
            'spanish' => 'Spaans',
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

    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios['bulkCreate'] = ['chapter_id', 'spanish', 'created_at', 'updated_at'];
        return $scenarios;
    }

    public function validate($attributeNames = null, $clearErrors = true)
    {
        Yii::debug('Current scenario: ' . $this->scenario);

        return parent::validate($attributeNames, $clearErrors);
    }

    public function beforeValidate()
    {
        if ($this->chapter_id === '' || $this->chapter_id === 0 || $this->chapter_id === '0') {
            $this->chapter_id = null;
        }

        return parent::beforeValidate();
    }

    public function getChapterNumber()
    {
        return $this->chapter ? $this->chapter->number : null;
    }

    public function getChapterLabel()
    {
        if (!$this->chapter) {
            return null;
        }

        $number = $this->chapter->number;
        $name = $this->chapter->name;

        if ($number === null || $number === '') {
            return $name;
        }

        return $number . ' - ' . $name;
    }

    public function getWordStatistic()
    {
        return $this->hasOne(WordStatistic::class, ['word_id' => 'id']);
    }

    public function getWordBasedOnDirection(bool $nl_to_sp = false)
    {
        return $nl_to_sp ? $this->dutch : $this->spanish;
    }

}
