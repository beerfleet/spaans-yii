<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/** @var yii\web\View $this */
/** @var app\models\Word $model */

$this->title = $model->id;
$this->params['breadcrumbs'][] = ['label' => 'Woorden', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
\yii\web\YiiAsset::register($this);
?>
<div class="word-view">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Wijzig', ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
        <?= Html::a('Wis', ['delete', 'id' => $model->id], [
            'class' => 'btn btn-danger',
            'data' => [
                'confirm' => 'Zeker dat je wil wissen?',
                'method' => 'post',
            ],
        ]) ?>
    </p>

    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'id',
            'chapter_id',
            'dutch',
            'spanish',
            'created_at',
            'updated_at',
        ],
    ]) ?>

</div>
