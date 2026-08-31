<?php

$params = require __DIR__ . '/params.php';
$db = require __DIR__ . '/db.php';

$config = [
    'id' => 'basic',
    'language' => 'nl-NL',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log', 'debug'],
    'container' => [
        'singletons' => [
            \yii\mail\MailerInterface::class => [
                'class' => \yii\symfonymailer\Mailer::class,
                // send all mails to a file by default.
                'useFileTransport' => true,
                'viewPath' => '@app/mail',
            ],
        ],
    ],
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm' => '@vendor/npm-asset',
    ],
    'components' => [
        'request' => [
            // !!! insert a secret key in the following (if it is empty) - this is required by cookie validation
            'cookieValidationKey' => 'im5GH0pnE_o9HglmRdjrAZDc_WGAHoQT',
        ],
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'enableStrictParsing' => false,
            'rules' => [
                // Add custom routing rules here if needed, e.g.:
                // 'word' => 'word/index',
                'chapter/index' => 'chapter',
                'hoofdstuk' => 'chapter/index',
                'hoofdstuk/maak' => 'chapter/create',                
                'hoofdstuk/wijzig/<id:\d+>' => 'chapter/update',
                'hoofdstuk/bekijk/<id:\d+>' => 'chapter/view',
                'hoofdstuk/wis/<id:\d+>' => 'chapter/delete',
                'word/index' => 'word',
                'woord' => 'word/index',
                'woord/hoofdstuk/<chapter_id:\d+>' => 'word/index',
                'woord/hs/<chapter_id:\d+>' => 'word/index-by-chapter',
                'woord/maak' => 'word/create',
                'woord/maak/bulk' => 'word/create-multiple',
                'woord/wijzig/<id:\d+>' => 'word/update',
                'woord/bekijk/<id:\d+>' => 'word/view',
                'woord/wis/<id:\d+>' => 'word/delete',
                'woord/onvertaald' => 'word/list-untranslated',
                'oefenen' => 'practice/start',
                'oefenen/oefening' => 'practice/practice',
                'oefenen/resultaat' => 'practice/result',
                
            ],
        ],
        'view' => [
            'title' => 'Oefeningen Woordenschat Spaans'
        ],
        'cache' => [
            'class' => \yii\caching\FileCache::class,
        ],
        'user' => [
            'identityClass' => \app\models\User::class,
            'enableAutoLogin' => true,
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        'mailer' => \yii\mail\MailerInterface::class,
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => \yii\log\FileTarget::class,
                    'levels' => ['error', 'warning'],
                ],
                /* [
                    'class' => \yii\log\DbTarget::class,
                ], */
            ],
        ],
        'db' => $db,
        /*
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules' => [
            ],
        ],
        */
    ],
    'modules' => [
        'debug' => [
            'class' => 'yii\debug\Module',
        ],
    ],
    'params' => $params,
];

if (YII_ENV_DEV) {
    // configuration adjustments for 'dev' environment
    $config['bootstrap'][] = 'debug';
    $config['modules']['debug'] = [
        'class' => \yii\debug\Module::class,
        // uncomment the following to add your IP if you are not connecting from localhost.
        //'allowedIPs' => ['127.0.0.1', '::1'],
    ];

    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => \yii\gii\Module::class,
        // uncomment the following to add your IP if you are not connecting from localhost.
        'allowedIPs' => ['127.0.0.1', '::1'],
    ];
}

return $config;
