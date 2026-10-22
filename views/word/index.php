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
        <?php if (isset($chapter_id)) : ?>
            <?= Html::a('Woorden beheren', ['chapter/manage-words', 'id' => $chapter_id], ['class' => 'btn btn-outline-primary']) ?>
        <?php endif; ?>
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
                'enableSorting' => false,
                'filter' => Html::activeDropDownList(
                    $searchModel,
                    'chapter_id',
                    [0 => '— Zonder lijst —'] + $chapterList,
                    ['prompt' => 'Alle', 'class' => 'form-control']
                ),
                'content' => function ($model) use ($chapterList, $suggestions) {
                    /** @var Word $model */
                    $selected = $model->getChapterIds();
                    $badges = '';
                    foreach ($selected as $cid) {
                        if (isset($chapterList[$cid])) {
                            $badges .= '<span class="badge text-bg-secondary me-1 mb-1">' . Html::encode($chapterList[$cid]) . '</span>';
                        }
                    }
                    $html = '';
                    if ($badges !== '') {
                        $html .= '<div class="mb-1">' . $badges . '</div>';
                    }
                    $html .= '<details class="small">';
                    $html .= '<summary class="text-muted" style="cursor:pointer;">Lijsten bewerken' . (count($selected) ? ' (' . count($selected) . ')' : '') . '</summary>';
                    $html .= '<div class="mt-1" style="max-height:140px; overflow:auto;">';
                    foreach ($chapterList as $cid => $name) {
                        $checked = in_array((int) $cid, array_map('intval', $selected), true);
                        $html .= '<div class="form-check form-check-inline m-0">';
                        $html .= Html::checkbox(
                            "Translation[{$model->id}][chapter_ids][]",
                            $checked,
                            ['value' => $cid, 'class' => 'form-check-input', 'id' => "ch-{$model->id}-{$cid}"]
                        );
                        $html .= Html::label(Html::encode($name), "ch-{$model->id}-{$cid}", ['class' => 'form-check-label small']);
                        $html .= '</div><br>';
                    }
                    $html .= '</div>';
                    $html .= '</details>';
                    if (empty($selected) && isset($suggestions[$model->id])) {
                        $html .= '<div class="small text-muted mt-1">ook in: ' . Html::encode(implode(', ', $suggestions[$model->id])) . '</div>';
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
