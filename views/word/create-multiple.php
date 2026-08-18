<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var app\models\Word $model */

$this->title = 'Nieuwe woorden';
$this->params['breadcrumbs'][] = ['label' => 'Woorden', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="word-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
