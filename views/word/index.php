<?php

use app\models\Chapter;
use app\models\Word;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\ActionColumn;
use yii\grid\CheckboxColumn;
use yii\grid\GridView;

/** @var yii\web\View $this */
/** @var app\models\WordSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */
/** @var int $chapter_id */
/** @var string $chapter_name */

$this->title = 'Woorden';
$this->params['breadcrumbs'][] = $this->title;

$chapterList = Chapter::find()
    ->select(['name'])
    ->orderBy(['name' => SORT_ASC])
    ->indexBy('id')
    ->column();

$returnUrl = Yii::$app->request->url;
$suggestions = Word::suggestLists($dataProvider->getModels());
?>
<div class="word-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <?php if (isset($chapter_id)) : ?>
        <h2>Lijst: <?= Html::encode($chapter_name ?? '') ?></h2>
    <?php endif; ?>

    <p>
        <?= Html::a('Nieuw Woord', ['create'], ['class' => 'btn btn-success']) ?>
        <?= Html::a('Meerdere woorden', ['create-multiple'], ['class' => 'btn btn-success']) ?>
    </p>

    <p class="text-muted">
        Pas Nederlands en/of lijst per regel aan en bewaar alles in één keer (geldt voor deze pagina).
        Spaans wijzig je per woord via het potloodje.
        Vink woorden aan om ze in één keer aan een lijst toe te wijzen.
    </p>

    <?= Html::beginForm(['word/bulk-translate'], 'post') ?>
    <?= Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->getCsrfToken()) ?>
    <?= Html::hiddenInput('returnUrl', $returnUrl) ?>

    <p class="d-flex gap-2 flex-wrap">
        <?= Html::submitButton('Alles opslaan', ['class' => 'btn btn-success']) ?>
    </p>

    <p class="d-flex gap-2 align-items-center flex-wrap">
        <?= Html::dropDownList(
            'assign_list',
            '',
            ['none' => '— Geen lijst —'] + $chapterList,
            ['prompt' => 'Kies een lijst…', 'class' => 'form-control', 'style' => 'max-width: 240px;']
        ) ?>
        <?= Html::submitButton('Selectie toewijzen', [
            'class' => 'btn btn-outline-primary',
            'formaction' => Url::to(['word/bulk-assign']),
        ]) ?>
    </p>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            // ['class' => 'yii\grid\SerialColumn'],

            // 'id',
            [
                'class' => CheckboxColumn::class,
                'name' => 'assign_ids',
            ],
            [
                'attribute' => 'chapter_id',
                'label' => 'Lijst',
                'filter' => Html::activeDropDownList(
                    $searchModel,
                    'chapter_id',
                    [0 => '— Zonder lijst —'] + $chapterList,
                    ['prompt' => 'Alle', 'class' => 'form-control']
                ),
                'content' => function ($model) use ($chapterList, $suggestions) {
                    /** @var Word $model */
                    $html = Html::dropDownList(
                        "Translation[{$model->id}][chapter_id]",
                        $model->chapter_id,
                        $chapterList,
                        ['prompt' => '—', 'class' => 'form-control form-control-sm']
                    );
                    if ($model->chapter_id === null && isset($suggestions[$model->id])) {
                        $html .= '<div class="small text-muted">ook in: ' . Html::encode(implode(', ', $suggestions[$model->id])) . '</div>';
                    }
                    return $html;
                },
            ],
            'spanish',
            [
                'attribute' => 'dutch',
                'label' => 'Nederlands',
                'content' => function ($model, $key, $index) {
                    /** @var Word $model */
                    return Html::textInput(
                        "Translation[{$model->id}][dutch]",
                        $model->dutch,
                        [
                            'class' => 'form-control translation-input',
                            'autofocus' => $index === 0,
                        ]
                    );
                },
            ],
            [
                'class' => ActionColumn::class,
                'urlCreator' => function ($action, Word $model, $key, $index, $column) {
                        return Url::toRoute([$action, 'id' => $model->id]);
                    }
            ],
        ],
    ]); ?>

    <p>
        <?= Html::submitButton('Alles opslaan', ['class' => 'btn btn-success']) ?>
    </p>

    <?= Html::endForm() ?>


</div>
