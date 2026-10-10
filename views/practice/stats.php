<?php

use app\models\WordStatistic;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\GridView;

/** @var yii\web\View $this */
/** @var yii\data\ActiveDataProvider $dataProvider */
/** @var string|int $chapterId selected list filter ('' = all, 0 = no list) */
/** @var array $chapterList id => name */
/** @var string $dir selected direction filter ('' = both, '1' = NL→ES, '0' = ES→NL) */
/** @var array $summary answers, good, words, percent */

$this->title = 'Statistieken';
$this->params['breadcrumbs'][] = $this->title;

$summary = $summary ?? ['answers' => 0, 'good' => 0, 'words' => 0, 'percent' => null];
?>

<h1><?= Html::encode($this->title) ?></h1>

<p class="text-muted">
    <?= $summary['answers'] ?> antwoorden ·
    <?= $summary['percent'] === null ? 'nog geen score' : $summary['percent'] . '% goed' ?> ·
    <?= $summary['words'] ?> <?= $summary['words'] === 1 ? 'woord' : 'woorden' ?> geoefend
</p>

<?= Html::beginForm(['stats'], 'get', ['class' => 'row row-cols-auto g-2 align-items-center mb-3']) ?>
<?= Html::dropDownList(
    'chapter_id',
    (string) ($chapterId ?? ''),
    [0 => '— Zonder lijst —'] + ($chapterList ?? []),
    ['prompt' => 'Alle lijsten', 'class' => 'form-control']
) ?>
<?= Html::dropDownList(
    'dir',
    (string) ($dir ?? ''),
    ['1' => 'NL → ES', '0' => 'ES → NL'],
    ['prompt' => 'Beide richtingen', 'class' => 'form-control']
) ?>
<?= Html::submitButton('Filteren', ['class' => 'btn btn-primary']) ?>
<?= Html::endForm() ?>

<div class="table-responsive">
<?= GridView::widget([
    'dataProvider' => $dataProvider,
    'columns' => [
        [
            'attribute' => 'spanish',
            'label' => 'Spaans',
            'format' => 'raw',
            'value' => function ($stat) {
                /** @var WordStatistic $stat */
                if ($stat->word === null) {
                    return '—';
                }
                return Html::a(Html::encode($stat->word->spanish), ['word/view', 'id' => $stat->word->id]);
            },
        ],
        [
            'attribute' => 'dutch',
            'label' => 'Nederlands',
            'value' => function ($stat) {
                /** @var WordStatistic $stat */
                return $stat->word && $stat->word->dutch !== null && $stat->word->dutch !== ''
                    ? $stat->word->dutch
                    : '—';
            },
        ],
        [
            'attribute' => 'nl_to_sp',
            'label' => 'Richting',
            'value' => function ($stat) {
                /** @var WordStatistic $stat */
                return $stat->nl_to_sp ? 'NL → ES' : 'ES → NL';
            },
        ],
        'correct_count:integer:Goed',
        'incorrect_count:integer:Fout',
        [
            'attribute' => 'success',
            'label' => 'Succes',
            'format' => 'raw',
            'value' => function ($stat) {
                /** @var WordStatistic $stat */
                $total = $stat->correct_count + $stat->incorrect_count;
                if ($total <= 0) {
                    return '<span class="text-muted">—</span>';
                }
                $pct = round(100 * $stat->correct_count / $total);
                $bar = $pct >= 80 ? 'bg-success' : ($pct >= 50 ? 'bg-warning' : 'bg-danger');
                return '<div class="progress" style="min-width: 90px;" role="progressbar" aria-valuenow="' . $pct . '" aria-valuemin="0" aria-valuemax="100" aria-label="Succespercentage">'
                    . '<div class="progress-bar ' . $bar . '" style="width: ' . $pct . '%;">' . $pct . '%</div>'
                    . '</div>';
            },
        ],
        [
            'attribute' => 'last_practiced_at',
            'label' => 'Laatst',
            'format' => ['datetime', 'php:d-m-Y H:i'],
            'value' => function ($stat) {
                /** @var WordStatistic $stat */
                return $stat->last_practiced_at ?: null;
            },
        ],
        [
            'class' => 'yii\grid\ActionColumn',
            'template' => '{reset}',
            'buttons' => [
                'reset' => function ($url, $stat) use ($chapterId, $dir) {
                    /** @var WordStatistic $stat */
                    return Html::a('Opnieuw', Url::to(['reset-stat', 'id' => $stat->id]), [
                        'title' => 'Statistieken wissen, dit woord begint opnieuw',
                        'class' => 'btn btn-sm btn-outline-secondary',
                        'data' => [
                            'confirm' => 'Statistieken voor dit woord wissen?',
                            'method' => 'post',
                            'params' => [
                                'chapter_id' => (string) ($chapterId ?? ''),
                                'dir' => (string) ($dir ?? ''),
                            ],
                        ],
                    ]);
                },
            ],
        ],
    ],
]); ?>
</div>
