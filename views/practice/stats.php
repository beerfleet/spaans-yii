<?php

use app\models\WordStatistic;
use yii\helpers\Html;
use yii\grid\GridView;

/** @var yii\web\View $this */
/** @var yii\data\ActiveDataProvider $dataProvider */
/** @var string|int $chapterId selected list filter ('' = all, 0 = no list) */
/** @var array $chapterList id => name */

$this->title = 'Statistieken';
$this->params['breadcrumbs'][] = $this->title;
?>

<h1><?= Html::encode($this->title) ?></h1>

<?= Html::beginForm(['stats'], 'get', ['class' => 'row row-cols-auto g-2 align-items-center mb-3']) ?>
<?= Html::dropDownList(
    'chapter_id',
    (string) ($chapterId ?? ''),
    [0 => '— Zonder lijst —'] + ($chapterList ?? []),
    ['prompt' => 'Alle lijsten', 'class' => 'form-control']
) ?>
<?= Html::submitButton('Filteren', ['class' => 'btn btn-primary']) ?>
<?= Html::endForm() ?>

<?= GridView::widget([
    'dataProvider' => $dataProvider,
    'columns' => [
        [
            'attribute' => 'spanish',
            'label' => 'Spaans',
            'value' => function ($stat) {
                /** @var WordStatistic $stat */
                return $stat->word ? $stat->word->spanish : '—';
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
            'label' => 'Succes %',
            'value' => function ($stat) {
                /** @var WordStatistic $stat */
                $total = $stat->correct_count + $stat->incorrect_count;
                return $total > 0 ? round(100 * $stat->correct_count / $total) . '%' : '—';
            },
        ],
    ],
]); ?>
