<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var int $correct */
/** @var int $total */
/** @var array $results each row: prompt, given, accepted (string[]), correct */
/** @var int $wrongCount */

$this->title = 'Resultaat';
$this->params['breadcrumbs'][] = ['label' => 'Oefening starten', 'url' => ['start']];
$this->params['breadcrumbs'][] = $this->title;

$results = $results ?? [];
$wrongCount = $wrongCount ?? 0;
$incorrect = $total - $correct;
$percentage = $total > 0 ? round(($correct / $total) * 100) : 0;
?>

<div class="practice-result">
    <h1><?= Html::encode($this->title) ?></h1>

    <div class="practice-score mb-4">
        <p>Totaal aantal woorden: <?= $total ?></p>
        <p>Correct: <?= $correct ?></p>
        <p>Fout: <?= $incorrect ?></p>
        <p>Score: <?= $percentage ?>%</p>
    </div>

    <p>
        <?= Html::a('Nieuwe oefening', ['start'], ['class' => 'btn btn-primary']) ?>
        <?php if ($wrongCount > 0): ?>
            <?= Html::beginForm(['repeat'], 'post', ['class' => 'd-inline']) ?>
            <?= Html::submitButton("Herhaal fouten ({$wrongCount})", ['class' => 'btn btn-warning']) ?>
            <?= Html::endForm() ?>
        <?php endif; ?>
    </p>

    <?php if (!empty($results)): ?>
        <h2>Overzicht</h2>
        <div class="table-responsive">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>Opgave</th>
                        <th>Jouw antwoord</th>
                        <th>Goed antwoord</th>
                        <th style="width: 4rem;"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($results as $row): ?>
                        <tr class="<?= !empty($row['correct']) ? 'table-success' : 'table-danger' ?>">
                            <td><?= Html::encode($row['prompt'] ?? '') ?></td>
                            <td><?= Html::encode($row['given'] ?? '') ?></td>
                            <td><?= Html::encode(implode(', ', $row['accepted'] ?? [])) ?></td>
                            <td><?= !empty($row['correct']) ? '✓' : '✗' ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
