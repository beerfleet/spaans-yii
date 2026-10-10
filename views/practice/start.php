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
/** @var array $untranslatedCounts untranslated words per chapter_id */
/** @var int $untranslatedTotal */
/** @var array $freshCounts translated words without any practice history per chapter_id */

$this->title = 'Oefening starten';
$this->params['breadcrumbs'][] = $this->title;

$counts = $counts ?? [];
$translatableTotal = $translatableTotal ?? 0;
$untranslatedCounts = $untranslatedCounts ?? [];
$untranslatedTotal = $untranslatedTotal ?? 0;
$freshCounts = $freshCounts ?? [];

$chapterOptions = [];
foreach ($chapters as $chapter) {
    $n = (int) ($counts[$chapter->id] ?? 0);
    $u = (int) ($untranslatedCounts[$chapter->id] ?? 0);
    $f = (int) ($freshCounts[$chapter->id] ?? 0);
    $chapterOptions[$chapter->id] = sprintf(
        '%s (%d te oefenen%s%s)',
        $chapter->name,
        $n,
        $f > 0 ? ", {$f} nooit geoefend" : '',
        $u > 0 ? ", {$u} onvertaald" : ''
    );
}
?>

<div class="practice-start">
    <h1><?= Html::encode($this->title) ?></h1>

    <?php $form = ActiveForm::begin([
        'action' => ['start'],
        'method' => 'post',
    ]); ?>

    <?= $form->field($model, 'all_chapters')->checkbox()->hint("Alle {$translatableTotal} vertaalde woorden, uit alle lijsten." . ($untranslatedTotal > 0 ? " ({$untranslatedTotal} onvertaald doen niet mee.)" : '')) ?>

    <?= $form->field($model, 'chapters')->checkboxList($chapterOptions)->hint('Het getal is het aantal oefenbare (vertaalde) woorden per lijst. Alleen nodig als je niet alles oefent.') ?>

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

    <?= $form->field($model, 'difficult_first')->checkbox()->hint('Woorden waar je het vaakst fout op ging eerst.') ?>

    <?= $form->field($model, 'only_unpracticed')->checkbox()->hint('Alleen woorden zonder enige oefenhistorie.') ?>

    <div class="form-group">
        <?= Html::submitButton('Start oefening', [
            'class' => 'btn btn-primary',
        ]) ?>
    </div>

    <?php ActiveForm::end(); ?>
</div>