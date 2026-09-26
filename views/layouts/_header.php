<?php

declare(strict_types=1);

/** @var yii\web\View $this */

use yii\bootstrap5\Nav;
use yii\bootstrap5\NavBar;
use yii\helpers\Html;
use yii\helpers\Url;

use app\models\Chapter;

$chapters = Chapter::find()->all();

// Maak een array voor de hoofdstukken
$chapterItems = array_map(function ($chapter) {
    return [
        'label' => $chapter->name . " - " . $chapter->number,
        'url' => ['word/index-by-chapter', 'chapter_id' => $chapter->id],
        'linkOptions' => ['class' => 'dropdown-item'],
    ];
}, $chapters);

// Define the separator
$separator = ['label' => '<div class="dropdown-divider"></div>', 'encode' => false];

$items = [
    [
        'label' => 'Home',
        'url' => ['/site/index'],
    ],
    /* [
        'label' => 'About',
        'url' => ['/site/about'],
    ],
    [
        'label' => 'Contact',
        'url' => ['/site/contact'],
    ], */
    [
        'label' => 'Hoofdstukken',
        'items' => [
            ['label' => 'Lijst', 'url' => ['/hoofdstuk']],
            $separator,
            ...$chapterItems,
            $separator,
            ['label' => 'Nieuw hoofdstuk', 'url' => ['/chapter/create']],
        ],
    ],
    [
        'label' => 'Woorden',
        'items' => [
            ['label' => 'Lijst', 'url' => ['/woord']],
            ['label' => 'Nieuw woord', 'url' => ['/word/create']],
            ['label' => 'Meerdere woorden', 'url' => ['/word/create-multiple']],
            ['label' => 'Onvertaalde woorden', 'url' => ['/word/list-untranslated']],
        ],
    ],
    [
        'label' => 'Oefenen',
        'url' => ['/oefenen'],
    ],
    /*     [
            'label' => 'Login',
            'url' => ['/site/login'],
            'visible' => Yii::$app->user->isGuest,
        ], */
    [
        'label' => 'Logout (' . Html::encode(Yii::$app->user->identity?->username ?? '') . ')',
        'url' => ['/site/logout'],
        'linkOptions' => [
            'data-method' => 'post',
            'class' => 'nav-link logout',
        ],
        'visible' => !Yii::$app->user->isGuest,
    ],
];

?>
<header id="header">
    <?php NavBar::begin(
        [
            //'brandLabel' => Yii::$app->name,
            'brandLabel' => '<span class="brand-flag" aria-hidden="true">🇪🇸</span> ' . Html::encode(Yii::$app->params['appName']),
            'brandUrl' => Yii::$app->homeUrl,
            'options' => ['class' => 'navbar-expand-md fixed-top app-navbar'],
            'brandOptions' => ['class' => 'navbar-brand app-brand'],
        ],
    ) ?>
    <?= Nav::widget(
        [
            'options' => ['class' => 'navbar-nav me-auto'],
            'encodeLabels' => false,
            'items' => $items,
        ],
    ) ?>
    <?= Html::button(
        '&#127769;',
        [
            'id' => 'theme-toggle',
            'class' => 'btn nav-link fs-5 theme-toggle-btn',
            'aria-label' => 'Switch to dark mode',
        ],
    ) ?>
    <?php NavBar::end() ?>
</header>