<?php

use app\models\Word;
use app\models\WordStatistic;
use yii\helpers\Html;
use yii\widgets\DetailView;

/** @var yii\web\View $this */
/** @var Word $model */
/** @var Word[] $homonyms other rows sharing the Spanish form */
/** @var WordStatistic[] $statistics */

$homonyms = $homonyms ?? [];
$statistics = $statistics ?? [];
$translatable = trim((string) $model->dutch) !== '';

$this->title = $model->spanish;
$this->params['breadcrumbs'][] = ['label' => 'Woorden', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
\yii\web\YiiAsset::register($this);
?>
<div class="word-view">

    <h1><?= Html::encode($this->title) ?></h1>
    <?php if ($translatable): ?>
        <p class="lead text-muted"><?= Html::encode($model->dutch) ?></p>
    <?php else: ?>
        <p>
            <?= Html::a('Vertaal dit woord', ['list-untranslated'], ['class' => 'btn btn-sm btn-warning']) ?>
            <span class="text-muted small">Onvertaalde woorden doen niet mee aan oefenen.</span>
        </p>
    <?php endif; ?>

    <p>
        <?= Html::a('Wijzig', ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
        <?= Html::a('Wis', ['delete', 'id' => $model->id], [
            'class' => 'btn btn-danger',
            'data' => [
                'confirm' => 'Zeker dat je wil wissen?',
                'method' => 'post',
            ],
        ]) ?>
        <?php if (!empty($model->getChapterIds())): ?>
            <?= Html::a('Naar woorden', ['index'], ['class' => 'btn btn-outline-secondary']) ?>
        <?php endif; ?>
    </p>

    <?php if ($translatable): ?>
    <div class="d-flex gap-2 mb-4">
        <?= Html::beginForm(['/practice/practice-word', 'id' => $model->id], 'post') ?>
        <?= Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->getCsrfToken()) ?>
        <?= Html::hiddenInput('dir', 'nl') ?>
        <?= Html::submitButton('Oefen NL → ES', ['class' => 'btn btn-success']) ?>
        <?= Html::endForm() ?>
        <?= Html::beginForm(['/practice/practice-word', 'id' => $model->id], 'post') ?>
        <?= Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->getCsrfToken()) ?>
        <?= Html::hiddenInput('dir', 'sp') ?>
        <?= Html::submitButton('Oefen ES → NL', ['class' => 'btn btn-success']) ?>
        <?= Html::endForm() ?>
    </div>
    <?php endif; ?>

    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            [
                'attribute' => 'chapterIds',
                'format' => 'raw',
                'value' => function ($model) {
                    /** @var \app\models\Word $model */
                    if (empty($model->chapters)) {
                        return '<span class="text-muted">—</span>';
                    }
                    $links = [];
                    foreach ($model->chapters as $chapter) {
                        $links[] = Html::a(Html::encode($chapter->name), ['index-by-chapter', 'chapter_id' => $chapter->id]);
                    }
                    return implode(', ', $links);
                },
            ],
            'spanish',
            'dutch',
        ],
    ]) ?>

    <h2 class="mt-4">Homoniemen</h2>
    <?php if (empty($homonyms)): ?>
        <p class="text-muted">Geen andere rijen met deze Spaanse vorm.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>Spaans</th>
                        <th>Nederlands</th>
                        <th>Lijst</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($homonyms as $other): ?>
                        <tr>
                            <td><?= Html::encode($other->spanish) ?></td>
                            <td><?= trim((string) $other->dutch) !== '' ? Html::encode($other->dutch) : '<span class="text-muted">—</span>' ?></td>
                            <td><?= $other->getListsText() !== null ? Html::encode($other->getListsText()) : '<span class="text-muted">—</span>' ?></td>
                            <td><?= Html::a('Bekijk', ['view', 'id' => $other->id], ['class' => 'btn btn-sm btn-outline-primary']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <h2 class="mt-4">Oefenstats</h2>
    <?php if (empty($statistics)): ?>
        <p class="text-muted">Nog niet geoefend. <?= Html::a('Bekijk alle statistieken', ['/practice/stats']) ?>.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>Richting</th>
                        <th>Goed</th>
                        <th>Fout</th>
                        <th>Succes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($statistics as $stat): ?>
                        <?php
                        /** @var WordStatistic $stat */
                        $statTotal = $stat->correct_count + $stat->incorrect_count;
                        ?>
                        <tr>
                            <td><?= $stat->nl_to_sp ? 'NL → ES' : 'ES → NL' ?></td>
                            <td><?= (int) $stat->correct_count ?></td>
                            <td><?= (int) $stat->incorrect_count ?></td>
                            <td><?= $statTotal > 0 ? round(100 * $stat->correct_count / $statTotal) . '%' : '—' ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

</div>
