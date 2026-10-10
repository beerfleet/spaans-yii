<?php

/** @var yii\web\View $this */
/** @var int $wordCount */
/** @var int $listCount */
/** @var int $untranslatedCount */
/** @var int $listlessCount */
/** @var int $duplicateGroupCount */
/** @var int $duplicateWordCount */
/** @var bool $practiceActive */
/** @var int $practiceProgress */
/** @var int $practiceTotal */

use yii\helpers\Html;

$this->title = 'Spaans Oefenen';
$this->params['meta_description'] = 'Oefen je Spaanse woordenschat met interactieve oefeningen.';
$this->params['meta_keywords'] = 'spaans, Nederlands, oefenen, woordenschat, leren';

$wordCount = $wordCount ?? 0;
$listCount = $listCount ?? 0;
$untranslatedCount = $untranslatedCount ?? 0;
$listlessCount = $listlessCount ?? 0;
$duplicateGroupCount = $duplicateGroupCount ?? 0;
$duplicateWordCount = $duplicateWordCount ?? 0;
$practiceActive = $practiceActive ?? false;
$practiceProgress = $practiceProgress ?? 0;
$practiceTotal = $practiceTotal ?? 0;

// Define the workflow cards: direct links to what the app can do.
$navItems = [
    [
        'title' => 'Bulk toevoegen',
        'description' => 'Plak een rij woorden (één per regel) en bekijk eerst een preview.',
        'icon' => '＋',
        'url' => ['/woord/maak/bulk'],
        'btn_text' => 'Woorden toevoegen',
        'btn_url' => ['/woord/maak/bulk'],
    ],
    [
        'title' => 'Vertalen',
        'description' => 'Werk de inbox weg: vul Nederlands en lijst in, alles in één keer.',
        'icon' => '✏️',
        'url' => ['/woord/onvertaald'],
        'btn_text' => 'Verder vertalen',
        'btn_url' => ['/woord/onvertaald'],
        'badge' => $untranslatedCount > 0 ? $untranslatedCount . ' open' : null,
    ],
    [
        'title' => 'Oefenen',
        'description' => 'Per lijst of alles tegelijk, heen en weer.',
        'icon' => '🎯',
        'url' => ['/oefenen'],
        'btn_text' => 'Nu oefenen',
        'btn_url' => ['/oefenen'],
    ],
    [
        'title' => 'Lijsten',
        'description' => 'Bekijk beschikbare lijsten of voeg een nieuwe lijst toe.',
        'icon' => '📚',
        'url' => ['/hoofdstuk'],
        'btn_text' => 'Bekijk lijsten',
        'btn_url' => ['/hoofdstuk'],
    ],
    [
        'title' => 'Statistieken',
        'description' => 'Bekijk je scores per woord en oefenrichting.',
        'icon' => '📊',
        'url' => ['/oefenen/statistieken'],
        'btn_text' => 'Bekijk scores',
        'btn_url' => ['/oefenen/statistieken'],
    ],
    [
        'title' => 'Woorden',
        'description' => 'Zoek, filter en bewerk je volledige woordenschat per lijst.',
        'icon' => '📖',
        'url' => ['/woord'],
        'btn_text' => 'Woorden beheren',
        'btn_url' => ['/woord'],
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
                <?php if ($practiceActive): ?>
                    <?= Html::a("▶ Ga verder met oefenen ({$practiceProgress} van {$practiceTotal})", ['/oefenen/oefening'], ['class' => 'btn btn-primary btn-lg']) ?>
                <?php elseif ($untranslatedCount > 0): ?>
                    <?= Html::a("✏️ Verder vertalen ({$untranslatedCount} open)", ['/woord/onvertaald'], ['class' => 'btn btn-primary btn-lg']) ?>
                <?php else: ?>
                    <?= Html::a('🎯 Start met oefenen', ['/oefenen'], ['class' => 'btn btn-primary btn-lg']) ?>
                <?php endif; ?>
                <?= Html::a('＋ Bulk toevoegen', ['/woord/maak/bulk'], ['class' => 'home-hero-link']) ?>
            </div>
            <dl class="home-stats">
                <div><dt><?= $wordCount ?></dt><dd>woorden</dd></div>
                <div><dt><?= $listCount ?></dt><dd>lijsten</dd></div>
                <div><dt><?= $untranslatedCount ?></dt><dd>onvertaald</dd></div>
            </dl>
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
        <span>Nog te doen</span>
        <small>Direct naar je inboxen</small>
    </div>

    <?php if ($listlessCount + $untranslatedCount + $duplicateGroupCount === 0): ?>
        <div class="alert alert-success">Alles bijgewerkt — geen openstaande woorden. 🎉</div>
    <?php else: ?>
    <div class="list-group home-todo mb-4">
        <?php if ($listlessCount > 0): ?>
        <?= Html::a(
            "📥 <strong>{$listlessCount} zonder lijst</strong><span>wijs een lijst toe</span>",
            ['/woord', 'WordSearch' => ['chapter_id' => '0']],
            ['class' => 'list-group-item list-group-item-action']
        ) ?>
        <?php endif; ?>
        <?php if ($untranslatedCount > 0): ?>
        <?= Html::a(
            "✏️ <strong>{$untranslatedCount} onvertaald</strong><span>vul Nederlands in</span>",
            ['/woord/onvertaald'],
            ['class' => 'list-group-item list-group-item-action']
        ) ?>
        <?php endif; ?>
        <?php if ($duplicateGroupCount > 0): ?>
        <?= Html::a(
            "👯 <strong>{$duplicateGroupCount} dubbele " . ($duplicateGroupCount === 1 ? 'vorm' : 'vormen') . " ({$duplicateWordCount} woorden)</strong><span>homoniemen of dubbelen opruimen</span>",
            ['/woord/dubbelen'],
            ['class' => 'list-group-item list-group-item-action']
        ) ?>
        <?php endif; ?>
    </div>
    <?php endif; ?>

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
                        <h2>
                            <?= Html::encode($item['title']) ?>
                            <?php if (!empty($item['badge'])): ?>
                                <span class="badge bg-warning text-dark"><?= Html::encode($item['badge']) ?></span>
                            <?php endif; ?>
                        </h2>
                        <p><?= Html::encode($item['description']) ?></p>
                    </div>
                    <div class="card-footer">
                        <?= Html::a($item['btn_text'] . ' →', $item['btn_url'], ['class' => 'btn btn-primary stretched-link']) ?>
                    </div>
                </article>
            </div>
        <?php endforeach; ?>
    </div>

    <section class="home-tip">
        <div class="home-tip-icon">💡</div>
        <div><strong>Tip van vandaag</strong>
            <?php if ($practiceActive): ?>
                <p>Je oefening staat nog open — maak hem af voor het beste resultaat.</p>
            <?php elseif ($untranslatedCount > 0): ?>
                <p>Nog <?= $untranslatedCount ?> te vertalen. Onvertaalde woorden doen niet mee aan oefenen.</p>
            <?php else: ?>
                <p>Een paar minuten oefenen is beter dan alles in één keer willen leren.</p>
            <?php endif; ?>
        </div>
    </section>

</div>
