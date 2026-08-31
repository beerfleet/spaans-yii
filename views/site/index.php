<?php
/** @var yii\web\View $this */

use yii\helpers\Html;

$this->title = 'Spaans Oefenen';
$this->params['meta_description'] = 'Oefen je Spaanse woordenschat met interactieve oefeningen.';
$this->params['meta_keywords'] = 'spaans, Nederlands, oefenen, woordenschat, leeren';

// Define the navigation items as cards
$navItems = [
    [
        'title' => 'Hoofdstukken',
        'description' => 'Bekijk beschikbare hoofdstukken of voeg een nieuw hoofdstuk toe aan je leerlijst.',
        'icon' => '📚',
        'url' => ['/hoofdstuk'],
        'btn_text' => 'Bekijk lijst',
        'btn_url' => ['/hoofdstuk']
    ],
    [
        'title' => 'Woorden',
        'description' => 'Beheer je woordenschat: voeg nieuwe woorden toe, bekijk onvertaalde woorden of maak bulk aanpassingen.',
        'icon' => '📖',
        'url' => ['/woord'],
        'btn_text' => 'Woorden beheren',
        'btn_url' => ['/woord']
    ],
    [
        'title' => 'Oefenen',
        'description' => 'Start direct met oefenen en test je kennis van de Spaanse woorden.',
        'icon' => '🎯',
        'url' => ['/oefenen'],
        'btn_text' => 'Nu oefenen',
        'btn_url' => ['/oefenen']
    ],
];
?>
<div class="site-index text-center">
    <div class="hero-banner text-white rounded-4 p-5 mb-5">
        <h1 class="display-4 fw-bold">Welkom bij Spaans Oefenen</h1>
        <p class="lead">Leer Spaanse woorden op een effectieve en gestructureerde manier.</p>
    </div>

    <div class="row g-4">
        <?php foreach ($navItems as $item): ?>
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm rounded-4 p-3">
                    <div class="card-body">
                        <div class="mb-3">
                            <span class="display-4"><?= $item['icon'] ?></span>
                        </div>
                        <h3 class="h4 fw-bold"><?= $item['title'] ?></h3>
                        <p class="text-muted small"><?= $item['description'] ?></p>
                    </div>
                    <div class="card-footer bg-transparent border-0">
                        <?= Html::a(
                            $item['btn_text'],
                            $item['btn_url'],
                            ['class' => 'btn btn-primary px-4']
                        ) ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>