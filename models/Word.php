<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "word".
 *
 * @property int $id
 * @property int $chapter_id
 * @property string $dutch
 * @property string $spanish
 * @property int $created_at
 * @property int $updated_at
 *
 * @property Chapter $chapter
 */
class Word extends \yii\db\ActiveRecord
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
        return [
            [['chapter_id', 'dutch', 'spanish', 'created_at', 'updated_at'], 'required'],
            [['chapter_id', 'created_at', 'updated_at'], 'integer'],
            [['dutch', 'spanish'], 'string', 'max' => 255],
            [['chapter_id'], 'exist', 'skipOnError' => true, 'targetClass' => Chapter::class, 'targetAttribute' => ['chapter_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'chapter_id' => 'Chapter ID',
            'dutch' => 'Dutch',
            'spanish' => 'Spanish',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
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

}
