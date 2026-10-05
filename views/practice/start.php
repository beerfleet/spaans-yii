<?php

use app\models\Chapter;
use app\models\PracticeSelection;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var PracticeSelection $model */
/** @var Chapter[] $chapters */
/** @var array $counts translatable words per chapter_id */
/** @var int $translatableTotal */

$this->title = 'Oefening starten';
$this->params['breadcrumbs'][] = $this->title;

$counts = $counts ?? [];
$translatableTotal = $translatableTotal ?? 0;

$chapterOptions = [];
foreach ($chapters as $chapter) {
    $n = (int) ($counts[$chapter->id] ?? 0);
    $chapterOptions[$chapter->id] = sprintf('%s (%d)', $chapter->name, $n);
}
?>

<div class="practice-start">
    <h1><?= Html::encode($this->title) ?></h1>

    <?php $form = ActiveForm::begin([
        'action' => ['start'],
        'method' => 'post',
    ]); ?>

    <?= $form->field($model, 'all_chapters')->checkbox()->hint("Alle {$translatableTotal} vertaalde woorden, uit alle lijsten.") ?>

    <?= $form->field($model, 'chapters')->checkboxList($chapterOptions)->hint('Alleen nodig als je niet alles oefent.') ?>

    <?= $form->field($model, 'max_words')->textInput([
        'type' => 'number',
        'min' => 1,
        'step' => 1,
        'placeholder' => '20',
    ]) ?>

    <?= $form->field($model, 'nl_to_sp')->radioList([
        1 => 'Nederlands naar Spaans',
        0 => 'Spaans naar Nederlands',
    ]) ?>

    <div class="form-group">
        <?= Html::submitButton('Start oefening', [
            'class' => 'btn btn-primary',
        ]) ?>
    </div>

    <?php ActiveForm::end(); ?>
</div>