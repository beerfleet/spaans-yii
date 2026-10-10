<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/** @var yii\web\View $this */
/** @var app\models\Chapter $model */

$this->title = $model->name;
$this->params['breadcrumbs'][] = ['label' => 'Lijsten', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
\yii\web\YiiAsset::register($this);
?>
<div class="chapter-view">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Wijzig', ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
        <?php
        $wordCount = (int) $model->countWordsOfChapter($model->id);
        $deleteConfirm = "Lijst '{$model->name}' wissen?";
        if ($wordCount === 1) {
            $deleteConfirm .= ' Ook het 1 woord in deze lijst wordt verwijderd.';
        } elseif ($wordCount > 1) {
            $deleteConfirm .= " Ook de {$wordCount} woorden in deze lijst worden verwijderd.";
        }
        ?>
        <?= Html::a('Wis', ['delete', 'id' => $model->id], [
            'class' => 'btn btn-danger',
            'data' => [
                'confirm' => $deleteConfirm,
                'method' => 'post',
            ],
        ]) ?>
    </p>

    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'id',
            'name',
            'description:ntext',
            [
                'attribute' => 'created_at',
                'format' => ['datetime', 'php:d-m-Y H:i:s']
            ],
            [
                'attribute' => 'updated_at',
                'format' => ['datetime', 'php:d-m-Y H:i:s']
            ]
            ,            
        ],
    ]) ?>

</div>
