<?php

use app\models\Chapter;
use app\models\Word;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\ActionColumn;
use yii\grid\CheckboxColumn;
use yii\grid\GridView;

/** @var yii\web\View $this */
/** @var app\models\Chapter $model */
/** @var app\models\WordSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */
/** @var array $chapterList */
/** @var string $returnUrl */

$this->title = 'Woorden beheren: ' . $model->name;
$this->params['breadcrumbs'][] = ['label' => 'Lijsten', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->name, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Beheren';

$chapterList = $chapterList ?? (Chapter::find()->select(['name'])->orderBy(['name' => SORT_ASC])->indexBy('id')->column());
?>
<div class="chapter-manage-words">

    <h1><?= Html::encode($this->title) ?></h1>

    <p class="text-muted">
        Vink woorden aan en klik "Toevoegen aan deze lijst". Woorden die al in deze lijst zitten zijn verborgen.
    </p>

    <?= Html::beginForm(['word/bulk-assign'], 'post') ?>
    <?= Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->getCsrfToken()) ?>
    <?= Html::hiddenInput('assign_list', $model->id) ?>
    <?= Html::hiddenInput('returnUrl', Url::to(['chapter/manage-words', 'id' => $model->id])) ?>

    <p class="d-flex gap-2 flex-wrap">
        <?= Html::submitButton('Selectie toevoegen aan deze lijst', ['class' => 'btn btn-success']) ?>
        <?= Html::a('Terug naar lijst', ['view', 'id' => $model->id], ['class' => 'btn btn-outline-secondary']) ?>
    </p>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            [
                'class' => CheckboxColumn::class,
                'name' => 'assign_ids',
            ],
            'spanish',
            [
                'attribute' => 'dutch',
                'label' => 'Nederlands',
            ],
            [
                'attribute' => 'chapter_id',
                'label' => 'Huidige lijsten',
                'enableSorting' => false,
                'filter' => Html::activeDropDownList(
                    $searchModel,
                    'chapter_id',
                    [0 => '— Zonder lijst —'] + $chapterList,
                    ['prompt' => 'Alle', 'class' => 'form-control']
                ),
                'content' => function ($model) use ($chapterList) {
                    /** @var Word $model */
                    $names = [];
                    foreach ($model->getChapterIds() as $cid) {
                        if (isset($chapterList[$cid])) {
                            $names[] = $chapterList[$cid];
                        }
                    }
                    return Html::encode(implode(', ', $names));
                },
            ],
            [
                'class' => ActionColumn::class,
                'template' => '{view}',
                'urlCreator' => function ($action, Word $model, $key, $index, $column) {
                    return Url::toRoute(['word/view', 'id' => $model->id]);
                },
            ],
        ],
    ]); ?>

    <?= Html::endForm() ?>

</div>
