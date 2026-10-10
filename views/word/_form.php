<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var app\models\Word $model */
/** @var yii\widgets\ActiveForm $form */
?>

<div class="word-form">

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'chapterIds')->checkboxList(
        \app\models\Chapter::find()
            ->select(['name'])
            ->orderBy(['name' => SORT_ASC])
            ->indexBy('id')
            ->column()
    )->label("Lijsten (optioneel)") ?>

    <?= $form->field($model, 'spanish')->textInput(['maxlength' => true])->label('Spaans') ?>

    <?= $form->field($model, 'dutch')->textInput(['maxlength' => true])->label('Nederlands') ?>

    <?= $this->render('_accent-keys', ['inputIds' => [Html::getInputId($model, 'spanish'), Html::getInputId($model, 'dutch')]]) ?>

    <div class="form-group mt-3">
        <?= Html::submitButton('Opslaan', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>