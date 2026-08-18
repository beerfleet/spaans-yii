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
        \app\models\Chapter::find()->select(['id', 'name'])->indexBy('id')->column(),
        ['prompt' => 'Kies een hoofdstuk']
    )->label("Hoofdstuk") ?>

    <?= $form->field($model, 'dutch')->textInput(['maxlength' => true])->label('Nederlands') ?>

    <?= $form->field($model, 'spanish')->textInput(['maxlength' => true])->label('Spaans') ?>

    <?= $form->field($model, 'created_at')->textInput()->label('Gemaakt op') ?>

    <?= $form->field($model, 'updated_at')->textInput()->label('Gewijzigd op') ?>

    <div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>