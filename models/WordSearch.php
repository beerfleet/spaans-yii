<?php

namespace app\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\Word;

/**
 * WordSearch represents the model behind the search form of `app\models\Word`.
 */
class WordSearch extends Word
{
    /**
     * List filter value from the grid: '' = all, '0' = no list, id = one list.
     * Declared here because Word no longer has a chapter_id column.
     * @var string|int|null
     */
    public $chapter_id;

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'created_at', 'updated_at'], 'integer'],
            [['chapter_id'], 'integer'],
            [['dutch', 'spanish'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    /**
     * Builds an EXISTS subquery for the junction, correlated to the outer
     * word query and optionally pinned to specific lists.
     * @param int|int[]|null $chapterIds null = any link, int(-array) = these lists
     * @return \yii\db\Query
     */
    protected static function junctionExists($chapterIds)
    {
        $sub = (new \yii\db\Query())
            ->select(['cw.word_id'])
            ->from(['cw' => '{{%chapter_word}}'])
            ->where('cw.word_id = {{%word}}.[[id]]');
        if ($chapterIds !== null) {
            $sub->andWhere(['cw.chapter_id' => $chapterIds]);
        }
        return $sub;
    }

    /**
     * Applies the list filter via the chapter_word junction.
     * Filter value 0 means "no list" (no junction rows), so list-less
     * words can be found too.
     * @param \yii\db\ActiveQuery $query
     */
    protected function applyChapterFilter($query)
    {
        if ((string) $this->chapter_id === '0') {
            $query->andWhere(['not exists', self::junctionExists(null)]);
        } elseif ($this->chapter_id !== '' && $this->chapter_id !== null) {
            $query->andWhere(['exists', self::junctionExists((int) $this->chapter_id)]);
        }
    }

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     * @param string|null $formName Form name to be used into `->load()` method.
     *
     * @return ActiveDataProvider
     */
    public function search($params, $formName = null)
    {
        $query = Word::find()->with('chapters');

        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        $this->load($params, $formName);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        // grid filtering conditions
        $query->andFilterWhere([
            'id' => $this->id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ]);
        $this->applyChapterFilter($query);

        $query->andFilterWhere(['like', 'dutch', $this->dutch])
            ->andFilterWhere(['like', 'spanish', $this->spanish]);

        return $dataProvider;
    }

    public function searchUntranslated($params, $formName = null)
    {
        $query = Word::find()->where(['or', ['dutch' => null], ['dutch' => '']])->with('chapters');

        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        $this->load($params, $formName);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        // grid filtering conditions
        $query->andFilterWhere([
            'id' => $this->id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ]);
        $this->applyChapterFilter($query);

        $query->andFilterWhere(['like', 'spanish', $this->spanish]);

        return $dataProvider;
    }

    /**
     * Creates data provider instance with search query applied for a specific chapter
     *
     * @param int $chapter_id Chapter ID
     * @param array $params
     * @param string|null $formName Form name to be used into `->load()` method.
     *
     * @return ActiveDataProvider
     */
    public function searchByChapter($chapter_id, $params, $formName = null)
    {
        $query = Word::find()->where(['exists', self::junctionExists((int) $chapter_id)])->with('chapters');

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        $this->load($params, $formName);

        if (!$this->validate()) {
            return $dataProvider;
        }

        // grid filtering conditions
        $query->andFilterWhere([
            'id' => $this->id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ]);
        $this->applyChapterFilter($query);

        $query->andFilterWhere(['like', 'dutch', $this->dutch])
            ->andFilterWhere(['like', 'spanish', $this->spanish]);

        return $dataProvider;
    }

    public function getChapterName($chapter_id) {
        $chapter = Chapter::findOne($chapter_id);
        return $chapter ? $chapter->name : null;
    }
}
