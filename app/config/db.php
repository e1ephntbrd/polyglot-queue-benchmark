<?php
return [
    'class' => yii\db\Connection::class,
    'dsn' => getenv('DB_DSN') ?: 'mysql:host=mysql;dbname=app;charset=utf8mb4',
    'username' => getenv('DB_USER') ?: 'root',
    'password' => getenv('DB_PASS') ?: 'root',
    'charset' => 'utf8mb4',
    'enableSchemaCache' => false,
];
