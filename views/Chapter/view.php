<?php

use app\models\Word;
use yii\helpers\Html;
use yii\widgets\DetailView;

/** @var yii\web\View $this */
/** @var app\models\Chapter $model */
/** @var Word[] $words words in this list, ordered by Spanish */
/** @var int $translatedCount */

$words = $words ?? [];
$translatedCount = $translatedCount ?? 0;
$totalCount = count($words);
$untranslatedCount = $totalCount - $translatedCount;

$this->title = $model->name;
$this->params['breadcrumbs'][] = ['label' => 'Lijsten', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
\yii\web\YiiAsset::register($this);
?>
<div class="chapter-view">

    <h1><?= Html::encode($this->title) ?></h1>

    <p class="text-muted">
        <?= $totalCount ?> <?= $totalCount === 1 ? 'woord' : 'woorden' ?> ·
        <?= $translatedCount ?> vertaald<?= $untranslatedCount > 0 ? ", {$untranslatedCount} onvertaald" : '' ?>
    </p>

    <p>
        <?= Html::a('Wijzig', ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
        <?= Html::a('Bekijk woorden', ['word/index-by-chapter', 'chapter_id' => $model->id], ['class' => 'btn btn-outline-secondary']) ?>
        <?php
        $wordCount = (int) $model->countWordsOfChapter($model->id);
        $deleteConfirm = "Lijst '{$model->name}' wissen?";
        if ($wordCount === 1) {
            $deleteConfirm .= ' Ook het 1 woord in deze lijst wordt verwijderd.';
        } elseif ($wordCount > 1) {
            $deleteConfirm .= " Ook de {$wordCount} woorden in deze lijst worden verwijderd.";
        }
        ?>
        <?= Html::a('Wis', ['delete', 'id' => $model->id], [
            'class' => 'btn btn-danger',
            'data' => [
                'confirm' => $deleteConfirm,
                'method' => 'post',
            ],
        ]) ?>
    </p>

    <?php if ($translatedCount > 0): ?>
    <div class="d-flex gap-2 mb-4">
        <?= Html::beginForm(['/oefenen'], 'post') ?>
        <?= Html::hiddenInput('PracticeSelection[chapters][]', $model->id) ?>
        <?= Html::hiddenInput('PracticeSelection[nl_to_sp]', '1') ?>
        <?= Html::hiddenInput('PracticeSelection[max_words]', '20') ?>
        <?= Html::submitButton('Oefen NL → ES', ['class' => 'btn btn-success']) ?>
        <?= Html::endForm() ?>
        <?= Html::beginForm(['/oefenen'], 'post') ?>
        <?= Html::hiddenInput('PracticeSelection[chapters][]', $model->id) ?>
        <?= Html::hiddenInput('PracticeSelection[nl_to_sp]', '0') ?>
        <?= Html::hiddenInput('PracticeSelection[max_words]', '20') ?>
        <?= Html::submitButton('Oefen ES → NL', ['class' => 'btn btn-success']) ?>
        <?= Html::endForm() ?>
    </div>
    <?php elseif ($totalCount > 0): ?>
        <p>
            <?= Html::a('Vertaal ontbrekende woorden', ['word/list-untranslated', 'WordSearch' => ['chapter_id' => $model->id]], ['class' => 'btn btn-sm btn-warning']) ?>
            <span class="text-muted small">Onvertaalde woorden doen niet mee aan oefenen.</span>
        </p>
    <?php endif; ?>

    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'name',
            'description:ntext',
        ],
    ]) ?>

    <h2 class="mt-4">Woorden (<?= $totalCount ?>)</h2>
    <?php if (empty($words)): ?>
        <p class="text-muted">
            Nog geen woorden in deze lijst.
            <?= Html::a('Voeg woorden toe', ['word/create-multiple']) ?> (kies daarna deze lijst)
            of <?= Html::a('maak er één', ['word/create', 'Word' => ['chapter_id' => $model->id]]) ?>.
        </p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>Spaans</th>
                        <th>Nederlands</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($words as $word): ?>
                        <tr>
                            <td><?= Html::encode($word->spanish) ?></td>
                            <td><?= trim((string) $word->dutch) !== '' ? Html::encode($word->dutch) : '<span class="text-muted">—</span>' ?></td>
                            <td><?= Html::a('Bekijk', ['word/view', 'id' => $word->id], ['class' => 'btn btn-sm btn-outline-primary']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

</div>
