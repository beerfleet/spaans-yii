<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var app\models\Chapter $model */

$this->title = 'Nieuwe lijst';
$this->params['breadcrumbs'][] = ['label' => 'Lijsten', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="chapter-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
