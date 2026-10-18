<?php
return [
    'class' => yii\mongodb\Connection::class,
    'dsn' => sprintf(
        '%s/%s',
        getenv('MONGO_URI') ?: 'mongodb://mongodb:27017',
        getenv('MONGO_DB') ?: 'app'
    ),
];
