<?php

namespace app\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "chapter".
 *
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property int $created_at
 * @property int $updated_at
 *
 * @property Word[] $words
 */
class Chapter extends ActiveRecord
{


    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'chapter';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['description'], 'default', 'value' => null],
            //[['name', 'created_at', 'updated_at'], 'required'],
            [['name'], 'required'],
            [['number'], 'integer'],
            [['description'], 'string'],
            [['created_at', 'updated_at'], 'integer'],
            [['name'], 'string', 'max' => 255],
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
            'name' => 'Naam',
            'number' => 'Hoofdstuk #',
            'description' => 'Omschrijving',
            'created_at' => 'Gemaakt Op',
            'updated_at' => 'Gewijzigd Op',
        ];
    }

    /**
     * Gets query for [[Words]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getWords()
    {
        return $this->hasMany(Word::class, ['chapter_id' => 'id']);
    }

}
