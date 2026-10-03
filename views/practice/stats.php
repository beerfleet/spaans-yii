<!-- views/practice/stats.php -->

<?php

use yii\helpers\Html;
use yii\grid\GridView;
use app\models\WordStatistic;
use app\models\Word;

/* @var $this yii\web\View */
/* @var $searchModel WordStatistic */
/* @var $stats WordStatistic[] */
/* @var $word Word */

$stats = $stats ?? [];

$this->title = 'Statistieken';
$this->params['breadcrumbs'][] = $this->title;
?>

<h1><?= Html::encode($this->title) ?></h1>

<?= GridView::widget([
    'dataProvider' => new \yii\data\ArrayDataProvider([
        'allModels' => $stats,
        'pagination' => false,
    ]),
    'columns' => [
        [
            'label' => 'Word',
            'value' => function ($stat) {
                $word = $stat->word;
                return $word ? $word->getWordBasedOnDirection($stat->nl_to_sp) : 'N/A';
            },
        ],
        'correct_count',
        'incorrect_count',
    ],
]); ?>