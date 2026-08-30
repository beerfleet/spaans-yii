<?php

use app\models\Chapter;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\ActionColumn;
use yii\grid\GridView;

/** @var yii\web\View $this */
/** @var app\models\ChapterSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */

$this->title = 'Hoofdstukken';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="chapter-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Nieuw hoofdstuk', ['create'], ['class' => 'btn btn-success']) ?>
    </p>

    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'summary' => 'Toont {begin}-{end} van de {count} items.',
        'columns' => [
            // ['class' => 'yii\grid\SerialColumn'],

            [ // nummer kolom wordt een link attribuut
                'attribute' => 'number',
                'format' => 'raw',
                'value' => 'numberLink', // Chapter.getNumberLink() call
            ],
            'name',
            'description:ntext',
            [
                'attribute' => 'created_at',
                'format' => ['datetime', 'php:d-m-Y H:i:s']
            ],
            [
                'attribute' => 'updated_at',
                'format' => ['datetime', 'php:d-m-Y H:i:s']
            ],
            [
                'attribute' => 'Aantal woorden',
                'format' => 'raw',
                'value' => function($model) {
                    return $model->countWordsOfChapter($model->id);
                },
            ],
            [
                'class' => ActionColumn::class,
                'urlCreator' => function ($action, Chapter $model, $key, $index, $column) {
                        return Url::toRoute([$action, 'id' => $model->id]);
                    }
            ],
        ],
    ]); ?>

</div>