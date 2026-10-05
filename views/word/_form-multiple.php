<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var app\models\Word $model */
/** @var yii\widgets\ActiveForm $form */
/** @var array|null $preview each row: ['spanish' => string, 'status' => 'new'|'duplicate'|'exists', 'existing' => string[], 'add' => bool] */

$preview = $preview ?? null;
$newCount = 0;
$existsCount = 0;
if (is_array($preview)) {
    foreach ($preview as $row) {
        if ($row['status'] === 'new') {
            $newCount++;
        } else {
            $existsCount++;
        }
    }
}
?>

<div class="word-form">

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'chapter_id')->dropDownList(
        \app\models\Chapter::find()
            ->select(['name'])
            ->orderBy(['name' => SORT_ASC])
            ->indexBy('id')
            ->column(),
        ['prompt' => '— Geen lijst (later bepalen) —']
    )->label("Lijst (optioneel)")->hint('Laat leeg als de woorden (nog) niet tot dezelfde lijst behoren. Je kunt de lijst later per woord invullen.') ?>

    <?= $form->field($model, 'spanish')->textarea(['rows' => 8, 'placeholder' => "buenos días\nmuchas gracias\nhasta luego"])->label('Spaans')->hint('Eén uitdrukking per regel. Uitdrukkingen met spaties blijven bij elkaar. Eén regel met komma\'s of puntkomma\'s wordt nog gesplitst (oude invoer).') ?>

    <?= $this->render('_accent-keys', ['inputIds' => ['word-spanish']]) ?>

    <?php if ($preview === null): ?>
        <div class="form-group mt-3">
            <?= Html::submitButton('Preview', ['class' => 'btn btn-primary', 'name' => 'preview', 'value' => '1']) ?>
        </div>
    <?php else: ?>
        <div class="card mt-3 mb-3">
            <div class="card-header">
                Preview: <?= count($preview) ?> regel(s) — <?= $newCount ?> nieuw<?= $existsCount > 0 ? ", {$existsCount} komt al voor" : '' ?>
            </div>
            <p class="mx-3 mt-2 mb-0 text-muted small">
                Zelfde Spaans kan meerdere betekenissen hebben (bv. <em>camino</em>: de weg / ik loop).
                Alleen aangevinkte rijen worden bewaard; vink een bestaande vorm expliciet aan als aparte betekenis.
            </p>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th style="width: 3rem;">#</th>
                            <th>Spaans</th>
                            <th style="width: 10rem;">Status</th>
                            <th>Bestaat al als</th>
                            <th style="width: 7rem;">Toevoegen?</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($preview as $i => $row): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td><?= Html::encode($row['spanish']) ?></td>
                                <td>
                                    <?php if ($row['status'] === 'new'): ?>
                                        <span class="badge bg-success">Nieuw</span>
                                    <?php elseif ($row['status'] === 'duplicate'): ?>
                                        <span class="badge bg-warning text-dark">Dubbel in lijst</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Bestaat al</span>
                                    <?php endif; ?>
                                </td>
                                <td class="small text-muted">
                                    <?php if (empty($row['existing'])): ?>
                                        —
                                    <?php else: ?>
                                        <?= Html::encode(implode(' · ', array_slice($row['existing'], 0, 3))) ?><?= count($row['existing']) > 3 ? ' …' : '' ?>
                                    <?php endif; ?>
                                </td>
                                <td><?= Html::checkbox("add[{$i}]", !empty($row['add'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php if ($newCount === 0 && $existsCount > 0): ?>
            <div class="alert alert-info">Alles komt al voor. Vink aan wat je toch als aparte betekenis wilt toevoegen, of pas de lijst aan.</div>
        <?php endif; ?>

        <div class="form-group mt-3 d-flex gap-2">
            <?= Html::submitButton('Bewaar selectie', ['class' => 'btn btn-success', 'name' => 'confirm', 'value' => '1']) ?>
            <?= Html::submitButton('Preview verversen', ['class' => 'btn btn-outline-primary', 'name' => 'preview', 'value' => '1']) ?>
        </div>
    <?php endif; ?>

    <?php ActiveForm::end(); ?>

</div>
