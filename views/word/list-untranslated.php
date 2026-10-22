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

$chapterList = Chapter::find()
    ->select(['name'])
    ->orderBy(['name' => SORT_ASC])
    ->indexBy('id')
    ->column();

$total = $dataProvider->getTotalCount();
$returnUrl = Yii::$app->request->url;
$suggestions = Word::suggestLists($dataProvider->getModels());
?>
<div class="word-index">

    <h1>
        <?= Html::encode($this->title) ?>
    </h1>

    <p class="text-muted">
        Nog <?= $total ?> onvertaald <?= $total === 1 ? 'woord' : 'woorden' ?>.
        Vul Nederlands en/of lijst in en bewaar alles in één keer (geldt voor deze pagina).
    </p>

    <?= Html::beginForm(['word/bulk-translate'], 'post') ?>
    <?= Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->getCsrfToken()) ?>
    <?= Html::hiddenInput('returnUrl', $returnUrl) ?>

    <p>
        <?= Html::submitButton('Alles opslaan', ['class' => 'btn btn-success']) ?>
    </p>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            [
                'attribute' => 'chapter_id',
                'label' => 'Lijst',
                'enableSorting' => false,
                'filter' => Html::activeDropDownList(
                    $searchModel,
                    'chapter_id',
                    [0 => '— Zonder lijst —'] + Chapter::find()
                        ->select(['name'])
                        ->orderBy(['name' => SORT_ASC])
                        ->indexBy('id')
                        ->column(),
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
                        $html .= '<div class="form-check m-0">';
                        $html .= Html::checkbox(
                            "Translation[{$model->id}][chapter_ids][]",
                            $checked,
                            ['value' => $cid, 'class' => 'form-check-input', 'id' => "ch-{$model->id}-{$cid}"]
                        );
                        $html .= Html::label(Html::encode($name), "ch-{$model->id}-{$cid}", ['class' => 'form-check-label small']);
                        $html .= '</div>';
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
