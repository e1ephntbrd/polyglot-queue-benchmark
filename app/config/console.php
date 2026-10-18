<?php
use yii\log\FileTarget;

return [
    'id' => 'email-campaign-console',
    'basePath' => dirname(__DIR__),
    'timeZone' => 'UTC',
    'controllerNamespace' => 'app\\commands',
    'controllerMap' => [
        'migrate' => [
            'class' => yii\console\controllers\MigrateController::class,
            'migrationNamespaces' => [
                'app\migrations',
            ],
            'migrationPath' => null,
        ],
    ],
    'bootstrap' => ['log'],
    'components' => [
        'db' => require __DIR__ . '/db.php',
        'mailer' => require __DIR__ . '/mailer.php',
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => FileTarget::class,
                    'levels' => ['error', 'warning', 'info'],
                    'logFile' => '@app/runtime/logs/app.log',
                ],
            ],
        ],
        'rabbit' => require __DIR__ . '/rabbitmq.php',
        'mongodb' => require __DIR__ . '/mongo.php',
        'redis' => require __DIR__ . '/redis.php'
    ],
    'container' => [
        'definitions' => require __DIR__ . '/di.php',
    ],
    'params' => [
        'campaign' => [
            'batchSize'     => 2500,
            'queue'         => 'email_campaign_queue',
            'fromEmail'     => getenv('MAIL_FROM') ?: 'no-reply@example.local',
            'mailStrategy'  => 'all_bcc', // @see \app\services\MailerStrategyFactory > iterate|all_cc|all_bcc|log_only
            'promoActions'  => ['banner_click','promo_view','message_read'],
            'hoursWindow'   => 24,
            'minViews'      => 3,
            'htmlEnabled'   => 1,
        ],
    ],
];
