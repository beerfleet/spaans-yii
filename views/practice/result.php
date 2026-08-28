<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var int $correct */
/** @var int $total */

$this->title = 'Resultaat';
$this->params['breadcrumbs'][] = $this->title;

$incorrect = $total - $correct;
$percentage = $total > 0 ? round(($correct / $total) * 100) : 0;
?>

<div class="practice-result">
	<h1><?= Html::encode($this->title) ?></h1>

	<p>Totaal aantal woorden: <?= $total ?></p>
	<p>Correct: <?= $correct ?></p>
	<p>Fout: <?= $incorrect ?></p>
	<p>Score: <?= $percentage ?>%</p>

	<?= Html::a('Nieuwe oefening', ['start'], ['class' => 'btn btn-primary']) ?>
</div>
