<?php

use app\models\Word;
use yii\helpers\Html;
use yii\helpers\Url;

/** @var yii\web\View $this */
/** @var array $groups normalized form => Word[] (each 2+ rows) */
/** @var int $groupCount */
/** @var int $wordCount */

$this->title = 'Dubbele woorden';
$this->params['breadcrumbs'][] = ['label' => 'Woorden', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="word-duplicates">

    <h1><?= Html::encode($this->title) ?></h1>

    <p class="text-muted">
        <?= $groupCount ?> <?= $groupCount === 1 ? 'vorm' : 'vormen' ?> in <?= $wordCount ?> <?= $wordCount === 1 ? 'woord' : 'woorden' ?>.
        Vormen met verschillende betekenissen zijn homoniemen (oefenen accepteert elke betekenis);
        identieke rijen kun je via Wis opruimen.
    </p>

    <?php if (empty($groups)): ?>
        <div class="alert alert-success">Geen dubbele vormen gevonden.</div>
    <?php endif; ?>

    <?php foreach ($groups as $group): ?>
        <?php
        $meanings = [];
        foreach ($group as $word) {
            /** @var Word $word */
            $dutch = trim((string) $word->dutch);
            $meaning = $dutch !== '' ? $dutch : '(nog onvertaald)';
            if (!in_array($meaning, $meanings)) {
                $meanings[] = $meaning;
            }
        }
        $isHomonym = count($meanings) > 1;
        ?>
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <strong><?= Html::encode($group[0]->spanish) ?></strong>
                <?php if ($isHomonym): ?>
                    <span class="badge bg-info text-dark">Homoniemen: <?= Html::encode(implode(' · ', $meanings)) ?></span>
                <?php else: ?>
                    <span class="badge bg-warning text-dark">Dubbel (<?= count($group) ?>×)</span>
                <?php endif; ?>
            </div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>Spaans</th>
                            <th>Nederlands</th>
                            <th>Lijst</th>
                            <th style="width: 12rem;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($group as $word): ?>
                            <tr>
                                <td><?= Html::encode($word->spanish) ?></td>
                                <td><?= $word->dutch !== null && trim($word->dutch) !== '' ? Html::encode($word->dutch) : '<span class="text-muted">—</span>' ?></td>
                                <td><?= $word->getListsText() !== null ? Html::encode($word->getListsText()) : '<span class="text-muted">—</span>' ?></td>
                                <td class="text-end text-nowrap">
                                    <?= Html::a('Bekijk', ['view', 'id' => $word->id], ['class' => 'btn btn-sm btn-outline-primary']) ?>
                                    <?= Html::a('Wijzig', ['update', 'id' => $word->id], ['class' => 'btn btn-sm btn-outline-primary']) ?>
                                    <?= Html::a('Wis', ['delete', 'id' => $word->id], [
                                        'class' => 'btn btn-sm btn-outline-danger',
                                        'data' => [
                                            'confirm' => "Woord '{$word->spanish}' (" . ($word->dutch ?? 'onvertaald') . ") wissen?",
                                            'method' => 'post',
                                        ],
                                    ]) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endforeach; ?>

</div>
