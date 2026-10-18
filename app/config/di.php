<?php

use app\services\EmailCampaignService;
use app\src\Application\UserIdPublisher;
use app\src\Domain\EmailCampaign\CampaignServiceInterface;
use app\src\Domain\EmailCampaign\Promo;
use app\src\Infrastructure\Queue\MessagePublisher;
use app\src\Infrastructure\Queue\RabbitMq\ConnectionFactory;
use app\src\Infrastructure\Queue\Worker;
use app\src\Infrastructure\SeedManager;
use app\src\Infrastructure\Seed\Context;
use app\src\Infrastructure\Seed\DbSeeder;
use app\src\Infrastructure\Seed\MongoDbSeeder;
use app\src\Infrastructure\UserId\MongoDbSource;
use app\src\Infrastructure\UserId\MysqlDbSource;
use app\src\Infrastructure\UserId\RedisStorage;

return [
    EmailCampaignService::class => EmailCampaignService::class,
    CampaignServiceInterface::class => Promo::class,
    SeedManager::class => [
        '__construct()' => [
            new Context(),
            [
                'db' => \Yii::createObject(DbSeeder::class),
                'mongo' => \Yii::createObject(MongoDbSeeder::class),
            ],
        ]
    ],
    RedisStorage::class => function() {
        return new RedisStorage(\Yii::$app->redis);
    },
    MessagePublisher::class => function() {
        $connectionFactory = new ConnectionFactory();
        $connection = $connectionFactory->create(
            \Yii::$app->rabbit->host,
            \Yii::$app->rabbit->port,
            \Yii::$app->rabbit->user,
            \Yii::$app->rabbit->pass
        );
        return new MessagePublisher($connection);
    },
    Worker::class => function() {
        $connectionFactory = new ConnectionFactory();
        $connection = $connectionFactory->create(
            \Yii::$app->rabbit->host,
            \Yii::$app->rabbit->port,
            \Yii::$app->rabbit->user,
            \Yii::$app->rabbit->pass
        );
        return new Worker($connection);
    },
    UserIdPublisher::class => function() {
        return new UserIdPublisher(
            \Yii::$container->get(RedisStorage::class),
            \Yii::$container->get(MessagePublisher::class),
            [
                'mysql' => \Yii::createObject(MysqlDbSource::class),
                'mongo' => \Yii::createObject(MongoDbSource::class),
            ]
        );
    },
];
