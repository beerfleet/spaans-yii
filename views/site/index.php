<?php

/** @var yii\web\View $this */

use yii\helpers\Html;

$this->title = 'Spaans Oefenen';
$this->params['meta_description'] = 'Oefen je Spaanse woordenschat met interactieve oefeningen.';
$this->params['meta_keywords'] = 'spaans, Nederlands, oefenen, woordenschat, leeren';

// Define the navigation items as cards
$navItems = [
    [
        'title' => 'Lijsten',
        'description' => 'Bekijk beschikbare lijsten of voeg een nieuwe lijst toe aan je leerlijst.',
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
<div class="site-index">

    <section class="home-hero">
        <div class="home-hero-content">
            <div class="home-eyebrow">🇪🇸 ¡Hola!</div>
            <h1>Welkom bij<br><span>Spaans Oefenen</span></h1>
            <p>Leer Spaanse woorden. Oefen op jouw tempo.<br>En maak van woordenschat iets dat blijft hangen.</p>
            <div class="home-hero-actions">
                <?= Html::a('🎯 Start met oefenen', ['/oefenen'], ['class' => 'btn btn-primary btn-lg']) ?>
                <?= Html::a('📖 Bekijk woorden', ['/woord'], ['class' => 'home-hero-link']) ?>
            </div>
        </div>
        <div class="home-hero-art" aria-hidden="true">
            <div class="sun"></div>
            <div class="speech speech-one">¡Hola!</div>
            <div class="speech speech-two">hola → hallo</div>
            <div class="hero-book">📖</div>
            <div class="hero-word">palabra</div>
        </div>
    </section>

    <div class="home-section-heading">
        <span>Wat wil je doen?</span>
        <small>Kies je volgende stap</small>
    </div>

    <div class="row g-4 home-cards">
        <?php foreach ($navItems as $index => $item): ?>
            <div class="col-md-4">
                <article class="card home-card home-card-<?= $index + 1 ?> h-100">
                    <div class="home-card-number">0<?= $index + 1 ?></div>
                    <div class="home-card-icon"><?= $item['icon'] ?></div>
                    <div class="card-body">
                        <h2><?= $item['title'] ?></h2>
                        <p><?= $item['description'] ?></p>
                    </div>
                    <div class="card-footer">
                        <?= Html::a($item['btn_text'] . ' →', $item['btn_url'], ['class' => 'btn btn-primary']) ?>
                    </div>
                </article>
            </div>
        <?php endforeach; ?>
    </div>

    <section class="home-tip">
        <div class="home-tip-icon">💡</div>
        <div><strong>Tip van vandaag</strong>
            <p>Een paar minuten oefenen is beter dan alles in één keer willen leren.</p>
        </div>
    </section>

</div>