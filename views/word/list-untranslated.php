<?php

use app\models\Chapter;
use app\models\Word;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\ActionColumn;
use yii\grid\GridView;

/** @var yii\web\View $this */
/** @var app\models\WordSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */

$this->title = 'Onvertaalde Woorden';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="word-index">

    <h1>
        <?= Html::encode($this->title) ?>
    </h1>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            // ['class' => 'yii\grid\SerialColumn'],

            'id',
            [
                'attribute' => 'chapter_id',
                'label' => 'Hoofdstuk',
                'format' => 'raw',
                'value' => function ($model) {
                    /** @var Word $model */
                    if (!$model->chapter) {
                        return '<span class="text-muted">—</span>';
                    }
                    return Html::a(
                        Html::encode($model->chapterLabel),
                        Url::to(['word/index-by-chapter', 'chapter_id' => $model->chapter_id])
                    );
                },
                'filter' => Html::activeDropDownList(
                    $searchModel,
                    'chapter_id',
                    Chapter::find()
                        ->select(['CONCAT(number, " - ", name) AS chapter_label'])
                        ->indexBy('id')
                        ->column(),
                    ['prompt' => 'Alle', 'class' => 'form-control']
                ),
            ],
            'spanish',
            [
                'attribute' => 'dutch',
                'label' => 'Nederlands',
                'content' => function ($model) {
                    return Html::beginForm(['word/update', 'id' => $model->id], 'post') .
                        Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->getCsrfToken()) .
                        Html::hiddenInput('returnUrl', 'list-untranslated') .
                        Html::textInput('Word[dutch]', $model->dutch, [
                            'class' => 'form-control',
                            'style' => 'min-width: 180px;'
                        ]) .
                        Html::submitButton('Opslaan', ['class' => 'btn btn-sm btn-success', 'style' => 'margin-top: 6px;']) .
                        Html::endForm();
                },
            ],
            [
                'attribute' => 'created_at',
                'format' => ['datetime', 'php:d-m-Y H:i:s']
            ],
            [
                'attribute' => 'updated_at',
                'format' => ['datetime', 'php:d-m-Y H:i:s']
            ],
            [
                'class' => ActionColumn::class,
                'urlCreator' => function ($action, Word $model, $key, $index, $column) {
                        return Url::toRoute([$action, 'id' => $model->id]);
                    }
            ],
        ],
    ]); ?>


</div>