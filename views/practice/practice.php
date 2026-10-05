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
/** @var int $meaningsCount */

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
            <?php if ($word->listLabel !== null): ?>
                <p class="text-muted mb-1"><?= Html::encode($word->listLabel) ?></p>
            <?php endif; ?>
            <?php if (($meaningsCount ?? 1) > 1): ?>
                <span class="badge bg-info text-dark">Meerdere betekenissen mogelijk — één goed antwoord volstaat</span>
            <?php endif; ?>
        </div>
    </div>

    <?php $form = ActiveForm::begin([
        'action' => ['practice'],
        'method' => 'post',
    ]); ?>

    <?= $form->field($answerModel, 'answer')
        ->label($answerLabel)
        ->hint('Geen Spaans toetsenbord? Klik een teken om het in te voegen.')
        ->textInput([
            'autofocus' => true,
            'autocomplete' => 'off',
            'autocapitalize' => 'off',
            'spellcheck' => false,
            'lang' => $nlToSp ? 'es' : 'nl',
        ]) ?>

    <?= $this->render('/word/_accent-keys', ['inputIds' => ['practiceanswer-answer']]) ?>

    <div class="form-group">
        <?= Html::submitButton('Controleer', [
            'class' => 'btn btn-primary',
        ]) ?>
    </div>

    <?php ActiveForm::end(); ?>

    <?= Html::beginForm(['stop'], 'post', ['class' => 'mt-3']) ?>
    <?= Html::submitButton('Stoppen', [
        'class' => 'btn btn-outline-danger',
        'data' => ['confirm' => 'Oefening afbreken? Je voortgang van deze sessie gaat verloren.'],
    ]) ?>
    <?= Html::endForm() ?>
</div>