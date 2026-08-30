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
        'label' => Html::a("Hoofdstuk " . $chapter->number, Url::to(['word/index-by-chapter', 'chapter_id' => $chapter->id])),
        'url' => '#', // URL is niet nodig omdat het al een link is
        'encode' => false, // Voer HTML-code niet uit
    ];
}, $chapters);

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
            ['label' => 'Nieuw hoofdstuk', 'url' => ['/chapter/create']],
            ...$chapterItems,
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
            'brandLabel' => Yii::$app->params['appName'],
            'brandUrl' => Yii::$app->homeUrl,
            'options' => ['class' => 'navbar-expand-md navbar-dark bg-dark fixed-top']
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
            'class' => 'btn btn-link nav-link fs-5',
            'aria-label' => 'Switch to dark mode',
        ],
    ) ?>
    <?php NavBar::end() ?>
</header>