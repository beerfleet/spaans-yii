<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var app\models\Chapter $model */

$this->title = 'Wijzig hoofdstuk: ' . $model->name;
$this->params['breadcrumbs'][] = ['label' => 'Hoofdstukken', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->name, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Wijzig';
?>
<div class="chapter-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
