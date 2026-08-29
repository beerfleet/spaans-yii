<?php

use app\models\Chapter;
use app\models\PracticeSelection;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var PracticeSelection $model */
/** @var Chapter[] $chapters */

$this->title = 'Oefening starten';
$this->params['breadcrumbs'][] = $this->title;

$chapterOptions = [];
foreach ($chapters as $chapter) {
    $chapterOptions[$chapter->id] = sprintf(
        'Hoofdstuk %s: %s',
        $chapter->number,
        $chapter->name,
    );
}
?>

<div class="practice-start">
    <h1><?= Html::encode($this->title) ?></h1>

    <?php $form = ActiveForm::begin([
        'action' => ['start'],
        'method' => 'post',
    ]); ?>

    <?= $form->field($model, 'chapters')->checkboxList($chapterOptions) ?>

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