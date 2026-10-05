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

    <div class="mb-3 accent-keys" role="group" aria-label="Spaanse tekens">
        <?php foreach (['á', 'é', 'í', 'ó', 'ú', 'ü', 'ñ', '¡', '¿'] as $char): ?>
            <button type="button" class="btn btn-outline-secondary btn-sm accent-key" data-char="<?= $char ?>"><?= $char ?></button>
        <?php endforeach; ?>
    </div>

    <div class="form-group">
        <?= Html::submitButton('Controleer', [
            'class' => 'btn btn-primary',
        ]) ?>
    </div>

    <?php ActiveForm::end(); ?>
</div>

<?php
// Insert the character at the cursor position instead of appending it.
$this->registerJs(<<<'JS'
document.querySelectorAll('.accent-key').forEach(function (btn) {
    btn.addEventListener('click', function () {
        var input = document.getElementById('practiceanswer-answer');
        if (!input) {
            return;
        }
        var start = input.selectionStart === null ? input.value.length : input.selectionStart;
        var end = input.selectionEnd === null ? start : input.selectionEnd;
        input.value = input.value.slice(0, start) + btn.dataset.char + input.value.slice(end);
        input.focus();
        var pos = start + btn.dataset.char.length;
        input.setSelectionRange(pos, pos);
    });
});
JS);
?>