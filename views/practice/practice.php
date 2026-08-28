<?php

use app\models\Word;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var Word $word */
/** @var bool $nlToSp */
/** @var int $progress */
/** @var int $total */
/** @var app\models\PracticeAnswer $answerModel */

$this->title = 'Oefenen';
$this->params['breadcrumbs'][] = [
    'label' => 'Oefening starten',
    'url' => ['start'],
];
$this->params['breadcrumbs'][] = $this->title;

$question = $nlToSp ? $word->dutch : $word->spanish;
$answerLabel = $nlToSp ? 'Spaans' : 'Nederlands';
?>

<div class="practice-practice">
    <h1><?= Html::encode($this->title) ?></h1>

    <p>Woord <?= $progress ?> van <?= $total ?></p>

    <div class="card mb-4">
        <div class="card-body text-center">
            <h2><?= Html::encode($question) ?></h2>
        </div>
    </div>

    <?php $form = ActiveForm::begin([
        'action' => ['practice'],
        'method' => 'post',
    ]); ?>

    <?= $form->field($answerModel, 'answer')
        ->label($answerLabel)
        ->textInput([
            'autofocus' => true,
            'autocomplete' => 'off',
        ]) ?>

    <div class="form-group">
        <?= Html::submitButton('Controleer', [
            'class' => 'btn btn-primary',
        ]) ?>
    </div>

    <?php ActiveForm::end(); ?>
</div>