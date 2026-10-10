<?php

use app\models\Word;
use yii\helpers\Html;
use yii\helpers\Url;

/** @var yii\web\View $this */
/** @var array $groups shared term => Word[] (each 2+ distinct rows) */
/** @var int $groupCount */
/** @var int $wordCount */
/** @var Word[] $multiVariantRows rows with several comma-separated variants in one field */

$this->title = 'Dubbele woorden';
$this->params['breadcrumbs'][] = ['label' => 'Woorden', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="word-duplicates">

    <h1><?= Html::encode($this->title) ?></h1>

    <p class="text-muted">
        <?= $groupCount ?> <?= $groupCount === 1 ? 'vorm' : 'vormen' ?> in <?= $wordCount ?> <?= $wordCount === 1 ? 'woord' : 'woorden' ?>.
        Vormen met verschillende betekenissen zijn homoniemen (oefenen accepteert elke betekenis);
        gedeelde vormen of vertalingen overlappen; identieke rijen kun je via Wis opruimen.
    </p>

    <?php if (empty($groups)): ?>
        <div class="alert alert-success">Geen dubbele vormen gevonden.</div>
    <?php endif; ?>

    <?php foreach ($groups as $term => $group): ?>
        <?php
        $spanishForms = [];
        $meanings = [];
        $dutchByForm = [];
        foreach ($group as $word) {
            /** @var Word $word */
            $form = mb_strtolower(trim((string) $word->spanish));
            if (!in_array($form, $spanishForms)) {
                $spanishForms[] = $form;
            }
            $dutch = trim((string) $word->dutch);
            $meaning = $dutch !== '' ? $dutch : '(nog onvertaald)';
            if (!in_array($meaning, $meanings)) {
                $meanings[] = $meaning;
            }
            $normDutch = mb_strtolower($dutch);
            if ($normDutch !== '') {
                $dutchByForm[$normDutch][] = $form;
            }
        }
        $sameForm = count($spanishForms) === 1;
        $isHomonym = $sameForm && count($meanings) > 1;
        // Same full translation on different Spanish forms: Dutch homonym
        // (bank → sofá/banco) or synonyms sharing one translation.
        $sharedTranslation = null;
        foreach ($dutchByForm as $dutch => $forms) {
            if (count(array_unique($forms)) > 1) {
                $sharedTranslation = $dutch;
                break;
            }
        }
        ?>
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center gap-2">
                <strong><?= Html::encode($term) ?></strong>
                <?php if ($isHomonym): ?>
                    <span class="badge bg-info text-dark">Homoniemen: <?= Html::encode(implode(' · ', $meanings)) ?></span>
                <?php elseif ($sameForm): ?>
                    <span class="badge bg-warning text-dark">Dubbel (<?= count($group) ?>×)</span>
                <?php elseif ($sharedTranslation !== null): ?>
                    <span class="badge bg-primary">Zelfde vertaling: <?= Html::encode($sharedTranslation) ?></span>
                <?php else: ?>
                    <span class="badge bg-secondary">Overlappend (<?= count($group) ?>×)</span>
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

    <h2 class="mt-5">Rijen met meerdere varianten</h2>
    <p class="text-muted">
        Eén rij met komma-varianten (zoals <em>empleo, trabajo, pista, órbita = baan</em>)
        groepeert nooit mee hierboven. Splitsen maakt er losse rijen van
        (Nederlands en lijsten blijven behouden; statistieken van de oude rij vervallen).
    </p>

    <?php $multiVariantRows = $multiVariantRows ?? []; ?>
    <?php if (empty($multiVariantRows)): ?>
        <div class="alert alert-success">Geen rijen met meerdere varianten.</div>
    <?php endif; ?>

    <?php foreach ($multiVariantRows as $word): ?>
        <?php
        /** @var Word $word */
        $parts = [];
        foreach (explode(',', (string) $word->spanish) as $part) {
            $part = trim($part);
            if ($part !== '' && !in_array($part, $parts)) {
                $parts[] = $part;
            }
        }
        ?>
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center gap-2">
                <strong><?= Html::encode($word->spanish) ?> = <?= Html::encode((string) $word->dutch) ?></strong>
                <span class="badge bg-secondary"><?= count($parts) ?> varianten</span>
            </div>
            <div class="card-body d-flex justify-content-between align-items-center gap-2 flex-wrap">
                <div>
                    <?php foreach ($parts as $part): ?>
                        <span class="badge bg-light text-dark border me-1"><?= Html::encode($part) ?></span>
                    <?php endforeach; ?>
                    <?php if ($word->getListsText() !== null): ?>
                        <span class="text-muted small ms-2"><?= Html::encode($word->getListsText()) ?></span>
                    <?php endif; ?>
                </div>
                <div class="d-flex gap-2">
                    <?= Html::a('Bekijk', ['view', 'id' => $word->id], ['class' => 'btn btn-sm btn-outline-primary']) ?>
                    <?= Html::a('Splitsen', ['split', 'id' => $word->id], [
                        'class' => 'btn btn-sm btn-outline-success',
                        'data' => [
                            'confirm' => 'Splitsen in ' . count($parts) . ' losse rijen? Statistieken van deze rij vervallen.',
                            'method' => 'post',
                        ],
                    ]) ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

</div>
