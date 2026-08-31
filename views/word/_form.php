<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var app\models\Word $model */
/** @var yii\widgets\ActiveForm $form */
?>

<div class="word-form">

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'chapter_id')->dropDownList(
        \app\models\Chapter::find()
            ->select(['CONCAT(number, " - ", name) AS chapter_label'])
            ->indexBy('id')
            ->column(),
        ['prompt' => 'Kies een hoofdstuk']
    )->label("Hoofdstuk") ?>

    <?= $form->field($model, 'spanish')->textInput(['maxlength' => true])->label('Spaans') ?>

    <?= $form->field($model, 'dutch')->textInput(['maxlength' => true])->label('Nederlands') ?>

    <div class="form-group mt-3">
        <?= Html::submitButton('Opslaan', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>